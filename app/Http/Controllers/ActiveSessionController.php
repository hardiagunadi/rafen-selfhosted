<?php

namespace App\Http\Controllers;

use App\Models\MikrotikConnection;
use App\Models\RadiusAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActiveSessionController extends Controller
{
    public function pppoe(): View
    {
        return $this->renderSessionPage(
            service: 'pppoe',
            isActive: true,
            routePrefix: 'super-admin.sessions.pppoe',
            pageTitle: 'Sesi PPPoE Aktif',
            pageDescription: 'Pantau akun PPPoE yang sedang aktif berdasarkan data RADIUS lokal self-hosted.',
        );
    }

    public function pppoeDatatable(Request $request): JsonResponse
    {
        return $this->datatable($request, service: 'pppoe', isActive: true);
    }

    public function pppoeInactive(): View
    {
        return $this->renderSessionPage(
            service: 'pppoe',
            isActive: false,
            routePrefix: 'super-admin.sessions.pppoe-inactive',
            pageTitle: 'Sesi PPPoE Tidak Aktif',
            pageDescription: 'Pantau akun PPPoE yang tidak aktif atau sudah offline dari cache RADIUS lokal.',
        );
    }

    public function pppoeInactiveDatatable(Request $request): JsonResponse
    {
        return $this->datatable($request, service: 'pppoe', isActive: false);
    }

    public function hotspot(): View
    {
        return $this->renderSessionPage(
            service: 'hotspot',
            isActive: true,
            routePrefix: 'super-admin.sessions.hotspot',
            pageTitle: 'Sesi Hotspot Aktif',
            pageDescription: 'Pantau akun Hotspot yang sedang aktif berdasarkan data RADIUS lokal self-hosted.',
        );
    }

    public function hotspotDatatable(Request $request): JsonResponse
    {
        return $this->datatable($request, service: 'hotspot', isActive: true);
    }

    public function hotspotInactive(): View
    {
        return $this->renderSessionPage(
            service: 'hotspot',
            isActive: false,
            routePrefix: 'super-admin.sessions.hotspot-inactive',
            pageTitle: 'Sesi Hotspot Tidak Aktif',
            pageDescription: 'Pantau akun Hotspot yang tidak aktif atau sudah offline dari cache RADIUS lokal.',
        );
    }

    public function hotspotInactiveDatatable(Request $request): JsonResponse
    {
        return $this->datatable($request, service: 'hotspot', isActive: false);
    }

    private function renderSessionPage(string $service, bool $isActive, string $routePrefix, string $pageTitle, string $pageDescription): View
    {
        return view('super-admin.sessions-index', [
            'pageTitle' => $pageTitle,
            'pageDescription' => $pageDescription,
            'service' => $service,
            'isActive' => $isActive,
            'routePrefix' => $routePrefix,
            'total' => RadiusAccount::query()
                ->where('service', $service)
                ->where('is_active', $isActive)
                ->count(),
            'routers' => MikrotikConnection::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    private function datatable(Request $request, string $service, bool $isActive): JsonResponse
    {
        $search = trim((string) $request->input('search.value', $request->input('search', '')));
        $draw = $request->integer('draw', 1);
        $start = $request->integer('start', 0);
        $length = max(1, $request->integer('length', 20));

        $query = RadiusAccount::query()
            ->with('mikrotikConnection')
            ->where('service', $service)
            ->where('is_active', $isActive)
            ->when($request->filled('router_id'), function ($builder) use ($request): void {
                $builder->where('mikrotik_connection_id', (int) $request->input('router_id'));
            })
            ->when($search !== '', function ($builder) use ($search): void {
                $builder->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery->where('username', 'like', "%{$search}%")
                        ->orWhere('ipv4_address', 'like', "%{$search}%")
                        ->orWhere('caller_id', 'like', "%{$search}%")
                        ->orWhere('profile', 'like', "%{$search}%")
                        ->orWhere('server_name', 'like', "%{$search}%");
                });
            });

        $recordsFiltered = (clone $query)->count();
        $recordsTotal = RadiusAccount::query()
            ->where('service', $service)
            ->where('is_active', $isActive)
            ->count();

        $rows = $query
            ->latest('updated_at')
            ->skip($start)
            ->take($length)
            ->get();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows->map(function (RadiusAccount $radiusAccount): array {
                return [
                    'username' => $radiusAccount->username,
                    'ipv4' => $radiusAccount->ipv4_address ?: '-',
                    'caller_id' => $radiusAccount->caller_id ?: '-',
                    'uptime' => $radiusAccount->uptime ?: '-',
                    'bytes_in' => $this->formatBytes((int) $radiusAccount->bytes_in),
                    'bytes_out' => $this->formatBytes((int) $radiusAccount->bytes_out),
                    'profile' => $radiusAccount->profile ?: '-',
                    'server_name' => $radiusAccount->server_name ?: '-',
                    'router' => $radiusAccount->mikrotikConnection?->name ?: '-',
                    'updated_at' => $radiusAccount->updated_at?->diffForHumans() ?: '-',
                ];
            })->values(),
        ]);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1048576) {
            return number_format($bytes / 1024, 2).' KB';
        }

        if ($bytes < 1073741824) {
            return number_format($bytes / 1048576, 2).' MB';
        }

        return number_format($bytes / 1073741824, 2).' GB';
    }
}
