@extends('layouts.admin')

@section('title', 'Edit Pelanggan PPP')

@section('content')
    @include('partials.customer-management-shell-styles')

    @php($latestInvoice = $pppUser->invoices->first())
    @php($accountBadgeClass = match ($pppUser->status_akun) {
        'enable' => 'cm-badge-success',
        'disable' => 'cm-badge-danger',
        'isolir' => 'cm-badge-warning',
        default => 'cm-badge-neutral',
    })
    @php($paymentBadgeClass = ($pppUser->status_bayar ?? 'belum_bayar') === 'sudah_bayar' ? 'cm-badge-success' : 'cm-badge-warning')

    <div class="cm-page">
        <div class="cm-header">
            <div class="cm-header-main">
                <div class="cm-header-icon" style="background:linear-gradient(135deg,#b45309,#f59e0b);">
                    <i class="fas fa-user-edit"></i>
                </div>
                <div class="cm-header-copy">
                    <p class="cm-kicker">PPP Management</p>
                    <h1 class="cm-title">Edit Pelanggan PPP</h1>
                    <p class="cm-subtitle">{{ $pppUser->customer_name ?: 'Pelanggan tanpa nama' }} · {{ $pppUser->customer_id ?: 'ID belum diisi' }}</p>
                </div>
            </div>
            <div class="cm-header-actions">
                <a href="{{ route('super-admin.settings.ppp-users.index') }}" class="cm-btn cm-btn-muted">
                    <i class="fas fa-arrow-left"></i>
                    Kembali ke List
                </a>
                @if($latestInvoice)
                    <a href="{{ route('super-admin.invoices.show', $latestInvoice) }}" class="cm-btn cm-btn-primary">
                        <i class="fas fa-file-invoice"></i>
                        Buka Invoice
                    </a>
                @endif
            </div>
        </div>

        @if($errors->any())
            <div class="cm-alert cm-alert-danger">
                <i class="fas fa-exclamation-circle mt-1"></i>
                <div>
                    <strong>Data pelanggan belum valid.</strong>
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
                        <h2 class="cm-main-card-title">Form Pelanggan</h2>
                        <p class="cm-main-card-subtitle">Field dibuat tetap kompatibel dengan data self-hosted, tapi alur editnya dirapikan agar lebih mirip tenant SaaS.</p>
                    </div>
                </div>
                <div class="cm-main-card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <form action="{{ route('super-admin.settings.ppp-users.update', $pppUser) }}" method="POST">
                        @csrf
                        @method('PUT')
                        @include('ppp_users._form')

                        <div class="cm-form-toolbar">
                            <div class="cm-form-meta">
                                Paket saat ini: <strong>{{ $pppUser->profile?->name ?? 'Belum dipilih' }}</strong>
                                @if($pppUser->odp)
                                    · ODP: <strong>{{ $pppUser->odp->code }}</strong>
                                @endif
                            </div>
                            <div class="cm-form-actions">
                                <a href="{{ route('super-admin.settings.ppp-users.index') }}" class="cm-btn cm-btn-muted">Batal</a>
                                <button type="submit" class="cm-btn cm-btn-primary">
                                    <i class="fas fa-save"></i>
                                    Simpan Perubahan
                                </button>
                                <button type="submit" form="delete-ppp-user-form" class="cm-btn cm-btn-danger" onclick="return confirm('Hapus user PPP ini?')">
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
                            <h3 class="cm-side-card-title">Ringkasan Akun</h3>
                            <p class="cm-side-card-subtitle">Snapshot cepat untuk operasional harian.</p>
                        </div>
                    </div>
                    <div class="cm-side-card-body">
                        <div class="cm-metrics">
                            <div class="cm-metric">
                                <p class="cm-metric-label">Username</p>
                                <p class="cm-metric-value">{{ $pppUser->username ?: '-' }}</p>
                            </div>
                            <div class="cm-metric">
                                <p class="cm-metric-label">Jatuh Tempo</p>
                                <p class="cm-metric-value">{{ $pppUser->jatuh_tempo?->format('Y-m-d') ?? '-' }}</p>
                            </div>
                        </div>

                        <div class="cm-summary-list mt-3">
                            <div class="cm-summary-item">
                                <div class="cm-summary-label">Status Akun</div>
                                <div class="cm-summary-value">
                                    <span class="cm-badge {{ $accountBadgeClass }}">{{ strtoupper((string) $pppUser->status_akun) }}</span>
                                </div>
                            </div>
                            <div class="cm-summary-item">
                                <div class="cm-summary-label">Status Bayar</div>
                                <div class="cm-summary-value">
                                    <span class="cm-badge {{ $paymentBadgeClass }}">{{ strtoupper((string) ($pppUser->status_bayar ?? 'belum_bayar')) }}</span>
                                </div>
                            </div>
                            <div class="cm-summary-item">
                                <div class="cm-summary-label">Tipe Service</div>
                                <div class="cm-summary-value">{{ strtoupper(str_replace('_', '/', (string) ($pppUser->tipe_service ?? 'pppoe'))) }}</div>
                            </div>
                            <div class="cm-summary-item">
                                <div class="cm-summary-label">Paket</div>
                                <div class="cm-summary-value">{{ $pppUser->profile?->name ?? '-' }}</div>
                            </div>
                            <div class="cm-summary-item">
                                <div class="cm-summary-label">Lokasi / ODP</div>
                                <div class="cm-summary-value">{{ $pppUser->odp?->code ?? ($pppUser->odp_pop ?: '-') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cm-side-card">
                    <div class="cm-side-card-header">
                        <div>
                            <h3 class="cm-side-card-title">Aksi Operasional</h3>
                            <p class="cm-side-card-subtitle">Shortcut yang paling sering dipakai setelah edit data.</p>
                        </div>
                    </div>
                    <div class="cm-side-card-body">
                        <div class="cm-action-list">
                            @if($latestInvoice)
                                <a href="{{ route('super-admin.invoices.show', $latestInvoice) }}" class="cm-btn cm-btn-muted">
                                    <i class="fas fa-receipt"></i>
                                    Lihat Invoice {{ $latestInvoice->invoice_number ?? '' }}
                                </a>
                                @if($latestInvoice->status === 'unpaid' && ! $latestInvoice->renewed_without_payment)
                                    <form action="{{ route('super-admin.invoices.renew', $latestInvoice) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="cm-btn cm-btn-primary" onclick="return confirm('Perpanjang layanan PPP tanpa pembayaran?')">
                                            <i class="fas fa-bolt"></i>
                                            Renew Layanan
                                        </button>
                                    </form>
                                @endif
                            @else
                                <form action="{{ route('super-admin.settings.ppp-users.add-invoice', $pppUser) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="cm-btn cm-btn-primary" onclick="return confirm('Buat invoice baru untuk pelanggan ini?')">
                                        <i class="fas fa-file-invoice"></i>
                                        Buat Invoice Baru
                                    </button>
                                </form>
                            @endif

                            <a href="{{ route('super-admin.settings.ppp-users.index') }}" class="cm-btn cm-btn-muted">
                                <i class="fas fa-list"></i>
                                Kembali ke Daftar Pelanggan
                            </a>
                        </div>

                        @if($latestInvoice)
                            <div class="cm-summary-list mt-3">
                                <div class="cm-summary-item">
                                    <div class="cm-summary-label">Invoice Terakhir</div>
                                    <div class="cm-summary-value">{{ $latestInvoice->invoice_number ?? '-' }}</div>
                                </div>
                                <div class="cm-summary-item">
                                    <div class="cm-summary-label">Status Invoice</div>
                                    <div class="cm-summary-value">{{ strtoupper((string) $latestInvoice->status) }}</div>
                                </div>
                                <div class="cm-summary-item">
                                    <div class="cm-summary-label">Due Date</div>
                                    <div class="cm-summary-value">{{ $latestInvoice->due_date?->format('Y-m-d') ?? '-' }}</div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <form id="delete-ppp-user-form" action="{{ route('super-admin.settings.ppp-users.destroy', $pppUser) }}" method="POST" class="d-none">
            @csrf
            @method('DELETE')
        </form>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelector('[data-generate-customer-id]')?.addEventListener('click', async function () {
        const response = await fetch(@json(route('super-admin.settings.ppp-users.customer-id')));
        if (!response.ok) {
            return;
        }
        const payload = await response.json();
        document.getElementById('customer_id').value = payload.customer_id || '';
    });
});
</script>
@endpush
