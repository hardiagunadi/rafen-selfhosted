@extends('layouts.admin')

@section('title', 'Tambah ODP')

@section('content')
    @include('partials.customer-management-shell-styles')

    <div class="cm-page">
        <div class="cm-header">
            <div class="cm-header-main">
                <div class="cm-header-icon" style="background:linear-gradient(135deg,#0f766e,#14b8a6);">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <div class="cm-header-copy">
                    <p class="cm-kicker">ODP Management</p>
                    <h1 class="cm-title">Tambah ODP</h1>
                    <p class="cm-subtitle">Flow pembuatan ODP dipisah dari list agar konsisten dengan master data tenant SaaS.</p>
                </div>
            </div>
            <div class="cm-header-actions">
                <a href="{{ route('super-admin.odps.index') }}" class="cm-btn cm-btn-muted">
                    <i class="fas fa-arrow-left"></i>
                    Kembali ke List
                </a>
            </div>
        </div>

        @if($errors->any())
            <div class="cm-alert cm-alert-danger">
                <i class="fas fa-exclamation-circle mt-1"></i>
                <div>
                    <strong>Data ODP belum valid.</strong>
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
                        <h2 class="cm-main-card-title">Form ODP</h2>
                        <p class="cm-main-card-subtitle">Isi kode, area, kapasitas, dan koordinat ODP.</p>
                    </div>
                </div>
                <div class="cm-main-card-body">
                    <form action="{{ route('super-admin.odps.store') }}" method="POST">
                        @csrf
                        @include('odps._form')

                        <div class="cm-form-toolbar">
                            <div class="cm-form-meta">Tips: gunakan tombol <strong>Auto</strong> untuk membuat kode berdasarkan area.</div>
                            <div class="cm-form-actions">
                                <a href="{{ route('super-admin.odps.index') }}" class="cm-btn cm-btn-muted">Batal</a>
                                <button type="submit" class="cm-btn cm-btn-primary">
                                    <i class="fas fa-save"></i>
                                    Simpan ODP
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
                            <p class="cm-side-card-subtitle">Urutan kerja yang paling sering dipakai operator.</p>
                        </div>
                    </div>
                    <div class="cm-side-card-body">
                        <div class="cm-summary-list">
                            <div class="cm-summary-item">
                                <div class="cm-summary-label">1</div>
                                <div class="cm-summary-value">Isi nama dan area ODP.</div>
                            </div>
                            <div class="cm-summary-item">
                                <div class="cm-summary-label">2</div>
                                <div class="cm-summary-value">Generate atau ketik manual kode ODP.</div>
                            </div>
                            <div class="cm-summary-item">
                                <div class="cm-summary-label">3</div>
                                <div class="cm-summary-value">Set kapasitas port dan status operasional.</div>
                            </div>
                            <div class="cm-summary-item">
                                <div class="cm-summary-label">4</div>
                                <div class="cm-summary-value">Isi koordinat jika ODP perlu dipakai di peta pelanggan.</div>
                            </div>
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
    document.querySelector('[data-generate-odp-code]')?.addEventListener('click', async function () {
        const response = await fetch(@json(route('super-admin.odps.generate-code')) + '?area_name=' + encodeURIComponent(document.getElementById('odp_area').value || ''));
        if (!response.ok) {
            return;
        }

        const payload = await response.json();
        document.getElementById('odp_code').value = payload.code || '';
    });
});
</script>
@endpush
