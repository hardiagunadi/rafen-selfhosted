@extends('layouts.admin')

@section('title', 'Dashboard Self-Hosted')

@section('content')
@php
    $license = $snapshot['license'] ?? null;
    $statusClass = match ($license?->status) {
        'active' => 'success',
        'grace' => 'warning',
        'restricted', 'invalid', 'missing' => 'danger',
        default => 'secondary',
    };
@endphp
<div class="container-fluid">
    <div class="row mb-3 align-items-center">
        <div class="col">
            <h4 class="mb-0"><i class="fas fa-tachometer-alt mr-2 text-primary"></i>Ringkasan Self-Hosted</h4>
            <small class="text-muted">Landing page operasional untuk modul yang sudah berhasil dipisahkan dari repo SaaS.</small>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-4 col-md-6">
            <div class="small-box bg-{{ $statusClass }}">
                <div class="inner">
                    <h3>{{ $snapshot['status_label'] ?? 'Lisensi' }}</h3>
                    <p>{{ $license?->instance_name ?: 'Lisensi belum diunggah' }}</p>
                </div>
                <div class="icon">
                    <i class="fas fa-certificate"></i>
                </div>
                <a href="{{ route('super-admin.settings.license') }}" class="small-box-footer">
                    Kelola Lisensi <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $stats['active_features'] }}</h3>
                    <p>Fitur aktif di dashboard self-hosted</p>
                </div>
                <div class="icon">
                    <i class="fas fa-puzzle-piece"></i>
                </div>
                <span class="small-box-footer">Radius, ACS, OLT, VPN, dan WhatsApp</span>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $stats['licensed_modules'] }}</h3>
                    <p>Modul lisensi terdeteksi</p>
                </div>
                <div class="icon">
                    <i class="fas fa-layer-group"></i>
                </div>
                <span class="small-box-footer">Menyesuaikan file lisensi sistem</span>
            </div>
        </div>
    </div>

    <div class="row">
        @foreach($modules as $module)
            <div class="col-xl-4 col-lg-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="{{ $module['icon'] }} text-{{ $module['tone'] }} mr-2"></i>
                            <strong>{{ $module['title'] }}</strong>
                        </div>
                        <span class="badge badge-{{ $module['enabled'] ? 'success' : 'secondary' }}">
                            {{ $module['enabled'] ? $module['badge'] : 'Nonaktif' }}
                        </span>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <div class="h5 mb-2">{{ $module['summary'] }}</div>
                        <p class="text-muted mb-3">{{ $module['description'] }}</p>
                        <div class="mt-auto d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                {{ $module['enabled'] ? 'Siap dikelola dari repo self-hosted.' : 'Terkunci oleh lisensi aktif.' }}
                            </small>
                            <a href="{{ $module['route'] }}" class="btn btn-outline-{{ $module['tone'] }} btn-sm">
                                {{ $module['route_label'] }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-list-check mr-1"></i> Status Porting Saat Ini
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <tbody>
                                <tr>
                                    <th class="w-50">Landing page self-hosted</th>
                                    <td>Sudah memakai dashboard dedicated</td>
                                </tr>
                                <tr>
                                    <th>Bootstrap lisensi</th>
                                    <td>{{ $snapshot['status_label'] ?? 'Belum tersedia' }}</td>
                                </tr>
                                <tr>
                                    <th>Resource inti</th>
                                    <td>{{ $stats['mikrotik_connections'] + $stats['radius_accounts'] + $stats['cpe_devices'] + $stats['olt_connections'] + $stats['wireguard_peers'] + $stats['wa_devices'] }} item terdata</td>
                                </tr>
                                <tr>
                                    <th>Modul self-hosted aktif</th>
                                    <td>{{ implode(', ', collect($featureFlags)->filter()->keys()->map(fn ($flag) => strtoupper($flag))->all()) ?: '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-bolt mr-1"></i> Aksi Cepat
                </div>
                <div class="card-body d-flex flex-column" style="gap: .75rem;">
                    <a href="{{ route('super-admin.settings.license') }}" class="btn btn-outline-primary text-left">
                        <i class="fas fa-certificate mr-2"></i> Verifikasi lisensi dan modul
                    </a>
                    <a href="{{ route('super-admin.settings.genieacs.index') }}" class="btn btn-outline-success text-left">
                        <i class="fas fa-satellite-dish mr-2"></i> Cek koneksi GenieACS
                    </a>
                    <a href="{{ route('super-admin.settings.freeradius.index') }}" class="btn btn-outline-secondary text-left">
                        <i class="fas fa-sync mr-2"></i> Sinkronkan FreeRADIUS
                    </a>
                    <a href="{{ route('super-admin.settings.wa-gateway.index') }}" class="btn btn-outline-success text-left">
                        <i class="fab fa-whatsapp mr-2"></i> Validasi device WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
