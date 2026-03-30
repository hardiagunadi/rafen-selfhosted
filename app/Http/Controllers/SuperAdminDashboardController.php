<?php

namespace App\Http\Controllers;

use App\Models\CpeDevice;
use App\Models\MikrotikConnection;
use App\Models\OltConnection;
use App\Models\OltOnuOptic;
use App\Models\RadiusAccount;
use App\Models\RadiusNas;
use App\Models\WaMultiSessionDevice;
use App\Models\WgPeer;
use App\Services\SelfHostedLicenseViewDataService;
use App\Services\SystemLicenseService;
use Illuminate\View\View;

class SuperAdminDashboardController extends Controller
{
    public function index(
        SelfHostedLicenseViewDataService $viewDataService,
        SystemLicenseService $systemLicenseService,
    ): View {
        $layoutData = $viewDataService->forAdminLayout();
        $featureFlags = $layoutData['systemFeatureFlags'] ?? $viewDataService->defaultFeatureFlags();
        $snapshot = $layoutData['systemLicenseSnapshot'] ?? $systemLicenseService->getSnapshot();
        $license = $snapshot['license'] ?? null;

        $stats = [
            'mikrotik_connections' => MikrotikConnection::query()->count(),
            'radius_accounts' => RadiusAccount::query()->count(),
            'radius_nas' => RadiusNas::query()->count(),
            'cpe_devices' => CpeDevice::query()->count(),
            'cpe_online' => CpeDevice::query()->where('status', 'online')->count(),
            'olt_connections' => OltConnection::query()->count(),
            'olt_onu_optics' => OltOnuOptic::query()->count(),
            'wireguard_peers' => WgPeer::query()->count(),
            'wa_devices' => WaMultiSessionDevice::query()->count(),
            'licensed_modules' => count(array_filter($license?->modules ?? [])),
            'active_features' => count(array_filter($featureFlags)),
        ];

        $modules = [
            [
                'title' => 'MikroTik',
                'summary' => $stats['mikrotik_connections'].' koneksi MikroTik',
                'description' => 'Kelola router, health check, dan endpoint RADIUS.',
                'route' => route('super-admin.settings.mikrotik.index'),
                'route_label' => 'Buka MikroTik',
                'enabled' => true,
                'badge' => 'Core',
                'icon' => 'fas fa-network-wired',
                'tone' => 'info',
            ],
            [
                'title' => 'FreeRADIUS',
                'summary' => $stats['radius_accounts'].' akun radius / '.$stats['radius_nas'].' NAS',
                'description' => 'Manajemen akun PPPoE, NAS, dan sinkronisasi reply/check.',
                'route' => route('super-admin.settings.freeradius.index'),
                'route_label' => 'Buka FreeRADIUS',
                'enabled' => (bool) ($featureFlags['radius'] ?? true),
                'badge' => 'Radius',
                'icon' => 'fas fa-user-shield',
                'tone' => 'primary',
            ],
            [
                'title' => 'GenieACS & CPE',
                'summary' => $stats['cpe_devices'].' device / '.$stats['cpe_online'].' online',
                'description' => 'Provisioning modem, refresh parameter, reboot, WiFi, dan PPPoE.',
                'route' => route('super-admin.settings.cpe.index'),
                'route_label' => 'Buka CPE',
                'enabled' => (bool) ($featureFlags['genieacs'] ?? true),
                'badge' => 'ACS',
                'icon' => 'fas fa-wifi',
                'tone' => 'success',
            ],
            [
                'title' => 'OLT',
                'summary' => $stats['olt_connections'].' koneksi / '.$stats['olt_onu_optics'].' ONU',
                'description' => 'Deteksi model/OID, polling ONU, dan ringkasan optic.',
                'route' => route('super-admin.settings.olt.index'),
                'route_label' => 'Buka OLT',
                'enabled' => (bool) ($featureFlags['olt'] ?? true),
                'badge' => 'Fiber',
                'icon' => 'fas fa-broadcast-tower',
                'tone' => 'warning',
            ],
            [
                'title' => 'WireGuard',
                'summary' => $stats['wireguard_peers'].' peer VPN',
                'description' => 'Manajemen peer dan sinkronisasi konfigurasi tunnel.',
                'route' => route('super-admin.settings.wireguard.index'),
                'route_label' => 'Buka WireGuard',
                'enabled' => (bool) ($featureFlags['vpn'] ?? true),
                'badge' => 'VPN',
                'icon' => 'fas fa-shield-alt',
                'tone' => 'secondary',
            ],
            [
                'title' => 'WhatsApp Gateway',
                'summary' => $stats['wa_devices'].' device WhatsApp',
                'description' => 'Monitoring session, restart service, dan kirim test message.',
                'route' => route('super-admin.settings.wa-gateway.index'),
                'route_label' => 'Buka WhatsApp',
                'enabled' => (bool) ($featureFlags['wa'] ?? true),
                'badge' => 'Messaging',
                'icon' => 'fab fa-whatsapp',
                'tone' => 'success',
            ],
        ];

        return view('super-admin.dashboard', [
            'snapshot' => $snapshot,
            'featureFlags' => $featureFlags,
            'stats' => $stats,
            'modules' => $modules,
        ]);
    }
}
