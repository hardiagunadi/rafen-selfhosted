<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOutageRequest;
use App\Http\Requests\StoreOutageUpdateRequest;
use App\Http\Requests\UpdateOutageRequest;
use App\Models\Outage;
use App\Models\OutageAffectedArea;
use App\Models\OutageUpdate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OutageController extends Controller
{
    public function index(): View
    {
        return view('super-admin.outages', [
            'outages' => Outage::query()
                ->with(['affectedAreas', 'updates', 'createdBy'])
                ->latest('started_at')
                ->get(),
        ]);
    }

    public function store(StoreOutageRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $areaLabels = $this->normalizeAreaLabels(
            $data['custom_areas'] ?? null,
            $data['area_labels'] ?? null
        );

        $outage = DB::transaction(function () use ($data, $areaLabels): Outage {
            $outage = Outage::query()->create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'status' => Outage::STATUS_OPEN,
                'severity' => $data['severity'],
                'started_at' => $data['started_at'],
                'estimated_resolved_at' => $data['estimated_resolved_at'] ?? null,
                'created_by_id' => auth()->id(),
                'include_status_link' => (bool) ($data['include_status_link'] ?? true),
            ]);

            foreach ($areaLabels as $label) {
                OutageAffectedArea::query()->create([
                    'outage_id' => $outage->id,
                    'area_type' => 'keyword',
                    'label' => $label,
                ]);
            }

            OutageUpdate::query()->create([
                'outage_id' => $outage->id,
                'user_id' => auth()->id(),
                'type' => 'created',
                'body' => 'Insiden gangguan dibuat.',
                'is_public' => true,
            ]);

            return $outage;
        });

        return redirect()
            ->route('super-admin.outages.show', $outage)
            ->with('success', 'Insiden gangguan berhasil dibuat.');
    }

    public function show(Outage $outage): View
    {
        return view('super-admin.outage-show', [
            'outage' => $outage->load(['affectedAreas', 'updates.user', 'createdBy']),
        ]);
    }

    public function update(UpdateOutageRequest $request, Outage $outage): RedirectResponse
    {
        $data = $request->validated();
        $areaLabels = $this->normalizeAreaLabels(
            $data['custom_areas'] ?? null,
            $data['area_labels'] ?? null
        );

        DB::transaction(function () use ($data, $outage, $areaLabels): void {
            $outage->update([
                'title' => $data['title'] ?? $outage->title,
                'description' => $data['description'] ?? $outage->description,
                'severity' => $data['severity'] ?? $outage->severity,
                'status' => $data['status'] ?? $outage->status,
                'started_at' => $data['started_at'] ?? $outage->started_at,
                'estimated_resolved_at' => $data['estimated_resolved_at'] ?? $outage->estimated_resolved_at,
                'include_status_link' => array_key_exists('include_status_link', $data)
                    ? (bool) $data['include_status_link']
                    : $outage->include_status_link,
            ]);

            if (array_key_exists('custom_areas', $data) || array_key_exists('area_labels', $data)) {
                $outage->affectedAreas()->delete();

                foreach ($areaLabels as $label) {
                    OutageAffectedArea::query()->create([
                        'outage_id' => $outage->id,
                        'area_type' => 'keyword',
                        'label' => $label,
                    ]);
                }
            }
        });

        return redirect()
            ->route('super-admin.outages.show', $outage)
            ->with('success', 'Insiden gangguan berhasil diperbarui.');
    }

    public function addUpdate(StoreOutageUpdateRequest $request, Outage $outage): RedirectResponse
    {
        $data = $request->validated();
        $changeStatus = $data['change_status'] ?? null;

        DB::transaction(function () use ($data, $outage, $changeStatus): void {
            $body = $data['body'] ?? null;
            $meta = $data['meta'] ?? null;
            $type = $data['type'] ?? 'note';

            if ($changeStatus !== null && $changeStatus !== $outage->status) {
                $outage->update([
                    'status' => $changeStatus,
                    'resolved_at' => $changeStatus === Outage::STATUS_RESOLVED ? now() : null,
                ]);

                $type = $changeStatus === Outage::STATUS_RESOLVED ? 'resolved' : 'status_change';
                $meta = 'Status diubah ke '.strtoupper(str_replace('_', ' ', $changeStatus)).'.';
            }

            OutageUpdate::query()->create([
                'outage_id' => $outage->id,
                'user_id' => auth()->id(),
                'type' => $type,
                'body' => $body,
                'meta' => $meta,
                'is_public' => (bool) ($data['is_public'] ?? true),
            ]);
        });

        return redirect()
            ->route('super-admin.outages.show', $outage)
            ->with('success', 'Update gangguan berhasil ditambahkan.');
    }

    public function resolve(Outage $outage): RedirectResponse
    {
        DB::transaction(function () use ($outage): void {
            $outage->update([
                'status' => Outage::STATUS_RESOLVED,
                'resolved_at' => now(),
            ]);

            OutageUpdate::query()->create([
                'outage_id' => $outage->id,
                'user_id' => auth()->id(),
                'type' => 'resolved',
                'body' => 'Layanan dinyatakan pulih.',
                'meta' => 'Gangguan ditutup.',
                'is_public' => true,
            ]);
        });

        return redirect()
            ->route('super-admin.outages.show', $outage)
            ->with('success', 'Gangguan berhasil diselesaikan.');
    }

    public function destroy(Outage $outage): RedirectResponse
    {
        $outage->delete();

        return redirect()
            ->route('super-admin.outages.index')
            ->with('success', 'Gangguan berhasil dihapus.');
    }

    /**
     * @param  array<int, mixed>|null  $customAreas
     * @return array<int, string>
     */
    private function normalizeAreaLabels(?array $customAreas, ?string $areaLabels): array
    {
        $labels = [];

        if (is_array($customAreas)) {
            $labels = array_merge($labels, $customAreas);
        }

        if (is_string($areaLabels) && trim($areaLabels) !== '') {
            $labels = array_merge($labels, preg_split('/[\r\n,]+/', $areaLabels) ?: []);
        }

        return collect($labels)
            ->map(fn (mixed $label): string => trim((string) $label))
            ->filter(fn (string $label): bool => $label !== '')
            ->unique()
            ->values()
            ->all();
    }
}
