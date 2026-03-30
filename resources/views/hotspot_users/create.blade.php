@extends('layouts.admin')

@section('title', 'Tambah User Hotspot')

@section('content')
    @include('partials.customer-management-shell-styles')

    <div class="cm-page">
        <div class="cm-header">
            <div class="cm-header-main">
                <div class="cm-header-icon" style="background:linear-gradient(135deg,#0369a1,#0ea5e9);">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="cm-header-copy">
                    <p class="cm-kicker">Hotspot Management</p>
                    <h1 class="cm-title">Tambah User Hotspot</h1>
                    <p class="cm-subtitle">Flow input dibuat lebih dekat ke tenant SaaS agar operator tidak perlu adaptasi ulang saat pindah ke self-hosted.</p>
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
                        <h2 class="cm-main-card-title">Form Registrasi Hotspot</h2>
                        <p class="cm-main-card-subtitle">Isi paket, identitas, dan kredensial hotspot dalam satu flow yang rapi.</p>
                    </div>
                </div>
                <div class="cm-main-card-body">
                    <form action="{{ route('super-admin.settings.hotspot-users.store') }}" method="POST">
                        @csrf
                        @include('hotspot_users._form')

                        <div class="cm-form-toolbar">
                            <div class="cm-form-meta">Gunakan tombol <strong>Auto</strong> untuk mengisi ID pelanggan dengan format yang konsisten.</div>
                            <div class="cm-form-actions">
                                <a href="{{ route('super-admin.settings.hotspot-users.index') }}" class="cm-btn cm-btn-muted">Batal</a>
                                <button type="submit" class="cm-btn cm-btn-primary">
                                    <i class="fas fa-save"></i>
                                    Simpan User
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
                            <h3 class="cm-side-card-title">Checklist Input</h3>
                            <p class="cm-side-card-subtitle">Urutan kerja yang disederhanakan dari tenant SaaS.</p>
                        </div>
                    </div>
                    <div class="cm-side-card-body">
                        <div class="cm-summary-list">
                            <div class="cm-summary-item">
                                <div class="cm-summary-label">1</div>
                                <div class="cm-summary-value">Isi nama pelanggan dan generate ID bila perlu.</div>
                            </div>
                            <div class="cm-summary-item">
                                <div class="cm-summary-label">2</div>
                                <div class="cm-summary-value">Pilih paket hotspot dan group profil.</div>
                            </div>
                            <div class="cm-summary-item">
                                <div class="cm-summary-label">3</div>
                                <div class="cm-summary-value">Tentukan metode login, username, dan password.</div>
                            </div>
                            <div class="cm-summary-item">
                                <div class="cm-summary-label">4</div>
                                <div class="cm-summary-value">Set jatuh tempo awal dan status pembayaran.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cm-side-card">
                    <div class="cm-side-card-header">
                        <div>
                            <h3 class="cm-side-card-title">Setelah Disimpan</h3>
                            <p class="cm-side-card-subtitle">Shortcut lanjutan yang biasa dipakai operator.</p>
                        </div>
                    </div>
                    <div class="cm-side-card-body">
                        <div class="cm-action-list">
                            <a href="{{ route('super-admin.settings.hotspot-users.index') }}" class="cm-btn cm-btn-muted">
                                <i class="fas fa-users"></i>
                                Lihat Semua User Hotspot
                            </a>
                            <span class="cm-btn cm-btn-muted" style="opacity:.75; cursor:default;">
                                <i class="fas fa-redo-alt"></i>
                                Perpanjang layanan tersedia di list dan halaman edit
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
