<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRadiusNasRequest;
use App\Http\Requests\UpdateRadiusNasRequest;
use App\Models\RadiusNas;
use App\Services\RadiusClientsSynchronizer;
use App\Services\RadiusServiceManager;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use SplFileObject;
use Throwable;

class FreeRadiusSettingsController extends Controller
{
    public function __construct(
        private readonly Filesystem $filesystem,
    ) {}

    public function index(RadiusServiceManager $serviceManager): View
    {
        $clientsPath = (string) config('radius.clients_path');
        $logPath = (string) config('radius.log_path');

        return view('super-admin.settings.freeradius', [
            'radiusNasClients' => RadiusNas::query()->orderBy('name')->get(),
            'clientsPath' => $clientsPath,
            'logPath' => $logPath,
            'syncStatus' => $this->resolveSyncStatus($clientsPath),
            'logPayload' => $this->readLogTail($logPath, 120),
            'serviceStatus' => $serviceManager->status(),
        ]);
    }

    public function store(StoreRadiusNasRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['require_message_authenticator'] = $request->boolean('require_message_authenticator');
        $payload['is_active'] = $request->boolean('is_active');
        $payload['auth_port'] = (int) ($payload['auth_port'] ?? 1812);
        $payload['acct_port'] = (int) ($payload['acct_port'] ?? 1813);

        RadiusNas::query()->create($payload);

        return redirect()
            ->route('super-admin.settings.freeradius.index')
            ->with('success', 'NAS FreeRADIUS berhasil ditambahkan.');
    }

    public function update(UpdateRadiusNasRequest $request, RadiusNas $radiusNas): RedirectResponse
    {
        $payload = $request->validated();
        $payload['require_message_authenticator'] = $request->boolean('require_message_authenticator');
        $payload['is_active'] = $request->boolean('is_active');
        $payload['auth_port'] = (int) ($payload['auth_port'] ?? 1812);
        $payload['acct_port'] = (int) ($payload['acct_port'] ?? 1813);

        $radiusNas->update($payload);

        return redirect()
            ->route('super-admin.settings.freeradius.index')
            ->with('success', 'NAS FreeRADIUS berhasil diperbarui.');
    }

    public function destroy(RadiusNas $radiusNas): RedirectResponse
    {
        $radiusNas->delete();

        return redirect()
            ->route('super-admin.settings.freeradius.index')
            ->with('success', 'NAS FreeRADIUS berhasil dihapus.');
    }

    public function sync(RadiusClientsSynchronizer $synchronizer): RedirectResponse
    {
        try {
            $synchronizer->sync();

            return redirect()
                ->route('super-admin.settings.freeradius.index')
                ->with('success', 'Sinkronisasi NAS clients FreeRADIUS berhasil.');
        } catch (Throwable $throwable) {
            return redirect()
                ->route('super-admin.settings.freeradius.index')
                ->with('error', 'Sinkronisasi NAS clients FreeRADIUS gagal: '.$throwable->getMessage());
        }
    }

    public function service(string $action, RadiusServiceManager $serviceManager): RedirectResponse
    {
        if (! in_array($action, ['status', 'reload', 'restart'], true)) {
            return redirect()
                ->route('super-admin.settings.freeradius.index')
                ->with('error', 'Aksi service FreeRADIUS tidak valid.');
        }

        $result = match ($action) {
            'status' => $serviceManager->status(),
            'reload' => $serviceManager->reload(),
            'restart' => $serviceManager->restart(),
        };

        return redirect()
            ->route('super-admin.settings.freeradius.index')
            ->with(($result['success'] ?? false) ? 'success' : 'error', (string) ($result['message'] ?? 'Permintaan service FreeRADIUS selesai.'));
    }

    /**
     * @return array{status: string, updated_at: ?string, size: ?int, message: string}
     */
    private function resolveSyncStatus(string $clientsPath): array
    {
        if ($clientsPath === '') {
            return [
                'status' => 'unknown',
                'updated_at' => null,
                'size' => null,
                'message' => 'Path clients.conf belum diatur.',
            ];
        }

        if (! $this->filesystem->exists($clientsPath)) {
            $parent = dirname($clientsPath);

            if ($this->filesystem->isDirectory($parent) && ! $this->filesystem->isWritable($parent)) {
                return [
                    'status' => 'denied',
                    'updated_at' => null,
                    'size' => null,
                    'message' => 'Direktori clients tidak dapat ditulis.',
                ];
            }

            return [
                'status' => 'missing',
                'updated_at' => null,
                'size' => null,
                'message' => 'File clients belum ditemukan.',
            ];
        }

        if (! $this->filesystem->isReadable($clientsPath)) {
            return [
                'status' => 'denied',
                'updated_at' => null,
                'size' => null,
                'message' => 'File clients tidak dapat dibaca.',
            ];
        }

        $size = $this->filesystem->size($clientsPath);
        $updatedAt = Carbon::createFromTimestamp($this->filesystem->lastModified($clientsPath))
            ->format('Y-m-d H:i:s');

        if ($size === 0) {
            return [
                'status' => 'empty',
                'updated_at' => $updatedAt,
                'size' => $size,
                'message' => 'File clients masih kosong.',
            ];
        }

        return [
            'status' => 'ok',
            'updated_at' => $updatedAt,
            'size' => $size,
            'message' => 'File clients tersedia dan terisi.',
        ];
    }

    /**
     * @return array{lines: array<int, string>, error: ?string}
     */
    private function readLogTail(string $path, int $limit): array
    {
        if ($path === '') {
            return [
                'lines' => [],
                'error' => 'Path log FreeRADIUS belum diatur.',
            ];
        }

        if (! $this->filesystem->exists($path)) {
            return [
                'lines' => [],
                'error' => 'File log FreeRADIUS tidak ditemukan.',
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
            ];
        } catch (FileNotFoundException) {
            return [
                'lines' => [],
                'error' => 'File log FreeRADIUS tidak ditemukan.',
            ];
        } catch (Throwable $throwable) {
            return [
                'lines' => [],
                'error' => $throwable->getMessage(),
            ];
        }
    }
}
