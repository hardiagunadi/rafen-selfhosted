<?php

namespace App\Http\Controllers;

use App\Http\Requests\RebootOltOnuRequest;
use App\Http\Requests\StoreOltConnectionRequest;
use App\Http\Requests\UpdateOltConnectionRequest;
use App\Models\OltConnection;
use App\Models\OltOnuOptic;
use App\Models\OltOnuOpticHistory;
use App\Services\HsgqSnmpCollector;
use App\Services\OltOnuAlarmEvaluator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Throwable;

class OltSettingsController extends Controller
{
    public function index(): View
    {
        $connections = OltConnection::query()
            ->withCount('onuOptics')
            ->latest()
            ->get();

        $selectedConnection = $connections->firstWhere('id', request()->integer('connection'))
            ?? $connections->first();
        $currentOnus = $selectedConnection
            ? $selectedConnection->onuOptics()->orderBy('pon_interface')->orderBy('onu_number')->get()
            : collect();
        $alarmPayload = $selectedConnection
            ? $this->buildAlarmPayload($selectedConnection, $currentOnus)
            : $this->emptyAlarmPayload();

        return view('super-admin.settings.olt', [
            'connections' => $connections,
            'selectedConnection' => $selectedConnection,
            'summaryRows' => $selectedConnection ? $this->buildSummaryRows($selectedConnection) : collect(),
            'currentOnus' => $currentOnus,
            'activeAlarmRows' => $alarmPayload['active_rows'],
            'historySeriesByOnu' => $alarmPayload['history_series_by_onu'],
            'recentHistoryRows' => $alarmPayload['recent_history_rows'],
            'alarmCounts' => $alarmPayload['alarm_counts'],
            'availableModels' => HsgqSnmpCollector::availableModels(),
        ]);
    }

    public function store(StoreOltConnectionRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['is_active'] = $request->boolean('is_active', true);

        $connection = OltConnection::query()->create($payload);

        return redirect()
            ->route('super-admin.settings.olt.index', ['connection' => $connection->id])
            ->with('success', 'Koneksi OLT berhasil ditambahkan.');
    }

    public function update(UpdateOltConnectionRequest $request, OltConnection $oltConnection): RedirectResponse
    {
        $payload = $request->validated();
        $payload['is_active'] = $request->boolean('is_active', $oltConnection->is_active);

        $oltConnection->update($payload);

        return redirect()
            ->route('super-admin.settings.olt.index', ['connection' => $oltConnection->id])
            ->with('success', 'Koneksi OLT berhasil diperbarui.');
    }

    public function destroy(OltConnection $oltConnection): RedirectResponse
    {
        $oltConnection->delete();

        return redirect()
            ->route('super-admin.settings.olt.index')
            ->with('success', 'Koneksi OLT berhasil dihapus.');
    }

    public function autoDetectModel(OltConnection $oltConnection, HsgqSnmpCollector $collector): RedirectResponse
    {
        try {
            $detected = $collector->detectModelFromSnmp($oltConnection->toArray());

            if (is_string($detected['matched_model']) && $detected['matched_model'] !== '') {
                $oltConnection->update([
                    'olt_model' => $detected['matched_model'],
                ]);
            }

            return redirect()
                ->route('super-admin.settings.olt.index', ['connection' => $oltConnection->id])
                ->with(($detected['matched_model'] ?? null) !== null ? 'success' : 'error', ($detected['matched_model'] ?? null) !== null
                    ? 'Model OLT berhasil dideteksi: '.$detected['matched_model']
                    : 'SNMP terhubung, tetapi model OLT belum terpetakan.');
        } catch (Throwable $throwable) {
            return redirect()
                ->route('super-admin.settings.olt.index', ['connection' => $oltConnection->id])
                ->with('error', 'Deteksi model OLT gagal: '.$throwable->getMessage());
        }
    }

    public function autoDetectOid(OltConnection $oltConnection, HsgqSnmpCollector $collector): RedirectResponse
    {
        try {
            $detected = $collector->detectMappingFromModel($oltConnection->toArray());

            $oltConnection->update($detected['oids']);

            return redirect()
                ->route('super-admin.settings.olt.index', ['connection' => $oltConnection->id])
                ->with('success', 'Mapping OID OLT berhasil diperbarui dari profil model.');
        } catch (Throwable $throwable) {
            return redirect()
                ->route('super-admin.settings.olt.index', ['connection' => $oltConnection->id])
                ->with('error', 'Deteksi OID OLT gagal: '.$throwable->getMessage());
        }
    }

    public function poll(OltConnection $oltConnection, HsgqSnmpCollector $collector): RedirectResponse
    {
        try {
            $records = $collector->collectEssential($oltConnection);
            $now = now();
            $seenOnuIndexes = [];

            if ($records !== []) {
                foreach ($records as $record) {
                    $onuIndex = (string) $record['onu_index'];
                    $seenOnuIndexes[] = $onuIndex;

                    $onuOptic = OltOnuOptic::query()->updateOrCreate(
                        [
                            'olt_connection_id' => $oltConnection->id,
                            'onu_index' => $onuIndex,
                        ],
                        [
                            'pon_interface' => $record['pon_interface'] ?? null,
                            'onu_number' => $record['onu_number'] ?? null,
                            'serial_number' => $record['serial_number'] ?? null,
                            'onu_name' => $record['onu_name'] ?? null,
                            'distance_m' => $record['distance_m'] ?? null,
                            'rx_onu_dbm' => $record['rx_onu_dbm'] ?? null,
                            'tx_onu_dbm' => $record['tx_onu_dbm'] ?? null,
                            'rx_olt_dbm' => $record['rx_olt_dbm'] ?? null,
                            'tx_olt_dbm' => $record['tx_olt_dbm'] ?? null,
                            'status' => $record['status'] ?? null,
                            'raw_payload' => $record['raw_payload'] ?? [],
                            'last_seen_at' => $now,
                        ],
                    );

                    OltOnuOpticHistory::query()->create([
                        'olt_connection_id' => $oltConnection->id,
                        'olt_onu_optic_id' => $onuOptic->id,
                        'onu_index' => $onuIndex,
                        'pon_interface' => $record['pon_interface'] ?? null,
                        'onu_number' => $record['onu_number'] ?? null,
                        'serial_number' => $record['serial_number'] ?? null,
                        'onu_name' => $record['onu_name'] ?? null,
                        'distance_m' => $record['distance_m'] ?? null,
                        'rx_onu_dbm' => $record['rx_onu_dbm'] ?? null,
                        'tx_onu_dbm' => $record['tx_onu_dbm'] ?? null,
                        'rx_olt_dbm' => $record['rx_olt_dbm'] ?? null,
                        'tx_olt_dbm' => $record['tx_olt_dbm'] ?? null,
                        'status' => $record['status'] ?? null,
                        'raw_payload' => $record['raw_payload'] ?? [],
                        'polled_at' => $now,
                    ]);
                }
            }

            OltOnuOptic::query()
                ->where('olt_connection_id', $oltConnection->id)
                ->when(
                    $seenOnuIndexes !== [],
                    fn ($query) => $query->whereNotIn('onu_index', $seenOnuIndexes),
                )
                ->delete();

            $oltConnection->update([
                'last_polled_at' => $now,
                'last_poll_success' => true,
                'last_poll_message' => 'Polling OLT berhasil. '.count($records).' ONU diperbarui.',
            ]);

            return redirect()
                ->route('super-admin.settings.olt.index', ['connection' => $oltConnection->id])
                ->with('success', 'Polling OLT berhasil. '.count($records).' ONU diperbarui.');
        } catch (Throwable $throwable) {
            $oltConnection->update([
                'last_polled_at' => Carbon::now(),
                'last_poll_success' => false,
                'last_poll_message' => $throwable->getMessage(),
            ]);

            return redirect()
                ->route('super-admin.settings.olt.index', ['connection' => $oltConnection->id])
                ->with('error', 'Polling OLT gagal: '.$throwable->getMessage());
        }
    }

    /**
     * @param  Collection<int, OltOnuOptic>  $currentOnus
     * @return array{
     *     active_rows: Collection<int, array<string, mixed>>,
     *     history_series_by_onu: Collection<string, Collection<int, OltOnuOpticHistory>>,
     *     recent_history_rows: Collection<int, OltOnuOpticHistory>,
     *     alarm_counts: array{critical: int, warning: int, normal: int}
     * }
     */
    private function buildAlarmPayload(OltConnection $oltConnection, Collection $currentOnus): array
    {
        if ($currentOnus->isEmpty()) {
            return $this->emptyAlarmPayload();
        }

        $historyLimit = (int) config('olt.synthetic_alarm.history_limit', 8);
        $recentEntriesLimit = (int) config('olt.synthetic_alarm.recent_entries_limit', 20);
        $historiesByOnu = $oltConnection->onuOpticHistories()
            ->whereIn('onu_index', $currentOnus->pluck('onu_index')->all())
            ->latest('polled_at')
            ->get()
            ->groupBy('onu_index')
            ->map(fn (Collection $items): Collection => $items->take($historyLimit));
        $alarmEvaluator = app(OltOnuAlarmEvaluator::class);
        $activeRows = $currentOnus
            ->map(function (OltOnuOptic $onu) use ($alarmEvaluator, $historiesByOnu): ?array {
                /** @var Collection<int, OltOnuOpticHistory> $onuHistories */
                $onuHistories = $historiesByOnu->get($onu->onu_index, collect());
                $evaluation = $alarmEvaluator->evaluate($onu, $onuHistories->skip(1)->first());

                if ($evaluation['severity'] === 'none') {
                    return null;
                }

                return [
                    'onu' => $onu,
                    'severity' => $evaluation['severity'],
                    'label' => $evaluation['label'],
                    'summary' => $evaluation['summary'],
                    'reasons' => $evaluation['reasons'],
                    'current_rx_onu_dbm' => $evaluation['current_rx_onu_dbm'],
                    'previous_rx_onu_dbm' => $evaluation['previous_rx_onu_dbm'],
                    'rx_delta_db' => $evaluation['rx_delta_db'],
                ];
            })
            ->filter()
            ->values()
            ->sortByDesc(fn (array $row): int => $row['severity'] === 'critical' ? 2 : 1)
            ->values();

        return [
            'active_rows' => $activeRows,
            'history_series_by_onu' => $historiesByOnu,
            'recent_history_rows' => $oltConnection->onuOpticHistories()
                ->latest('polled_at')
                ->limit($recentEntriesLimit)
                ->get(),
            'alarm_counts' => [
                'critical' => $activeRows->where('severity', 'critical')->count(),
                'warning' => $activeRows->where('severity', 'warning')->count(),
                'normal' => max(0, $currentOnus->count() - $activeRows->count()),
            ],
        ];
    }

    /**
     * @return array{
     *     active_rows: Collection<int, array<string, mixed>>,
     *     history_series_by_onu: Collection<string, Collection<int, OltOnuOpticHistory>>,
     *     recent_history_rows: Collection<int, OltOnuOpticHistory>,
     *     alarm_counts: array{critical: int, warning: int, normal: int}
     * }
     */
    private function emptyAlarmPayload(): array
    {
        return [
            'active_rows' => collect(),
            'history_series_by_onu' => collect(),
            'recent_history_rows' => collect(),
            'alarm_counts' => [
                'critical' => 0,
                'warning' => 0,
                'normal' => 0,
            ],
        ];
    }

    public function rebootOnu(
        OltConnection $oltConnection,
        RebootOltOnuRequest $request,
        HsgqSnmpCollector $collector,
    ): RedirectResponse {
        $onuIndex = (string) $request->validated('onu_index');

        $onuExists = $oltConnection->onuOptics()
            ->where('onu_index', $onuIndex)
            ->exists();

        if (! $onuExists) {
            return redirect()
                ->route('super-admin.settings.olt.index', ['connection' => $oltConnection->id])
                ->with('error', 'ONU tidak ditemukan pada data OLT ini.');
        }

        try {
            $collector->rebootOnu($oltConnection, $onuIndex);

            return redirect()
                ->route('super-admin.settings.olt.index', ['connection' => $oltConnection->id])
                ->with('success', 'Perintah reboot ONU berhasil dikirim ke OLT.');
        } catch (Throwable $throwable) {
            return redirect()
                ->route('super-admin.settings.olt.index', ['connection' => $oltConnection->id])
                ->with('error', 'Reboot ONU gagal: '.$throwable->getMessage());
        }
    }

    /**
     * @return Collection<int, array{port_id: string, total: int, online: int, offline: int, avg_rx_onu_dbm: float|null}>
     */
    private function buildSummaryRows(OltConnection $oltConnection): Collection
    {
        return $oltConnection->onuOptics()
            ->whereNotNull('pon_interface')
            ->get()
            ->groupBy('pon_interface')
            ->map(function (Collection $items, string $portId): array {
                $total = $items->count();
                $online = $items->where('status', 'online')->count();
                $rxValues = $items
                    ->pluck('rx_onu_dbm')
                    ->filter(fn ($value): bool => $value !== null)
                    ->map(fn ($value): float => (float) $value);

                return [
                    'port_id' => $portId,
                    'total' => $total,
                    'online' => $online,
                    'offline' => max(0, $total - $online),
                    'avg_rx_onu_dbm' => $rxValues->isNotEmpty() ? round($rxValues->avg(), 2) : null,
                ];
            })
            ->sortBy('port_id')
            ->values();
    }
}
