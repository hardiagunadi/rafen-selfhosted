<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenieAcsDeviceActionRequest;
use App\Services\GenieAcsClient;
use App\Services\GenieAcsServiceManager;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use SplFileObject;
use Throwable;

class GenieAcsSettingsController extends Controller
{
    public function __construct(
        private readonly Filesystem $filesystem,
    ) {}

    public function index(GenieAcsServiceManager $serviceManager): View
    {
        $client = GenieAcsClient::fromConfig();
        $status = $client->getStatus();
        $summary = [
            'total_devices' => 0,
            'online_devices' => 0,
            'pending_tasks' => 0,
            'faults' => 0,
        ];

        if ($status['online']) {
            try {
                $summary = $client->summary();
            } catch (Throwable $throwable) {
                $status['message'] = 'NBI GenieACS aktif, tetapi ringkasan gagal diambil: '.$throwable->getMessage();
            }
        }

        $logPath = (string) config('genieacs.log_path');

        return view('super-admin.settings.genieacs', [
            'nbiStatus' => $status,
            'summary' => $summary,
            'serviceStatus' => $serviceManager->overview(),
            'uiUrl' => (string) config('genieacs.ui_url', ''),
            'nbiUrl' => (string) config('genieacs.nbi_url', ''),
            'logPath' => $logPath,
            'logPayload' => $this->readLogTail($logPath, 120),
            'thresholdMinutes' => (int) config('genieacs.online_threshold_minutes', 70),
        ]);
    }

    public function testConnection(): RedirectResponse
    {
        $status = GenieAcsClient::fromConfig()->getStatus();

        return redirect()
            ->route('super-admin.settings.genieacs.index')
            ->with($status['online'] ? 'success' : 'error', $status['message']);
    }

    public function service(string $action, GenieAcsServiceManager $serviceManager): RedirectResponse
    {
        if (! in_array($action, ['status', 'restart-cwmp', 'restart-nbi', 'restart-fs', 'restart-all'], true)) {
            return redirect()
                ->route('super-admin.settings.genieacs.index')
                ->with('error', 'Aksi service GenieACS tidak valid.');
        }

        $result = $serviceManager->control($action);

        return redirect()
            ->route('super-admin.settings.genieacs.index')
            ->with(($result['success'] ?? false) ? 'success' : 'error', (string) ($result['message'] ?? 'Permintaan service GenieACS selesai.'));
    }

    public function connectionRequest(GenieAcsDeviceActionRequest $request): RedirectResponse
    {
        $result = GenieAcsClient::fromConfig()->sendConnectionRequest(
            (string) $request->validated('device_id'),
            (string) ($request->validated('profile') ?: 'igd'),
        );

        return redirect()
            ->route('super-admin.settings.genieacs.index')
            ->with(($result['success'] ?? false) ? 'success' : 'error', $result['message']);
    }

    public function clearTasks(GenieAcsDeviceActionRequest $request): RedirectResponse
    {
        try {
            $deletedCount = GenieAcsClient::fromConfig()->deleteDeviceTasks((string) $request->validated('device_id'));

            return redirect()
                ->route('super-admin.settings.genieacs.index')
                ->with('success', $deletedCount.' task GenieACS berhasil dihapus.');
        } catch (Throwable $throwable) {
            return redirect()
                ->route('super-admin.settings.genieacs.index')
                ->with('error', 'Task GenieACS gagal dibersihkan: '.$throwable->getMessage());
        }
    }

    /**
     * @return array{lines: array<int, string>, error: ?string, updated_at: ?string}
     */
    private function readLogTail(string $path, int $limit): array
    {
        if ($path === '') {
            return [
                'lines' => [],
                'error' => 'Path log GenieACS belum diatur.',
                'updated_at' => null,
            ];
        }

        if (! $this->filesystem->exists($path)) {
            return [
                'lines' => [],
                'error' => 'File log GenieACS tidak ditemukan.',
                'updated_at' => null,
            ];
        }

        try {
            $file = new SplFileObject($path, 'r');
            $file->setFlags(SplFileObject::DROP_NEW_LINE);
            $buffer = [];

            foreach ($file as $line) {
                if ($line === null) {
                    continue;
                }

                $buffer[] = $line;

                if (count($buffer) > $limit) {
                    array_shift($buffer);
                }
            }

            return [
                'lines' => $buffer,
                'error' => null,
                'updated_at' => Carbon::createFromTimestamp($this->filesystem->lastModified($path))
                    ->format('Y-m-d H:i:s'),
            ];
        } catch (FileNotFoundException) {
            return [
                'lines' => [],
                'error' => 'File log GenieACS tidak ditemukan.',
                'updated_at' => null,
            ];
        } catch (Throwable $throwable) {
            return [
                'lines' => [],
                'error' => $throwable->getMessage(),
                'updated_at' => null,
            ];
        }
    }
}
