<!DOCTYPE html>
<html lang="id">
<head>
    @php($systemSettings = \App\Models\SystemSetting::instance())
    @php($updateNotice = app(\App\Services\SelfHostedUpdateNoticeResolver::class)->resolve($systemSettings))
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1367a4">
    <title>@yield('title', $systemSettings->appName(config('app.name', 'Rafen Self-Hosted')))</title>
    <link rel="manifest" href="{{ route('manifest.admin') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ route('manifest.admin.icon', ['size' => 180]) }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
</head>
<body class="hold-transition layout-top-nav">
    <div class="wrapper">
        <nav class="main-header navbar navbar-expand-md navbar-light navbar-white">
            <div class="container">
                <a href="{{ auth()->check() && ! auth()->user()->isSuperAdmin() ? route('shifts.my') : route('super-admin.dashboard') }}" class="navbar-brand">
                    <span class="brand-text font-weight-bold">{{ $systemSettings->appName('Rafen Self-Hosted') }}</span>
                </a>
                <div class="navbar-nav ml-auto align-items-center">
                    <a href="{{ auth()->check() && ! auth()->user()->isSuperAdmin() ? route('shifts.my') : route('super-admin.dashboard') }}" class="nav-link {{ request()->routeIs('super-admin.dashboard', 'shifts.my') ? 'active' : '' }}">Dashboard</a>
                    <a href="{{ route('super-admin.users.index') }}" class="nav-link {{ request()->routeIs('super-admin.users.*') ? 'active' : '' }}">Pengguna</a>
                    <a href="{{ route('shifts.my') }}" class="nav-link {{ request()->routeIs('shifts.*') ? 'active' : '' }}">Shift</a>
                    <a href="{{ route('super-admin.settings.system.index') }}" class="nav-link {{ request()->routeIs('super-admin.settings.system.*') ? 'active' : '' }}">Profil Sistem</a>
                    <a href="{{ route('super-admin.settings.license') }}" class="nav-link {{ request()->routeIs('super-admin.settings.license*') ? 'active' : '' }}">Lisensi Sistem</a>
                    <a href="{{ route('super-admin.settings.mikrotik.index') }}" class="nav-link {{ request()->routeIs('super-admin.settings.mikrotik.*') ? 'active' : '' }}">MikroTik</a>
                    @if(($systemFeatureFlags['radius'] ?? true) === true)
                        <a href="{{ route('super-admin.settings.bandwidth-profiles.index') }}" class="nav-link {{ request()->routeIs('super-admin.settings.bandwidth-profiles.*') ? 'active' : '' }}">Bandwidth</a>
                        <a href="{{ route('super-admin.settings.profile-groups.index') }}" class="nav-link {{ request()->routeIs('super-admin.settings.profile-groups.*') ? 'active' : '' }}">Profile Group</a>
                        <a href="{{ route('super-admin.settings.ppp-profiles.index') }}" class="nav-link {{ request()->routeIs('super-admin.settings.ppp-profiles.*') ? 'active' : '' }}">Paket PPP</a>
                        <a href="{{ route('super-admin.settings.ppp-users.index') }}" class="nav-link {{ request()->routeIs('super-admin.settings.ppp-users.*') ? 'active' : '' }}">Pelanggan PPP</a>
                        <a href="{{ route('super-admin.odps.index') }}" class="nav-link {{ request()->routeIs('super-admin.odps.*') ? 'active' : '' }}">ODP</a>
                        <a href="{{ route('super-admin.customer-map.index') }}" class="nav-link {{ request()->routeIs('super-admin.customer-map.*') ? 'active' : '' }}">Peta Pelanggan</a>
                        <a href="{{ route('super-admin.settings.hotspot-profiles.index') }}" class="nav-link {{ request()->routeIs('super-admin.settings.hotspot-profiles.*') ? 'active' : '' }}">Paket Hotspot</a>
                        <a href="{{ route('super-admin.settings.hotspot-users.index') }}" class="nav-link {{ request()->routeIs('super-admin.settings.hotspot-users.*') ? 'active' : '' }}">Pelanggan Hotspot</a>
                        <a href="{{ route('super-admin.vouchers.index') }}" class="nav-link {{ request()->routeIs('super-admin.vouchers.*') ? 'active' : '' }}">Voucher</a>
                        <a href="{{ route('super-admin.invoices.index') }}" class="nav-link {{ request()->routeIs('super-admin.invoices.*') ? 'active' : '' }}">Invoice</a>
                        <a href="{{ route('super-admin.payments.index') }}" class="nav-link {{ request()->routeIs('super-admin.payments.*') ? 'active' : '' }}">Pembayaran</a>
                        <a href="{{ route('teknisi-setoran.index') }}" class="nav-link {{ request()->routeIs('teknisi-setoran.*') ? 'active' : '' }}">Setoran Teknisi</a>
                        <a href="{{ route('super-admin.reports.income') }}" class="nav-link {{ request()->routeIs('super-admin.reports.*') ? 'active' : '' }}">Laporan</a>
                    @endif
                    @if(($systemFeatureFlags['radius'] ?? true) === true)
                        <a href="{{ route('super-admin.settings.radius-accounts.index') }}" class="nav-link {{ request()->routeIs('super-admin.settings.radius-accounts.*') ? 'active' : '' }}">Radius Accounts</a>
                        <a href="{{ route('super-admin.sessions.pppoe') }}" class="nav-link {{ request()->routeIs('super-admin.sessions.pppoe*') ? 'active' : '' }}">Sesi PPP</a>
                        <a href="{{ route('super-admin.sessions.hotspot') }}" class="nav-link {{ request()->routeIs('super-admin.sessions.hotspot*') ? 'active' : '' }}">Sesi Hotspot</a>
                    @endif
                    @if(($systemFeatureFlags['radius'] ?? true) === true)
                        <a href="{{ route('super-admin.settings.freeradius.index') }}" class="nav-link {{ request()->routeIs('super-admin.settings.freeradius.*') ? 'active' : '' }}">FreeRADIUS</a>
                    @endif
                    @if(($systemFeatureFlags['genieacs'] ?? true) === true)
                        <a href="{{ route('super-admin.settings.genieacs.index') }}" class="nav-link {{ request()->routeIs('super-admin.settings.genieacs.*') ? 'active' : '' }}">GenieACS</a>
                        <a href="{{ route('super-admin.settings.cpe.index') }}" class="nav-link {{ request()->routeIs('super-admin.settings.cpe.*') ? 'active' : '' }}">CPE</a>
                    @endif
                    @if(($systemFeatureFlags['olt'] ?? true) === true)
                        <a href="{{ route('super-admin.settings.olt.index') }}" class="nav-link {{ request()->routeIs('super-admin.settings.olt.*') ? 'active' : '' }}">OLT</a>
                    @endif
                    @if(($systemFeatureFlags['vpn'] ?? true) === true)
                        <a href="{{ route('super-admin.settings.wireguard.index') }}" class="nav-link {{ request()->routeIs('super-admin.settings.wireguard.*') ? 'active' : '' }}">WireGuard</a>
                    @endif
                    @if(($systemFeatureFlags['wa'] ?? true) === true)
                        <a href="{{ route('super-admin.settings.wa-gateway.index') }}" class="nav-link {{ request()->routeIs('super-admin.settings.wa-gateway.*') ? 'active' : '' }}">WhatsApp</a>
                        <a href="{{ route('super-admin.wa-blast.index') }}" class="nav-link {{ request()->routeIs('super-admin.wa-blast.*') ? 'active' : '' }}">WA Blast</a>
                        <a href="{{ route('super-admin.wa-chat.index') }}" class="nav-link {{ request()->routeIs('super-admin.wa-chat.*') ? 'active' : '' }}">WA Chat</a>
                        <a href="{{ route('super-admin.wa-keyword-rules.index') }}" class="nav-link {{ request()->routeIs('super-admin.wa-keyword-rules.*') ? 'active' : '' }}">Keyword WA</a>
                        <a href="{{ route('super-admin.wa-tickets.index') }}" class="nav-link {{ request()->routeIs('super-admin.wa-tickets.*') ? 'active' : '' }}">Tiket WA</a>
                    @endif
                    <a href="{{ route('super-admin.outages.index') }}" class="nav-link {{ request()->routeIs('super-admin.outages.*') ? 'active' : '' }}">Gangguan</a>
                    <a href="{{ route('super-admin.logs.activity') }}" class="nav-link {{ request()->routeIs('super-admin.logs.*') ? 'active' : '' }}">Log Aktivitas</a>
                    <a href="{{ route('super-admin.tools.backup') }}" class="nav-link {{ request()->routeIs('super-admin.tools.*') ? 'active' : '' }}">System Tools</a>
                    <a href="{{ route('super-admin.terminal.index') }}" class="nav-link {{ request()->routeIs('super-admin.terminal.*') ? 'active' : '' }}">Terminal</a>
                    <a href="{{ route('super-admin.help.index') }}" class="nav-link {{ request()->routeIs('super-admin.help.*') ? 'active' : '' }}">Bantuan</a>
                    @if(config('push.vapid.public_key') !== '')
                        <button id="push-subscribe-btn" type="button" class="btn btn-outline-secondary btn-sm ml-2" data-subscribed="0" title="Aktifkan notifikasi push">
                            <i class="far fa-bell"></i>
                        </button>
                    @endif
                    @auth
                        <form action="{{ route('logout') }}" method="POST" class="mb-0 ml-2">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary btn-sm">Logout</button>
                        </form>
                    @endauth
                </div>
            </div>
        </nav>

        <div class="content-wrapper">
            <div class="content pt-3">
                @includeWhen(($isSelfHostedLicenseEnabled ?? false) && ($systemLicenseSnapshot['license']->validation_error ?? false), 'self-hosted-license.partials.admin-alert')
                @if($updateNotice !== null)
                    <div class="container-fluid mb-3">
                        <div class="alert alert-{{ $updateNotice['severity'] }} mb-0">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start">
                                <div class="pr-md-3">
                                    <div class="font-weight-bold mb-1">
                                        <i class="fas fa-download mr-1"></i>{{ $updateNotice['headline'] }}
                                    </div>
                                    <div class="small mb-1">
                                        Versi terpasang <strong>{{ $updateNotice['current_version'] }}</strong>
                                        <span class="mx-1">-></span>
                                        Versi tersedia <strong>{{ $updateNotice['available_version'] }}</strong>
                                        @if($updateNotice['available_at'])
                                            <span class="ml-1 text-nowrap">({{ $updateNotice['available_at']->format('d M Y H:i') }})</span>
                                        @endif
                                    </div>
                                    <div>{{ $updateNotice['summary'] }}</div>
                                    <div class="small mt-2">
                                        <strong>Mode update:</strong> manual terjadwal agar aplikasi tidak down saat jam operasional.
                                    </div>
                                    <div class="small mt-1">{{ $updateNotice['instructions'] }}</div>
                                </div>
                                <div class="mt-3 mt-md-0 d-flex" style="gap: .5rem;">
                                    @if(filled($updateNotice['release_notes_url']))
                                        <a href="{{ $updateNotice['release_notes_url'] }}" class="btn btn-sm btn-outline-dark" target="_blank" rel="noopener">
                                            Catatan Rilis
                                        </a>
                                    @endif
                                    @auth
                                        @if(auth()->user()->isSuperAdmin())
                                            <a href="{{ route('super-admin.settings.system.index') }}" class="btn btn-sm btn-dark">
                                                Kelola Update
                                            </a>
                                        @endif
                                    @endauth
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                @yield('content')
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    @stack('scripts')
    @auth
        @if(config('push.vapid.public_key') !== '')
            <script>
                (function () {
                    var vapidPublicKey = @json(config('push.vapid.public_key'));
                    var subscribeUrl = @json(route('push.subscribe'));
                    var unsubscribeUrl = @json(route('push.unsubscribe'));
                    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    var button = document.getElementById('push-subscribe-btn');

                    if (!button || !vapidPublicKey || !('serviceWorker' in navigator) || !('PushManager' in window)) {
                        return;
                    }

                    window.addEventListener('load', function () {
                        navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(function () {});
                    });

                    function urlBase64ToUint8Array(base64String) {
                        var padding = '='.repeat((4 - base64String.length % 4) % 4);
                        var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
                        var rawData = window.atob(base64);
                        var output = new Uint8Array(rawData.length);

                        for (var index = 0; index < rawData.length; index += 1) {
                            output[index] = rawData.charCodeAt(index);
                        }

                        return output;
                    }

                    function serializeSubscription(subscription) {
                        return {
                            endpoint: subscription.endpoint,
                            keys: {
                                p256dh: window.btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('p256dh')))),
                                auth: window.btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('auth')))),
                            },
                        };
                    }

                    function apiRequest(method, url, payload) {
                        return window.fetch(url, {
                            method: method,
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: payload ? JSON.stringify(payload) : undefined,
                        }).then(function (response) {
                            return response.json();
                        });
                    }

                    function setButtonState(subscribed) {
                        if (subscribed) {
                            button.dataset.subscribed = '1';
                            button.innerHTML = '<i class="fas fa-bell text-success"></i>';
                            button.title = 'Notifikasi aktif. Klik untuk menonaktifkan.';

                            return;
                        }

                        button.dataset.subscribed = '0';
                        button.innerHTML = '<i class="far fa-bell"></i>';
                        button.title = 'Aktifkan notifikasi push';
                    }

                    navigator.serviceWorker.ready.then(function (registration) {
                        return registration.pushManager.getSubscription();
                    }).then(function (subscription) {
                        setButtonState(!!subscription);
                    }).catch(function () {});

                    button.addEventListener('click', function () {
                        navigator.serviceWorker.ready.then(function (registration) {
                            return registration.pushManager.getSubscription().then(function (subscription) {
                                if (subscription) {
                                    var endpoint = subscription.endpoint;

                                    return subscription.unsubscribe().then(function () {
                                        return apiRequest('DELETE', unsubscribeUrl, { endpoint: endpoint });
                                    }).then(function () {
                                        setButtonState(false);
                                    });
                                }

                                return Notification.requestPermission().then(function (permission) {
                                    if (permission !== 'granted') {
                                        return;
                                    }

                                    return registration.pushManager.subscribe({
                                        userVisibleOnly: true,
                                        applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
                                    }).then(function (createdSubscription) {
                                        return apiRequest('POST', subscribeUrl, serializeSubscription(createdSubscription));
                                    }).then(function () {
                                        setButtonState(true);
                                    });
                                });
                            });
                        }).catch(function () {});
                    });
                })();
            </script>
        @endif
    @endauth
</body>
</html>
