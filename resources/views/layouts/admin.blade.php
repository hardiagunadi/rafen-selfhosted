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
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/svg+xml" href="{{ asset('branding/rafen-favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('branding/favicon-32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ route('manifest.admin.icon', ['size' => 180]) }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-bs4@1.13.8/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-responsive-bs4@2.5.0/css/responsive.bootstrap4.min.css">
    <style>
        :root {
            --app-bg: #f4f7fb;
            --app-border: #d7e1ee;
            --app-surface: #ffffff;
            --app-shadow: 0 10px 22px rgba(15, 23, 42, 0.07);
            --app-shadow-soft: 0 6px 14px rgba(15, 23, 42, 0.05);
            --app-text: #0f172a;
            --app-text-soft: #5b6b83;
        }

        body {
            background:
                radial-gradient(circle at 8% -8%, rgba(14, 116, 144, 0.1), transparent 30%),
                radial-gradient(circle at 100% 0%, rgba(37, 99, 235, 0.07), transparent 24%),
                var(--app-bg);
            overflow-x: hidden;
        }

        .content-wrapper,
        .content-wrapper > .content,
        .content-wrapper > .content > .container,
        .content-wrapper > .content > .container-fluid {
            background: transparent;
        }

        .content-wrapper > .content {
            padding-top: 0.8rem;
            padding-bottom: 1rem;
        }

        .content-wrapper .card {
            border: 1px solid var(--app-border);
            border-radius: 16px;
            box-shadow: var(--app-shadow-soft);
            background: var(--app-surface);
            overflow: hidden;
        }

        .content-wrapper .card-header {
            border-bottom: 1px solid #e4ebf5;
            background: linear-gradient(180deg, #fbfdff 0%, #f5f9ff 100%);
            padding: 0.82rem 1rem;
        }

        .content-wrapper .card-title {
            color: var(--app-text);
            font-weight: 700;
        }

        .content-wrapper .card-body,
        .content-wrapper .card-footer {
            padding: 1rem;
        }

        .content-wrapper .card-footer {
            border-top: 1px solid #e4ebf5;
            background: #f8fbff;
        }

        .content-wrapper .form-control,
        .content-wrapper .custom-select,
        .content-wrapper .custom-file-label,
        .content-wrapper .input-group-text {
            border-radius: 8px;
            border-color: #d4deea;
        }

        .content-wrapper .form-control:focus,
        .content-wrapper .custom-select:focus {
            border-color: #8fb5df;
            box-shadow: 0 0 0 0.2rem rgba(19, 103, 164, 0.15);
        }

        .content-wrapper .table thead th {
            border-top: 0;
            border-bottom: 1px solid #dfe8f4;
            background: #f8fbff;
            color: #64748b;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .content-wrapper .btn-primary {
            background-color: #1367a4;
            border-color: #1367a4;
        }

        .content-wrapper .btn-primary:hover,
        .content-wrapper .btn-primary:focus {
            background-color: #0f5689;
            border-color: #0f5689;
        }

        .main-header.navbar {
            border-bottom: 1px solid rgba(9, 39, 68, 0.22);
            background: linear-gradient(105deg, rgba(10, 62, 104, 0.98), rgba(15, 107, 149, 0.95) 45%, rgba(12, 138, 143, 0.94)) !important;
            box-shadow: 0 8px 20px rgba(9, 39, 68, 0.22);
        }

        .main-header .navbar-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            color: #f8fbff !important;
            font-weight: 700;
        }

        .main-header .brand-logo-mark {
            width: 2rem;
            height: 2rem;
            border-radius: 0.55rem;
            box-shadow: 0 8px 16px rgba(8, 23, 39, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.24);
            object-fit: contain;
            background: rgba(255, 255, 255, 0.92);
            padding: 0.18rem;
        }

        .main-header .navbar-nav > .nav-link,
        .main-header .navbar-nav > .nav-item > .nav-link,
        .main-header .navbar-nav > .nav-item > form .btn,
        .main-header .navbar-nav > .nav-item > button.btn {
            color: #e7f5ff;
            border-radius: 9px;
            transition: background-color 0.16s ease, color 0.16s ease, transform 0.16s ease;
            white-space: nowrap;
        }

        .main-header .navbar-nav > .nav-link:hover,
        .main-header .navbar-nav > .nav-item > .nav-link:hover,
        .main-header .navbar-nav > .nav-item > .nav-link:focus,
        .main-header .navbar-nav > .nav-item > form .btn:hover,
        .main-header .navbar-nav > .nav-item > button.btn:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.16);
            transform: translateY(-1px);
        }

        .main-header .navbar-nav > .nav-link.active,
        .main-header .navbar-nav > .nav-item > .nav-link.active {
            background: rgba(255, 255, 255, 0.18);
            color: #ffffff;
            font-weight: 700;
        }

        .main-header .navbar-nav > .nav-item > form .btn-outline-secondary,
        .main-header .navbar-nav > .nav-item > button.btn-outline-secondary {
            color: #e7f5ff;
            border-color: rgba(255, 255, 255, 0.35);
            background: transparent;
        }

        .main-sidebar.sidebar-modern {
            position: relative;
            border-right: 1px solid rgba(148, 163, 184, 0.24);
            background: linear-gradient(180deg, #081527 0%, #0d2035 48%, #102a44 100%);
            overflow: hidden;
        }

        .main-sidebar.sidebar-modern::before {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                radial-gradient(circle at 18% 6%, rgba(56, 189, 248, 0.24), transparent 28%),
                radial-gradient(circle at 85% 0%, rgba(14, 165, 233, 0.18), transparent 26%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.03) 0%, rgba(255, 255, 255, 0) 35%);
        }

        .sidebar-modern .brand-link,
        .sidebar-modern .sidebar {
            position: relative;
            z-index: 1;
        }

        .sidebar-modern .brand-link {
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
            background: linear-gradient(110deg, rgba(15, 118, 168, 0.35), rgba(14, 165, 233, 0.14));
            padding: 0.9rem 0.9rem 0.85rem;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 0.55rem;
            overflow: hidden;
        }

        .sidebar-modern .brand-text {
            color: #f8fbff;
            font-weight: 600;
            font-size: 1.05rem;
            letter-spacing: 0.015em;
            text-shadow: 0 2px 10px rgba(15, 23, 42, 0.35);
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-modern .brand-logo-mark {
            width: 1.95rem;
            height: 1.95rem;
            border-radius: 0.5rem;
            box-shadow: 0 8px 16px rgba(8, 23, 39, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.24);
            object-fit: contain;
            background: rgba(255, 255, 255, 0.92);
            padding: 0.18rem;
        }

        .sidebar-modern .sidebar {
            scrollbar-width: thin;
            scrollbar-color: rgba(125, 166, 210, 0.5) transparent;
            height: calc(100% - 57px);
            overflow-y: auto;
            overflow-x: hidden;
            padding-bottom: 1rem;
        }

        .sidebar-modern .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar-modern .sidebar::-webkit-scrollbar-thumb {
            border-radius: 999px;
            background: rgba(125, 166, 210, 0.42);
        }

        .sidebar-modern .nav-header {
            color: #9eb3cc;
            font-size: 0.69rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding-top: 0.9rem;
            padding-bottom: 0.45rem;
        }

        .sidebar-modern .nav-sidebar .nav-link {
            margin: 0.17rem 0.4rem;
            border-radius: 11px;
            border: 1px solid transparent;
            color: #d7e5f7;
            padding: 0.58rem 0.72rem;
            transition: all 170ms ease;
        }

        .sidebar-modern .nav-sidebar > .nav-item > .nav-link {
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.015);
        }

        .sidebar-modern .nav-sidebar .nav-link p {
            margin: 0;
            font-weight: 500;
            letter-spacing: 0.01em;
        }

        .sidebar-modern .nav-sidebar .nav-link .nav-icon {
            width: 1.84rem;
            height: 1.84rem;
            margin-right: 0.5rem;
            border-radius: 9px;
            line-height: 1.84rem;
            text-align: center;
            background: rgba(148, 163, 184, 0.16);
            color: #97b4d2;
            font-size: 0.87rem;
            transition: all 170ms ease;
        }

        .sidebar-modern .nav-sidebar .nav-link .right {
            color: #9ab4d1;
        }

        .sidebar-modern .nav-sidebar .nav-link:hover,
        .sidebar-modern .nav-sidebar .nav-link:focus {
            background: rgba(37, 99, 235, 0.18);
            border-color: rgba(125, 166, 210, 0.3);
            color: #f5fbff;
            transform: translateX(2px);
        }

        .sidebar-modern .nav-sidebar .nav-link:hover .nav-icon,
        .sidebar-modern .nav-sidebar .nav-link:focus .nav-icon {
            background: rgba(191, 219, 254, 0.23);
            color: #eaf5ff;
        }

        .sidebar-modern .nav-sidebar .menu-open > .nav-link,
        .sidebar-modern .nav-sidebar .nav-link.active {
            background: linear-gradient(135deg, #0f6aa7 0%, #17a2b8 100%);
            border-color: rgba(255, 255, 255, 0.26);
            box-shadow: 0 10px 22px rgba(9, 91, 138, 0.36);
            color: #f8fdff;
        }

        .sidebar-modern .nav-sidebar .menu-open > .nav-link .nav-icon,
        .sidebar-modern .nav-sidebar .nav-link.active .nav-icon {
            background: rgba(255, 255, 255, 0.22);
            color: #fff;
        }

        .sidebar-modern .nav-treeview {
            margin: 0.22rem 0.38rem 0.42rem 1.35rem;
            padding-left: 0.46rem;
            border-left: 1px dashed rgba(148, 163, 184, 0.38);
        }

        .sidebar-modern .nav-treeview > .nav-item > .nav-link {
            margin: 0.14rem 0;
            padding: 0.48rem 0.6rem;
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.24);
        }

        .sidebar-modern .nav-treeview > .nav-item > .nav-link:hover,
        .sidebar-modern .nav-treeview > .nav-item > .nav-link:focus {
            background: rgba(37, 99, 235, 0.26);
            transform: none;
        }

        .sidebar-modern .nav-treeview > .nav-item > .nav-link.active {
            background: linear-gradient(135deg, #2c7fbf 0%, #0ea5e9 100%);
        }

        .sidebar-modern .nav-treeview > .nav-item > .nav-link .nav-icon {
            width: 1.6rem;
            height: 1.6rem;
            line-height: 1.6rem;
            border-radius: 7px;
            font-size: 0.72rem;
        }

        @media (max-width: 991.98px) {
            .sidebar-modern .brand-text {
                font-size: 1rem;
            }

            .sidebar-modern .nav-sidebar .nav-link {
                margin-left: 0.35rem;
                margin-right: 0.35rem;
            }
        }

        .alert {
            border-radius: 14px;
            box-shadow: var(--app-shadow-soft);
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <nav class="main-header navbar navbar-expand navbar-light navbar-white">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
            </ul>
            <ul class="navbar-nav ml-auto align-items-center">
                @if(config('push.vapid.public_key') !== '')
                    <li class="nav-item">
                        <button id="push-subscribe-btn" type="button" class="btn btn-outline-secondary btn-sm ml-2" data-subscribed="0" title="Aktifkan notifikasi push">
                            <i class="far fa-bell"></i>
                        </button>
                    </li>
                @endif
                @auth
                    <li class="nav-item">
                        <form action="{{ route('logout') }}" method="POST" class="mb-0 ml-2">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary btn-sm">Logout</button>
                        </form>
                    </li>
                @endauth
            </ul>
        </nav>

        <aside class="main-sidebar sidebar-dark-primary elevation-4 sidebar-modern">
            <a href="{{ auth()->check() && ! auth()->user()->isSuperAdmin() ? route('shifts.my') : route('super-admin.dashboard') }}" class="brand-link">
                <img src="{{ asset('branding/rafen-mark.svg') }}" alt="RAFEN" class="brand-logo-mark">
                <span class="brand-text font-weight-light">{{ $systemSettings->appName('Rafen Self-Hosted') }}</span>
            </a>
            <div class="sidebar">
                <nav class="mt-2 pb-3">
                    @php
                        $sessionRoutes = ['super-admin.sessions.pppoe*', 'super-admin.sessions.hotspot*'];
                        $customerRoutes = ['super-admin.settings.hotspot-users.*', 'super-admin.settings.ppp-users.*', 'super-admin.vouchers.*', 'super-admin.customer-map.*', 'super-admin.odps.*'];
                        $connectionRoutes = ['super-admin.settings.mikrotik.*', 'super-admin.settings.olt.*', 'super-admin.settings.cpe.*'];
                        $packageRoutes = ['super-admin.settings.bandwidth-profiles.*', 'super-admin.settings.profile-groups.*', 'super-admin.settings.hotspot-profiles.*', 'super-admin.settings.ppp-profiles.*'];
                        $billingRoutes = ['super-admin.invoices.*', 'super-admin.payments.*', 'teknisi-setoran.*', 'super-admin.reports.*'];
                        $radiusRoutes = ['super-admin.settings.radius-accounts.*', 'super-admin.settings.freeradius.*'];
                        $serviceRoutes = ['super-admin.settings.genieacs.*', 'super-admin.settings.wireguard.*', 'super-admin.settings.wa-gateway.*', 'super-admin.wa-blast.*', 'super-admin.wa-chat.*', 'super-admin.wa-keyword-rules.*', 'super-admin.wa-tickets.*'];
                        $adminRoutes = ['super-admin.outages.*', 'shifts.*', 'super-admin.users.*'];
                        $systemRoutes = ['super-admin.settings.system.*', 'super-admin.settings.license*', 'super-admin.logs.*', 'super-admin.tools.*', 'super-admin.terminal.*', 'super-admin.help.*'];
                    @endphp
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                        <li class="nav-item"><a href="{{ auth()->check() && ! auth()->user()->isSuperAdmin() ? route('shifts.my') : route('super-admin.dashboard') }}" class="nav-link {{ request()->routeIs('super-admin.dashboard', 'shifts.my') ? 'active' : '' }}"><i class="nav-icon fas fa-tachometer-alt"></i><p>Dashboard</p></a></li>
                        @if(($systemFeatureFlags['radius'] ?? true) === true)
                            <li class="nav-item has-treeview {{ request()->routeIs(...$sessionRoutes) ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ request()->routeIs(...$sessionRoutes) ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-signal"></i>
                                    <p>Session User<i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item"><a href="{{ route('super-admin.sessions.pppoe') }}" class="nav-link {{ request()->routeIs('super-admin.sessions.pppoe*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>PPPoE</p></a></li>
                                    <li class="nav-item"><a href="{{ route('super-admin.sessions.hotspot') }}" class="nav-link {{ request()->routeIs('super-admin.sessions.hotspot*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Hotspot</p></a></li>
                                </ul>
                            </li>
                            <li class="nav-item has-treeview {{ request()->routeIs(...$customerRoutes) ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ request()->routeIs(...$customerRoutes) ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-users"></i>
                                    <p>List Pelanggan<i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item"><a href="{{ route('super-admin.settings.hotspot-users.index') }}" class="nav-link {{ request()->routeIs('super-admin.settings.hotspot-users.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>User Hotspot</p></a></li>
                                    <li class="nav-item"><a href="{{ route('super-admin.settings.ppp-users.index') }}" class="nav-link {{ request()->routeIs('super-admin.settings.ppp-users.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>User PPP</p></a></li>
                                    <li class="nav-item"><a href="{{ route('super-admin.vouchers.index') }}" class="nav-link {{ request()->routeIs('super-admin.vouchers.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Voucher</p></a></li>
                                    <li class="nav-item"><a href="{{ route('super-admin.customer-map.index') }}" class="nav-link {{ request()->routeIs('super-admin.customer-map.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Peta Pelanggan</p></a></li>
                                    <li class="nav-item"><a href="{{ route('super-admin.odps.index') }}" class="nav-link {{ request()->routeIs('super-admin.odps.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>ODP</p></a></li>
                                </ul>
                            </li>
                        @endif
                        <li class="nav-item has-treeview {{ request()->routeIs(...$connectionRoutes) ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->routeIs(...$connectionRoutes) ? 'active' : '' }}">
                                <i class="nav-icon fas fa-network-wired"></i>
                                <p>Koneksi & Perangkat<i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item"><a href="{{ route('super-admin.settings.mikrotik.index') }}" class="nav-link {{ request()->routeIs('super-admin.settings.mikrotik.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>MikroTik</p></a></li>
                                @if(($systemFeatureFlags['olt'] ?? true) === true)
                                    <li class="nav-item"><a href="{{ route('super-admin.settings.olt.index') }}" class="nav-link {{ request()->routeIs('super-admin.settings.olt.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>OLT</p></a></li>
                                @endif
                                @if(($systemFeatureFlags['genieacs'] ?? true) === true)
                                    <li class="nav-item"><a href="{{ route('super-admin.settings.cpe.index') }}" class="nav-link {{ request()->routeIs('super-admin.settings.cpe.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>CPE</p></a></li>
                                @endif
                            </ul>
                        </li>
                        @if(($systemFeatureFlags['radius'] ?? true) === true)
                            <li class="nav-item has-treeview {{ request()->routeIs(...$packageRoutes) ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ request()->routeIs(...$packageRoutes) ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-box-open"></i>
                                    <p>Profile & Paket<i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item"><a href="{{ route('super-admin.settings.bandwidth-profiles.index') }}" class="nav-link {{ request()->routeIs('super-admin.settings.bandwidth-profiles.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Bandwidth</p></a></li>
                                    <li class="nav-item"><a href="{{ route('super-admin.settings.profile-groups.index') }}" class="nav-link {{ request()->routeIs('super-admin.settings.profile-groups.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Profile Group</p></a></li>
                                    <li class="nav-item"><a href="{{ route('super-admin.settings.hotspot-profiles.index') }}" class="nav-link {{ request()->routeIs('super-admin.settings.hotspot-profiles.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Paket Hotspot</p></a></li>
                                    <li class="nav-item"><a href="{{ route('super-admin.settings.ppp-profiles.index') }}" class="nav-link {{ request()->routeIs('super-admin.settings.ppp-profiles.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Paket PPP</p></a></li>
                                </ul>
                            </li>
                            <li class="nav-item has-treeview {{ request()->routeIs(...$billingRoutes) ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ request()->routeIs(...$billingRoutes) ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-file-invoice-dollar"></i>
                                    <p>Billing<i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item"><a href="{{ route('super-admin.invoices.index') }}" class="nav-link {{ request()->routeIs('super-admin.invoices.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Invoice</p></a></li>
                                    <li class="nav-item"><a href="{{ route('super-admin.payments.index') }}" class="nav-link {{ request()->routeIs('super-admin.payments.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Pembayaran</p></a></li>
                                    <li class="nav-item"><a href="{{ route('teknisi-setoran.index') }}" class="nav-link {{ request()->routeIs('teknisi-setoran.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Setoran Teknisi</p></a></li>
                                    <li class="nav-item"><a href="{{ route('super-admin.reports.income') }}" class="nav-link {{ request()->routeIs('super-admin.reports.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Laporan</p></a></li>
                                </ul>
                            </li>
                            <li class="nav-item has-treeview {{ request()->routeIs(...$radiusRoutes) ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ request()->routeIs(...$radiusRoutes) ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-shield-alt"></i>
                                    <p>RADIUS<i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item"><a href="{{ route('super-admin.settings.radius-accounts.index') }}" class="nav-link {{ request()->routeIs('super-admin.settings.radius-accounts.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Akun RADIUS</p></a></li>
                                    <li class="nav-item"><a href="{{ route('super-admin.settings.freeradius.index') }}" class="nav-link {{ request()->routeIs('super-admin.settings.freeradius.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>FreeRADIUS</p></a></li>
                                </ul>
                            </li>
                        @endif
                        <li class="nav-item has-treeview {{ request()->routeIs(...$serviceRoutes) ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->routeIs(...$serviceRoutes) ? 'active' : '' }}">
                                <i class="nav-icon fas fa-cogs"></i>
                                <p>Layanan Tambahan<i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                @if(($systemFeatureFlags['genieacs'] ?? true) === true)
                                    <li class="nav-item"><a href="{{ route('super-admin.settings.genieacs.index') }}" class="nav-link {{ request()->routeIs('super-admin.settings.genieacs.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>GenieACS</p></a></li>
                                @endif
                                @if(($systemFeatureFlags['vpn'] ?? true) === true)
                                    <li class="nav-item"><a href="{{ route('super-admin.settings.wireguard.index') }}" class="nav-link {{ request()->routeIs('super-admin.settings.wireguard.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>WireGuard</p></a></li>
                                @endif
                                @if(($systemFeatureFlags['wa'] ?? true) === true)
                                    <li class="nav-item"><a href="{{ route('super-admin.settings.wa-gateway.index') }}" class="nav-link {{ request()->routeIs('super-admin.settings.wa-gateway.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>WhatsApp</p></a></li>
                                    <li class="nav-item"><a href="{{ route('super-admin.wa-blast.index') }}" class="nav-link {{ request()->routeIs('super-admin.wa-blast.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>WA Blast</p></a></li>
                                    <li class="nav-item"><a href="{{ route('super-admin.wa-chat.index') }}" class="nav-link {{ request()->routeIs('super-admin.wa-chat.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>WA Chat</p></a></li>
                                    <li class="nav-item"><a href="{{ route('super-admin.wa-keyword-rules.index') }}" class="nav-link {{ request()->routeIs('super-admin.wa-keyword-rules.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Keyword WA</p></a></li>
                                    <li class="nav-item"><a href="{{ route('super-admin.wa-tickets.index') }}" class="nav-link {{ request()->routeIs('super-admin.wa-tickets.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Tiket WA</p></a></li>
                                @endif
                            </ul>
                        </li>
                        <li class="nav-item has-treeview {{ request()->routeIs(...$adminRoutes) ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->routeIs(...$adminRoutes) ? 'active' : '' }}">
                                <i class="nav-icon fas fa-user-cog"></i>
                                <p>Administrasi<i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item"><a href="{{ route('super-admin.outages.index') }}" class="nav-link {{ request()->routeIs('super-admin.outages.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Gangguan</p></a></li>
                                <li class="nav-item"><a href="{{ route('shifts.my') }}" class="nav-link {{ request()->routeIs('shifts.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Shift</p></a></li>
                                <li class="nav-item"><a href="{{ route('super-admin.users.index') }}" class="nav-link {{ request()->routeIs('super-admin.users.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Pengguna</p></a></li>
                            </ul>
                        </li>
                        <li class="nav-item has-treeview {{ request()->routeIs(...$systemRoutes) ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->routeIs(...$systemRoutes) ? 'active' : '' }}">
                                <i class="nav-icon fas fa-tools"></i>
                                <p>Pengaturan Sistem<i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item"><a href="{{ route('super-admin.settings.system.index') }}" class="nav-link {{ request()->routeIs('super-admin.settings.system.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Profil Sistem</p></a></li>
                                <li class="nav-item"><a href="{{ route('super-admin.settings.license') }}" class="nav-link {{ request()->routeIs('super-admin.settings.license*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Lisensi Sistem</p></a></li>
                                <li class="nav-item"><a href="{{ route('super-admin.logs.activity') }}" class="nav-link {{ request()->routeIs('super-admin.logs.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Log Aktivitas</p></a></li>
                                <li class="nav-item"><a href="{{ route('super-admin.tools.backup') }}" class="nav-link {{ request()->routeIs('super-admin.tools.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>System Tools</p></a></li>
                                <li class="nav-item"><a href="{{ route('super-admin.terminal.index') }}" class="nav-link {{ request()->routeIs('super-admin.terminal.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Terminal</p></a></li>
                                <li class="nav-item"><a href="{{ route('super-admin.help.index') }}" class="nav-link {{ request()->routeIs('super-admin.help.*') ? 'active' : '' }}"><i class="far fa-circle nav-icon"></i><p>Bantuan</p></a></li>
                            </ul>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

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
                <div class="container-fluid">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net@1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net-bs4@1.13.8/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net-responsive@2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net-responsive-bs4@2.5.0/js/responsive.bootstrap4.min.js"></script>
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
