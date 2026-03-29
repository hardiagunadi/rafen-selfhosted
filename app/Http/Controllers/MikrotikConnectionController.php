<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMikrotikConnectionRequest;
use App\Http\Requests\TestMikrotikConnectionRequest;
use App\Http\Requests\UpdateMikrotikConnectionRequest;
use App\Models\MikrotikConnection;
use App\Services\MikrotikPingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class MikrotikConnectionController extends Controller
{
    public function index(): View
    {
        return view('super-admin.settings.mikrotik', [
            'connections' => MikrotikConnection::query()->latest()->get(),
        ]);
    }

    public function store(StoreMikrotikConnectionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['use_ssl'] = $request->boolean('use_ssl');
        $data['is_active'] = $request->boolean('is_active', true);
        $data['username'] = $data['username'] ?: $this->generateApiUsername();
        $data['password'] = $data['password'] ?: $this->generateApiSecret();
        $data['radius_secret'] = $data['radius_secret'] ?: $data['password'];

        MikrotikConnection::query()->create($data);

        return redirect()
            ->route('super-admin.settings.mikrotik.index')
            ->with('success', 'Koneksi Mikrotik berhasil ditambahkan.');
    }

    public function update(UpdateMikrotikConnectionRequest $request, MikrotikConnection $mikrotikConnection): RedirectResponse
    {
        $data = $request->validated();
        $data['use_ssl'] = $request->boolean('use_ssl', $mikrotikConnection->use_ssl);
        $data['is_active'] = $request->boolean('is_active', $mikrotikConnection->is_active);
        $data['radius_secret'] = $data['radius_secret'] ?? $mikrotikConnection->radius_secret;

        $mikrotikConnection->update($data);

        return redirect()
            ->route('super-admin.settings.mikrotik.index')
            ->with('success', 'Koneksi Mikrotik berhasil diperbarui.');
    }

    public function destroy(MikrotikConnection $mikrotikConnection): RedirectResponse
    {
        $mikrotikConnection->delete();

        return redirect()
            ->route('super-admin.settings.mikrotik.index')
            ->with('success', 'Koneksi Mikrotik berhasil dihapus.');
    }

    public function test(TestMikrotikConnectionRequest $request, MikrotikPingService $pingService): JsonResponse
    {
        $data = $request->validated();
        $timeout = (int) ($data['api_timeout'] ?? 10);
        $useSsl = (bool) ($data['use_ssl'] ?? false);
        $port = $useSsl
            ? (int) ($data['api_ssl_port'] ?? 8729)
            : (int) ($data['api_port'] ?? 8728);

        $result = $pingService->probe($data['host'], $timeout, $port, $useSsl);
        $message = $result['online']
            ? 'Koneksi OK'.($result['latency'] ? ' ('.$result['latency'].' ms)' : '')
            : ($result['ping_success']
                ? 'Ping OK, port API '.$data['host'].':'.$port.' tertutup'
                : 'Ping ke '.$data['host'].' gagal');

        return response()->json([
            'success' => $result['online'],
            'latency' => $result['latency'],
            'port_open' => $result['port_open'],
            'message' => $message,
        ], $result['online'] ? 200 : 422);
    }

    public function pingNow(MikrotikConnection $mikrotikConnection, MikrotikPingService $pingService): JsonResponse
    {
        try {
            $pingService->ping($mikrotikConnection);
            $mikrotikConnection->refresh();

            return response()->json([
                'is_online' => $mikrotikConnection->is_online,
                'ping_unstable' => $mikrotikConnection->ping_unstable,
                'message' => $mikrotikConnection->last_ping_message,
            ]);
        } catch (Throwable $throwable) {
            return response()->json([
                'error' => 'Ping gagal: '.$throwable->getMessage(),
            ], 500);
        }
    }

    private function generateApiUsername(): string
    {
        return 'TMDRadius'.Str::upper(Str::random(6));
    }

    private function generateApiSecret(): string
    {
        return Str::password(10);
    }
}
