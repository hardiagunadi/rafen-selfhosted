<!DOCTYPE html>
<html lang="id">
<head>
    @php($systemSettings = \App\Models\SystemSetting::instance())
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f6b95">
    <title>@yield('title', $systemSettings->portalName())</title>
    <link rel="manifest" href="{{ route('portal.manifest') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/svg+xml" href="{{ asset('branding/rafen-favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('branding/favicon-32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ route('portal.icon', ['size' => 180]) }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <style>
        :root {
            --brand-start: #0a3e68;
            --brand-mid: #0f6b95;
            --brand-end: #0c8a8f;
        }

        body {
            background: #f4f7fb;
            min-height: 100vh;
            font-family: 'Source Sans Pro', sans-serif;
        }

        .portal-navbar {
            background: linear-gradient(105deg, var(--brand-start) 0%, var(--brand-mid) 55%, var(--brand-end) 100%);
            box-shadow: 0 2px 8px rgba(10, 62, 104, .35);
            padding: .7rem 1.25rem;
        }

        .portal-navbar .navbar-brand {
            color: #fff !important;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .portal-navbar .brand-icon {
            width: 36px;
            height: 36px;
            background: rgba(255, 255, 255, .18);
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .portal-navbar .nav-link,
        .portal-navbar .btn-link.nav-link {
            color: rgba(255, 255, 255, .8) !important;
            text-decoration: none;
            font-size: .875rem;
            padding: .35rem .65rem !important;
            border-radius: 6px;
            transition: background .15s, color .15s;
        }

        .portal-navbar .nav-link:hover,
        .portal-navbar .nav-link.active,
        .portal-navbar .btn-link.nav-link:hover {
            color: #fff !important;
            background: rgba(255, 255, 255, .18);
        }

        .portal-navbar .nav-link.active {
            font-weight: 600;
        }

        .portal-navbar .btn-logout {
            color: rgba(255, 255, 255, .75) !important;
            border: 1px solid rgba(255, 255, 255, .3);
            margin-left: .25rem;
        }

        .portal-main {
            max-width: 980px;
            margin: 0 auto;
            padding: 1.75rem 1rem 2rem;
        }

        .portal-main .card {
            border: none;
            box-shadow: 0 1px 6px rgba(0, 0, 0, .07);
            border-radius: 10px;
        }

        .portal-main .card-header {
            border-radius: 10px 10px 0 0 !important;
            font-weight: 600;
            font-size: .92rem;
            letter-spacing: .01em;
        }

        .portal-main .card-header.bg-primary,
        .portal-main .card-header.bg-dark {
            background: linear-gradient(90deg, var(--brand-start), var(--brand-mid)) !important;
            color: #fff !important;
            border-bottom: none;
        }

        .portal-main .btn-primary {
            background: linear-gradient(90deg, var(--brand-mid), var(--brand-end));
            border: none;
        }

        .portal-main .btn-primary:hover,
        .portal-main .btn-primary:focus {
            background: linear-gradient(90deg, var(--brand-start), var(--brand-mid));
            border: none;
        }

        footer {
            border-top: 1px solid #e2eaf4;
            margin-top: 2rem;
            color: #6b7a90;
            font-size: .82rem;
            padding: 1rem;
        }
    </style>
    @stack('css')
</head>
<body>
    @php($hasPortalSession = request()->cookies->has('portal_session'))

    @unless(request()->routeIs('portal.login'))
        <nav class="navbar navbar-expand navbar-dark portal-navbar">
            <div class="container">
                <a href="{{ route('portal.dashboard') }}" class="navbar-brand font-weight-bold">
                    <span class="brand-icon"><i class="fas fa-wifi"></i></span>
                    <span>{{ $systemSettings->portalName() }}</span>
                </a>
                @if($hasPortalSession)
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item">
                            <a href="{{ route('portal.dashboard') }}" class="nav-link {{ request()->routeIs('portal.dashboard') ? 'active' : '' }}">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('portal.invoices') }}" class="nav-link {{ request()->routeIs('portal.invoices') ? 'active' : '' }}">Tagihan</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('portal.account') }}" class="nav-link {{ request()->routeIs('portal.account') ? 'active' : '' }}">Akun</a>
                        </li>
                        @if(config('push.vapid.public_key') !== '')
                            <li class="nav-item">
                                <button id="portal-push-btn" type="button" class="btn btn-link nav-link" data-subscribed="0" style="display:none;">
                                    <i class="fas fa-bell"></i>
                                </button>
                            </li>
                        @endif
                        <li class="nav-item">
                            <form action="{{ route('portal.logout') }}" method="POST" class="mb-0">
                                @csrf
                                <button type="submit" class="btn btn-link nav-link btn-logout">Keluar</button>
                            </form>
                        </li>
                    </ul>
                @endif
            </div>
        </nav>
    @endunless

    <main class="portal-main">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @yield('content')
    </main>

    <footer class="text-center">
        &copy; {{ date('Y') }} {{ $systemSettings->portalName() }}
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    @stack('js')
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/sw-portal.js', { scope: '/portal/' }).catch(function () {});
            });
        }
    </script>
    @if($hasPortalSession && config('push.vapid.public_key') !== '')
        <script>
            (function () {
                var vapidPublicKey = @json(config('push.vapid.public_key'));
                var subscribeUrl = @json(route('portal.push.subscribe'));
                var unsubscribeUrl = @json(route('portal.push.unsubscribe'));
                var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                var button = document.getElementById('portal-push-btn');

                if (!button || !vapidPublicKey || !('serviceWorker' in navigator) || !('PushManager' in window)) {
                    return;
                }

                button.style.display = '';

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
                        button.innerHTML = '<i class="fas fa-bell text-warning"></i>';
                        button.title = 'Notifikasi aktif. Klik untuk menonaktifkan.';

                        return;
                    }

                    button.dataset.subscribed = '0';
                    button.innerHTML = '<i class="fas fa-bell"></i>';
                    button.title = 'Aktifkan notifikasi';
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
</body>
</html>
