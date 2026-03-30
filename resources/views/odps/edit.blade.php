@extends('layouts.admin')

@section('title', 'Edit ODP')

@section('content')
    @include('partials.customer-management-shell-styles')

    @php($statusBadgeClass = match ($odp->status) {
        'active' => 'cm-badge-success',
        'maintenance' => 'cm-badge-warning',
        default => 'cm-badge-neutral',
    })
    @php($usedPorts = (int) ($odp->ppp_users_count ?? 0))
    @php($capacityPorts = max(0, (int) $odp->capacity_ports))

    <div class="cm-page">
        <div class="cm-header">
            <div class="cm-header-main">
                <div class="cm-header-icon" style="background:linear-gradient(135deg,#0f766e,#14b8a6);">
                    <i class="fas fa-network-wired"></i>
                </div>
                <div class="cm-header-copy">
                    <p class="cm-kicker">ODP Management</p>
                    <h1 class="cm-title">Edit ODP</h1>
                    <p class="cm-subtitle">{{ $odp->name }} · {{ $odp->code }}</p>
                </div>
            </div>
            <div class="cm-header-actions">
                <a href="{{ route('super-admin.odps.index') }}" class="cm-btn cm-btn-muted">
                    <i class="fas fa-arrow-left"></i>
                    Kembali ke List
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

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
                        <p class="cm-main-card-subtitle">Layout edit dibuat konsisten dengan master data lain di self-hosted.</p>
                    </div>
                </div>
                <div class="cm-main-card-body">
                    <form action="{{ route('super-admin.odps.update', $odp) }}" method="POST">
                        @csrf
                        @method('PUT')
                        @include('odps._form')

                        <div class="cm-form-toolbar">
                            <div class="cm-form-meta">Port terpakai saat ini: <strong>{{ $usedPorts }}</strong> dari <strong>{{ $capacityPorts }}</strong>.</div>
                            <div class="cm-form-actions">
                                <a href="{{ route('super-admin.odps.index') }}" class="cm-btn cm-btn-muted">Batal</a>
                                <button type="submit" class="cm-btn cm-btn-primary">
                                    <i class="fas fa-save"></i>
                                    Simpan Perubahan
                                </button>
                                <button type="submit" form="delete-odp-form" class="cm-btn cm-btn-danger" onclick="return confirm('Hapus data ODP ini?')" {{ $usedPorts > 0 ? 'disabled' : '' }}>
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
                            <h3 class="cm-side-card-title">Ringkasan ODP</h3>
                            <p class="cm-side-card-subtitle">Snapshot singkat untuk memudahkan operator lapangan.</p>
                        </div>
                    </div>
                    <div class="cm-side-card-body">
                        <div class="cm-metrics">
                            <div class="cm-metric">
                                <p class="cm-metric-label">Port Pakai</p>
                                <p class="cm-metric-value">{{ $usedPorts }}</p>
                            </div>
                            <div class="cm-metric">
                                <p class="cm-metric-label">Sisa Port</p>
                                <p class="cm-metric-value">{{ max(0, $capacityPorts - $usedPorts) }}</p>
                            </div>
                        </div>

                        <div class="cm-summary-list mt-3">
                            <div class="cm-summary-item">
                                <div class="cm-summary-label">Status</div>
                                <div class="cm-summary-value"><span class="cm-badge {{ $statusBadgeClass }}">{{ strtoupper($odp->status) }}</span></div>
                            </div>
                            <div class="cm-summary-item">
                                <div class="cm-summary-label">Area</div>
                                <div class="cm-summary-value">{{ $odp->area ?: '-' }}</div>
                            </div>
                            <div class="cm-summary-item">
                                <div class="cm-summary-label">Koordinat</div>
                                <div class="cm-summary-value">
                                    @if($odp->latitude !== null && $odp->longitude !== null)
                                        {{ $odp->latitude }}, {{ $odp->longitude }}
                                    @else
                                        -
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form id="delete-odp-form" action="{{ route('super-admin.odps.destroy', $odp) }}" method="POST" class="d-none">
            @csrf
            @method('DELETE')
        </form>
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
