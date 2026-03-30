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
    <link rel="apple-touch-icon" sizes="180x180" href="{{ route('portal.icon', ['size' => 180]) }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <style>
        body {
            background: #f4f7fb;
            min-height: 100vh;
        }

        .portal-navbar {
            background: linear-gradient(105deg, #0a3e68 0%, #0f6b95 55%, #0c8a8f 100%);
            box-shadow: 0 2px 8px rgba(10, 62, 104, .35);
        }

        .portal-navbar .nav-link,
        .portal-navbar .navbar-brand {
            color: rgba(255, 255, 255, .9) !important;
        }

        .portal-navbar .nav-link.active {
            font-weight: 700;
        }

        .portal-main {
            max-width: 980px;
            margin: 0 auto;
            padding: 1.5rem 1rem 2rem;
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
                    <i class="fas fa-wifi mr-2"></i>{{ $systemSettings->portalName() }}
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
                                <button type="submit" class="btn btn-link nav-link">Keluar</button>
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
