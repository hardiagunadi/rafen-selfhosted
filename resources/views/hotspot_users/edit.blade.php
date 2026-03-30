@extends('layouts.admin')

@section('title', 'Edit User Hotspot')

@section('content')
    @include('partials.customer-management-shell-styles')

    @php($accountBadgeClass = match ($hotspotUser->status_akun) {
        'enable' => 'cm-badge-success',
        'disable' => 'cm-badge-danger',
        'isolir' => 'cm-badge-warning',
        default => 'cm-badge-neutral',
    })
    @php($paymentBadgeClass = ($hotspotUser->status_bayar ?? 'belum_bayar') === 'sudah_bayar' ? 'cm-badge-success' : 'cm-badge-warning')

    <div class="cm-page">
        <div class="cm-header">
            <div class="cm-header-main">
                <div class="cm-header-icon" style="background:linear-gradient(135deg,#b45309,#f59e0b);">
                    <i class="fas fa-wifi"></i>
                </div>
                <div class="cm-header-copy">
                    <p class="cm-kicker">Hotspot Management</p>
                    <h1 class="cm-title">Edit User Hotspot</h1>
                    <p class="cm-subtitle">{{ $hotspotUser->customer_name ?: 'Pelanggan tanpa nama' }} · {{ $hotspotUser->customer_id ?: 'ID belum diisi' }}</p>
                </div>
            </div>
            <div class="cm-header-actions">
                <a href="{{ route('super-admin.settings.hotspot-users.index') }}" class="cm-btn cm-btn-muted">
                    <i class="fas fa-arrow-left"></i>
                    Kembali ke List
                </a>
            </div>
        </div>

        @if($errors->any())
            <div class="cm-alert cm-alert-danger">
                <i class="fas fa-exclamation-circle mt-1"></i>
                <div>
                    <strong>Data hotspot belum valid.</strong>
                    <ul class="mb-0 pl-3 mt-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="cm-layout">
            <div class="cm-main-card">
                <div class="cm-main-card-header">
                    <div>
                        <h2 class="cm-main-card-title">Form User Hotspot</h2>
                        <p class="cm-main-card-subtitle">Konten field tetap memakai data model self-hosted, tetapi tata letak dan hierarkinya disamakan ke pola tenant SaaS.</p>
                    </div>
                </div>
                <div class="cm-main-card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <form action="{{ route('super-admin.settings.hotspot-users.update', $hotspotUser) }}" method="POST">
                        @csrf
                        @method('PUT')
                        @include('hotspot_users._form')

                        <div class="cm-form-toolbar">
                            <div class="cm-form-meta">
                                Profil saat ini: <strong>{{ $hotspotUser->hotspotProfile?->name ?? 'Belum dipilih' }}</strong>
                            </div>
                            <div class="cm-form-actions">
                                <a href="{{ route('super-admin.settings.hotspot-users.index') }}" class="cm-btn cm-btn-muted">Batal</a>
                                <button type="submit" class="cm-btn cm-btn-primary">
                                    <i class="fas fa-save"></i>
                                    Simpan Perubahan
                                </button>
                                <button type="submit" form="delete-hotspot-user-form" class="cm-btn cm-btn-danger" onclick="return confirm('Hapus user hotspot ini?')">
                                    <i class="fas fa-trash"></i>
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="cm-side-stack">
                <div class="cm-side-card">
                    <div class="cm-side-card-header">
                        <div>
                            <h3 class="cm-side-card-title">Ringkasan User</h3>
                            <p class="cm-side-card-subtitle">Status layanan yang paling sering dicek operator.</p>
                        </div>
                    </div>
                    <div class="cm-side-card-body">
                        <div class="cm-metrics">
                            <div class="cm-metric">
                                <p class="cm-metric-label">Username</p>
                                <p class="cm-metric-value">{{ $hotspotUser->username ?: '-' }}</p>
                            </div>
                            <div class="cm-metric">
                                <p class="cm-metric-label">Jatuh Tempo</p>
                                <p class="cm-metric-value">{{ $hotspotUser->jatuh_tempo?->format('Y-m-d') ?? '-' }}</p>
                            </div>
                        </div>

                        <div class="cm-summary-list mt-3">
                            <div class="cm-summary-item">
                                <div class="cm-summary-label">Status Akun</div>
                                <div class="cm-summary-value">
                                    <span class="cm-badge {{ $accountBadgeClass }}">{{ strtoupper((string) $hotspotUser->status_akun) }}</span>
                                </div>
                            </div>
                            <div class="cm-summary-item">
                                <div class="cm-summary-label">Status Bayar</div>
                                <div class="cm-summary-value">
                                    <span class="cm-badge {{ $paymentBadgeClass }}">{{ strtoupper((string) ($hotspotUser->status_bayar ?? 'belum_bayar')) }}</span>
                                </div>
                            </div>
                            <div class="cm-summary-item">
                                <div class="cm-summary-label">Profil Hotspot</div>
                                <div class="cm-summary-value">{{ $hotspotUser->hotspotProfile?->name ?? '-' }}</div>
                            </div>
                            <div class="cm-summary-item">
                                <div class="cm-summary-label">Metode Login</div>
                                <div class="cm-summary-value">{{ strtoupper(str_replace('_', ' ', (string) ($hotspotUser->metode_login ?? 'username_password'))) }}</div>
                            </div>
                            <div class="cm-summary-item">
                                <div class="cm-summary-label">Nomor HP</div>
                                <div class="cm-summary-value">{{ $hotspotUser->nomor_hp ?: '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cm-side-card">
                    <div class="cm-side-card-header">
                        <div>
                            <h3 class="cm-side-card-title">Aksi Operasional</h3>
                            <p class="cm-side-card-subtitle">Shortcut lanjutan yang biasanya dilakukan setelah edit.</p>
                        </div>
                    </div>
                    <div class="cm-side-card-body">
                        <div class="cm-action-list">
                            <form action="{{ route('super-admin.settings.hotspot-users.renew', $hotspotUser) }}" method="POST">
                                @csrf
                                <button type="submit" class="cm-btn cm-btn-primary" onclick="return confirm('Perpanjang layanan hotspot ini?')">
                                    <i class="fas fa-redo-alt"></i>
                                    Perpanjang Layanan
                                </button>
                            </form>
                            <a href="{{ route('super-admin.settings.hotspot-users.index') }}" class="cm-btn cm-btn-muted">
                                <i class="fas fa-list"></i>
                                Kembali ke Daftar User
                            </a>
                        </div>

                        <div class="cm-summary-list mt-3">
                            <div class="cm-summary-item">
                                <div class="cm-summary-label">Aksi Jatuh Tempo</div>
                                <div class="cm-summary-value">{{ strtoupper(str_replace('_', ' ', (string) ($hotspotUser->aksi_jatuh_tempo ?? 'isolir'))) }}</div>
                            </div>
                            <div class="cm-summary-item">
                                <div class="cm-summary-label">Registrasi</div>
                                <div class="cm-summary-value">{{ strtoupper((string) ($hotspotUser->status_registrasi ?? 'aktif')) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form id="delete-hotspot-user-form" action="{{ route('super-admin.settings.hotspot-users.destroy', $hotspotUser) }}" method="POST" class="d-none">
            @csrf
            @method('DELETE')
        </form>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelector('[data-generate-customer-id]')?.addEventListener('click', async function () {
        const response = await fetch(@json(route('super-admin.settings.hotspot-users.customer-id')));
        if (!response.ok) {
            return;
        }
        const payload = await response.json();
        document.getElementById('customer_id').value = payload.customer_id || '';
    });
});
</script>
@endpush
