@extends('layouts.admin')

@section('title', 'Detail Invoice')

@section('content')
    @include('partials.customer-management-shell-styles')

    @php($isPaid = $invoice->isPaid())
    @php($isOverdue = $invoice->isOverdue())
    @php($statusBadgeClass = $isPaid ? 'cm-badge-success' : ($isOverdue ? 'cm-badge-danger' : 'cm-badge-warning'))

    <div class="cm-page">
        <div class="cm-header">
            <div class="cm-header-main">
                <div class="cm-header-icon" style="background:linear-gradient(135deg,#7c3aed,#2563eb);">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <div class="cm-header-copy">
                    <p class="cm-kicker">Billing Detail</p>
                    <h1 class="cm-title">Detail Invoice</h1>
                    <p class="cm-subtitle">{{ $invoice->invoice_number }} · {{ $invoice->customer_name }}</p>
                </div>
            </div>
            <div class="cm-header-actions">
                <a href="{{ route('super-admin.invoices.index') }}" class="cm-btn cm-btn-muted">
                    <i class="fas fa-arrow-left"></i>
                    Kembali ke Invoice
                </a>
                @if($invoice->payment)
                    <a href="{{ route('super-admin.payments.show', $invoice->payment) }}" class="cm-btn cm-btn-primary">
                        <i class="fas fa-money-check-alt"></i>
                        Lihat Pembayaran
                    </a>
                @endif
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
                    <strong>Proses invoice belum berhasil.</strong>
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
                        <h2 class="cm-main-card-title">Ringkasan Tagihan</h2>
                        <p class="cm-main-card-subtitle">Format dibikin lebih dekat ke tenant SaaS supaya operator mudah memindai detail invoice, pelanggan, dan paket terkait.</p>
                    </div>
                </div>
                <div class="cm-main-card-body">
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="cm-summary-list">
                                <div class="cm-summary-item">
                                    <div class="cm-summary-label">Nomor Invoice</div>
                                    <div class="cm-summary-value">{{ $invoice->invoice_number }}</div>
                                </div>
                                <div class="cm-summary-item">
                                    <div class="cm-summary-label">Pelanggan</div>
                                    <div class="cm-summary-value">{{ $invoice->customer_name }}</div>
                                </div>
                                <div class="cm-summary-item">
                                    <div class="cm-summary-label">ID Pelanggan</div>
                                    <div class="cm-summary-value">{{ $invoice->customer_id ?: '-' }}</div>
                                </div>
                                <div class="cm-summary-item">
                                    <div class="cm-summary-label">Tipe Service</div>
                                    <div class="cm-summary-value">{{ strtoupper(str_replace('_', '/', (string) ($invoice->tipe_service ?? '-'))) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="cm-summary-list">
                                <div class="cm-summary-item">
                                    <div class="cm-summary-label">Tanggal Invoice</div>
                                    <div class="cm-summary-value">{{ $invoice->created_at->format('d-m-Y H:i') }}</div>
                                </div>
                                <div class="cm-summary-item">
                                    <div class="cm-summary-label">Jatuh Tempo</div>
                                    <div class="cm-summary-value">{{ $invoice->due_date?->format('d-m-Y') ?: '-' }}</div>
                                </div>
                                <div class="cm-summary-item">
                                    <div class="cm-summary-label">Status</div>
                                    <div class="cm-summary-value">
                                        <span class="cm-badge {{ $statusBadgeClass }}">
                                            {{ $isPaid ? 'LUNAS' : ($isOverdue ? 'TERLAMBAT' : 'BELUM BAYAR') }}
                                        </span>
                                    </div>
                                </div>
                                <div class="cm-summary-item">
                                    <div class="cm-summary-label">Paket</div>
                                    <div class="cm-summary-value">{{ $invoice->paket_langganan ?: '-' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Komponen</th>
                                    <th class="text-right">Nilai</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Harga Dasar</td>
                                    <td class="text-right">Rp {{ number_format((float) $invoice->harga_dasar, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>PPN</td>
                                    <td class="text-right">{{ number_format((float) $invoice->ppn_percent, 0, ',', '.') }}% / Rp {{ number_format((float) $invoice->ppn_amount, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>Status Pembayaran</td>
                                    <td class="text-right">{{ $isPaid ? 'Sudah Lunas' : 'Belum Lunas' }}</td>
                                </tr>
                                @if($invoice->paid_at)
                                    <tr>
                                        <td>Dibayar Pada</td>
                                        <td class="text-right">{{ $invoice->paid_at->format('d-m-Y H:i') }}</td>
                                    </tr>
                                @endif
                                @if($invoice->payment_reference)
                                    <tr>
                                        <td>Referensi Pembayaran</td>
                                        <td class="text-right">{{ $invoice->payment_reference }}</td>
                                    </tr>
                                @endif
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Total</th>
                                    <th class="text-right">Rp {{ number_format((float) $invoice->total, 0, ',', '.') }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="cm-side-stack">
                <div class="cm-side-card">
                    <div class="cm-side-card-header">
                        <div>
                            <h3 class="cm-side-card-title">Aksi Invoice</h3>
                            <p class="cm-side-card-subtitle">Semua tindakan penting dikumpulkan di satu tempat.</p>
                        </div>
                    </div>
                    <div class="cm-side-card-body">
                        @if($isPaid)
                            <div class="alert alert-success">
                                Invoice sudah dibayar{{ $invoice->paid_at ? ' pada '.$invoice->paid_at->format('d-m-Y H:i') : '' }}.
                            </div>
                            @if($invoice->payment)
                                <a href="{{ route('super-admin.payments.show', $invoice->payment) }}" class="cm-btn cm-btn-primary w-100">
                                    <i class="fas fa-receipt"></i>
                                    Buka Pembayaran
                                </a>
                            @endif
                        @else
                            <form action="{{ route('super-admin.invoices.pay', $invoice) }}" method="POST" class="mb-3">
                                @csrf
                                <div class="form-group">
                                    <label for="payment_method">Metode Pembayaran</label>
                                    <select id="payment_method" name="payment_method" class="form-control">
                                        <option value="cash">Tunai</option>
                                        <option value="transfer">Transfer</option>
                                        <option value="other">Lainnya</option>
                                    </select>
                                </div>
                                <div class="form-group" data-pay-field="cash">
                                    <label for="cash_received">Tunai Diterima</label>
                                    <input type="number" step="0.01" id="cash_received" name="cash_received" class="form-control" value="{{ old('cash_received', $invoice->total) }}">
                                </div>
                                <div class="form-group" data-pay-field="transfer">
                                    <label for="transfer_amount">Nominal Transfer</label>
                                    <input type="number" step="0.01" id="transfer_amount" name="transfer_amount" class="form-control" value="{{ old('transfer_amount', $invoice->total) }}">
                                </div>
                                <div class="form-group">
                                    <label for="payment_note">Catatan</label>
                                    <textarea id="payment_note" name="payment_note" class="form-control" rows="2">{{ old('payment_note') }}</textarea>
                                </div>
                                <button type="submit" class="cm-btn cm-btn-primary w-100">
                                    <i class="fas fa-check-circle"></i>
                                    Tandai Lunas
                                </button>
                            </form>

                            @if(!$invoice->renewed_without_payment)
                                <form action="{{ route('super-admin.invoices.renew', $invoice) }}" method="POST" class="mb-3">
                                    @csrf
                                    <button type="submit" class="cm-btn cm-btn-muted w-100" onclick="return confirm('Perpanjang layanan tanpa pembayaran?')">
                                        <i class="fas fa-bolt"></i>
                                        Renew Tanpa Pembayaran
                                    </button>
                                </form>
                            @else
                                <div class="alert alert-light border">Invoice ini sudah pernah di-renew tanpa pembayaran.</div>
                            @endif
                        @endif

                        <form action="{{ route('super-admin.invoices.destroy', $invoice) }}" method="POST" onsubmit="return confirm('Hapus invoice ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="cm-btn cm-btn-danger w-100">
                                <i class="fas fa-trash"></i>
                                Hapus Invoice
                            </button>
                        </form>
                    </div>
                </div>

                <div class="cm-side-card">
                    <div class="cm-side-card-header">
                        <div>
                            <h3 class="cm-side-card-title">Pelanggan Terkait</h3>
                            <p class="cm-side-card-subtitle">Snapshot layanan PPP yang terhubung ke invoice ini.</p>
                        </div>
                    </div>
                    <div class="cm-side-card-body">
                        <div class="cm-summary-list">
                            <div class="cm-summary-item">
                                <div class="cm-summary-label">Nama</div>
                                <div class="cm-summary-value">{{ $invoice->pppUser?->customer_name ?: '-' }}</div>
                            </div>
                            <div class="cm-summary-item">
                                <div class="cm-summary-label">Username PPP</div>
                                <div class="cm-summary-value">{{ $invoice->pppUser?->username ?: '-' }}</div>
                            </div>
                            <div class="cm-summary-item">
                                <div class="cm-summary-label">Status Akun</div>
                                <div class="cm-summary-value">{{ strtoupper((string) ($invoice->pppUser?->status_akun ?? '-')) }}</div>
                            </div>
                            <div class="cm-summary-item">
                                <div class="cm-summary-label">Jatuh Tempo Layanan</div>
                                <div class="cm-summary-value">{{ $invoice->pppUser?->jatuh_tempo?->format('d-m-Y') ?: '-' }}</div>
                            </div>
                            <div class="cm-summary-item">
                                <div class="cm-summary-label">Paket PPP</div>
                                <div class="cm-summary-value">{{ $invoice->pppUser?->profile?->name ?: '-' }}</div>
                            </div>
                        </div>

                        @if($invoice->pppUser)
                            <a href="{{ route('super-admin.settings.ppp-users.edit', $invoice->pppUser) }}" class="cm-btn cm-btn-muted w-100 mt-3">
                                <i class="fas fa-user-cog"></i>
                                Buka Pelanggan PPP
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const paymentMethod = document.getElementById('payment_method');
    const cashField = document.querySelector('[data-pay-field="cash"]');
    const transferField = document.querySelector('[data-pay-field="transfer"]');

    function syncPaymentFieldVisibility() {
        if (!paymentMethod) {
            return;
        }

        const method = paymentMethod.value;
        if (cashField) {
            cashField.style.display = method === 'cash' ? '' : 'none';
        }
        if (transferField) {
            transferField.style.display = method === 'transfer' ? '' : 'none';
        }
    }

    paymentMethod?.addEventListener('change', syncPaymentFieldVisibility);
    syncPaymentFieldVisibility();
});
</script>
@endpush
