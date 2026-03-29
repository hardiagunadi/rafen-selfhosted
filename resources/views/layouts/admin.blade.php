<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Rafen Self-Hosted'))</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
</head>
<body class="hold-transition layout-top-nav">
    <div class="wrapper">
        <nav class="main-header navbar navbar-expand-md navbar-light navbar-white">
            <div class="container">
                <a href="{{ route('super-admin.settings.license') }}" class="navbar-brand">
                    <span class="brand-text font-weight-bold">Rafen Self-Hosted</span>
                </a>
                <div class="navbar-nav ml-auto align-items-center">
                    <a href="{{ route('super-admin.settings.license') }}" class="nav-link">Lisensi Sistem</a>
                    <a href="{{ route('super-admin.settings.mikrotik.index') }}" class="nav-link">MikroTik</a>
                    @if(($systemFeatureFlags['radius'] ?? true) === true)
                        <a href="{{ route('super-admin.settings.radius-accounts.index') }}" class="nav-link">Radius Accounts</a>
                    @endif
                    @if(($systemFeatureFlags['radius'] ?? true) === true)
                        <a href="{{ route('super-admin.settings.freeradius.index') }}" class="nav-link">FreeRADIUS</a>
                    @endif
                    @if(($systemFeatureFlags['genieacs'] ?? true) === true)
                        <a href="{{ route('super-admin.settings.genieacs.index') }}" class="nav-link">GenieACS</a>
                    @endif
                    @if(($systemFeatureFlags['olt'] ?? true) === true)
                        <a href="{{ route('super-admin.settings.olt.index') }}" class="nav-link">OLT</a>
                    @endif
                    @if(($systemFeatureFlags['vpn'] ?? true) === true)
                        <a href="{{ route('super-admin.settings.wireguard.index') }}" class="nav-link">WireGuard</a>
                    @endif
                    @if(($systemFeatureFlags['wa'] ?? true) === true)
                        <a href="{{ route('super-admin.settings.wa-gateway.index') }}" class="nav-link">WhatsApp</a>
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
                @yield('content')
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    @stack('scripts')
</body>
</html>
