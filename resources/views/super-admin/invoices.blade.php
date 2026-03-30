@extends('layouts.admin')

@section('title', $pageTitle)

@section('content')
    @include('partials.customer-management-shell-styles')

    <div class="cm-page">
        <div class="cm-header">
            <div class="cm-header-main">
                <div class="cm-header-icon" style="background:linear-gradient(135deg,#7c3aed,#2563eb);">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="cm-header-copy">
                    <p class="cm-kicker">Billing Management</p>
                    <h1 class="cm-title">{{ $pageTitle }}</h1>
                    <p class="cm-subtitle">{{ $pageDescription }}</p>
                </div>
            </div>
            <div class="cm-header-actions">
                <a href="{{ route('super-admin.invoices.index') }}" class="cm-btn {{ request()->routeIs('super-admin.invoices.index') ? 'cm-btn-primary' : 'cm-btn-muted' }}">
                    <i class="fas fa-layer-group"></i>
                    Semua Invoice
                </a>
                <a href="{{ route('super-admin.invoices.unpaid') }}" class="cm-btn {{ request()->routeIs('super-admin.invoices.unpaid') ? 'cm-btn-primary' : 'cm-btn-muted' }}">
                    <i class="fas fa-exclamation-circle"></i>
                    Belum Lunas
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row">
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="cm-metric h-100">
                    <p class="cm-metric-label">Total Invoice</p>
                    <p class="cm-metric-value">{{ $invoiceStats['total_invoice'] }}</p>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="cm-metric h-100">
                    <p class="cm-metric-label">Invoice Lunas</p>
                    <p class="cm-metric-value">{{ $invoiceStats['invoice_paid'] }}</p>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="cm-metric h-100">
                    <p class="cm-metric-label">Belum Lunas</p>
                    <p class="cm-metric-value">{{ $invoiceStats['invoice_unpaid'] }}</p>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="cm-metric h-100">
                    <p class="cm-metric-label">Jatuh Tempo</p>
                    <p class="cm-metric-value">{{ $invoiceStats['invoice_overdue'] }}</p>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="cm-metric h-100">
                    <p class="cm-metric-label">Nominal Lunas</p>
                    <p class="cm-metric-value">Rp {{ number_format($invoiceStats['nominal_paid'], 0, ',', '.') }}</p>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="cm-metric h-100">
                    <p class="cm-metric-label">Nominal Belum Lunas</p>
                    <p class="cm-metric-value">Rp {{ number_format($invoiceStats['nominal_unpaid'], 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        <div class="cm-layout">
            <div class="cm-main-card">
                <div class="cm-main-card-header">
                    <div>
                        <h2 class="cm-main-card-title">Daftar Invoice</h2>
                        <p class="cm-main-card-subtitle">Flow tindakan dibuat lebih dekat ke tenant SaaS: cek status, renew, bayar, buka detail, lalu lanjut ke pembayaran.</p>
                    </div>
                </div>
                <div class="cm-main-card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Invoice</th>
                                    <th>Pelanggan</th>
                                    <th>Tipe / Paket</th>
                                    <th>Tagihan</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Status</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($invoices as $invoice)
                                    @php($isPaid = $invoice->status === 'paid')
                                    @php($isOverdue = $invoice->isOverdue())
                                    @php($statusBadgeClass = $isPaid ? 'cm-badge-success' : ($isOverdue ? 'cm-badge-danger' : 'cm-badge-warning'))
                                    <tr>
                                        <td>
                                            <div class="font-weight-bold">{{ $invoice->invoice_number }}</div>
                                            <div class="text-muted small">{{ $invoice->created_at->format('d-m-Y H:i') }}</div>
                                        </td>
                                        <td>
                                            <div class="font-weight-bold">{{ $invoice->customer_name }}</div>
                                            <div class="text-muted small">{{ $invoice->customer_id ?: '-' }}</div>
                                        </td>
                                        <td>
                                            <div>{{ strtoupper(str_replace('_', '/', (string) ($invoice->tipe_service ?? '-'))) }}</div>
                                            <div class="text-muted small">{{ $invoice->paket_langganan ?: '-' }}</div>
                                        </td>
                                        <td>
                                            <div class="font-weight-bold">{{ $invoice->formatted_total }}</div>
                                            @if($invoice->ppn_amount > 0)
                                                <div class="text-muted small">PPN {{ number_format($invoice->ppn_percent, 0, ',', '.') }}%</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div>{{ $invoice->due_date?->format('d-m-Y') ?: '-' }}</div>
                                            @if($invoice->renewed_without_payment)
                                                <div class="text-primary small">Sudah di-renew tanpa bayar</div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="cm-badge {{ $statusBadgeClass }}">
                                                {{ $isPaid ? 'LUNAS' : ($isOverdue ? 'TERLAMBAT' : 'BELUM BAYAR') }}
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <div class="btn-group btn-group-sm">
                                                @if(!$isPaid)
                                                    <button
                                                        type="button"
                                                        class="btn btn-success btn-sm"
                                                        data-toggle="modal"
                                                        data-target="#invoice-pay-modal"
                                                        data-pay-action="{{ route('super-admin.invoices.pay', $invoice) }}"
                                                        data-invoice-number="{{ $invoice->invoice_number }}"
                                                        data-customer-name="{{ $invoice->customer_name }}"
                                                        data-total="{{ (float) $invoice->total }}"
                                                        title="Bayar"
                                                    >
                                                        <i class="fas fa-credit-card"></i>
                                                    </button>
                                                    @if(!$invoice->renewed_without_payment)
                                                        <form action="{{ route('super-admin.invoices.renew', $invoice) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Perpanjang layanan tanpa pembayaran?')" title="Renew">
                                                                <i class="fas fa-bolt"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                @elseif($invoice->payment)
                                                    <a href="{{ route('super-admin.payments.show', $invoice->payment) }}" class="btn btn-success btn-sm" title="Lihat Pembayaran">
                                                        <i class="fas fa-money-check-alt"></i>
                                                    </a>
                                                @endif

                                                <a href="{{ route('super-admin.invoices.show', $invoice) }}" class="btn btn-info btn-sm" title="Detail">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <form action="{{ route('super-admin.invoices.destroy', $invoice) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus invoice ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">Belum ada invoice.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="cm-side-stack">
                <div class="cm-side-card">
                    <div class="cm-side-card-header">
                        <div>
                            <h3 class="cm-side-card-title">Buat Invoice</h3>
                            <p class="cm-side-card-subtitle">Form cepat untuk menambah tagihan pelanggan PPP.</p>
                        </div>
                    </div>
                    <div class="cm-side-card-body">
                        <form action="{{ route('super-admin.invoices.store') }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="ppp_user_id">Pelanggan PPP</label>
                                <select id="ppp_user_id" name="ppp_user_id" class="form-control">
                                    @foreach($pppUsers as $pppUser)
                                        <option value="{{ $pppUser->id }}">
                                            {{ $pppUser->customer_id }} - {{ $pppUser->customer_name }}{{ $pppUser->profile ? ' / '.$pppUser->profile->name : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-6">
                                    <label for="due_date">Jatuh Tempo</label>
                                    <input type="date" id="due_date" name="due_date" class="form-control" value="{{ old('due_date', now()->toDateString()) }}">
                                </div>
                                <div class="form-group col-6">
                                    <label for="paket_langganan">Nama Paket</label>
                                    <input type="text" id="paket_langganan" name="paket_langganan" class="form-control" value="{{ old('paket_langganan') }}">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-6">
                                    <label for="harga_dasar">Harga Dasar</label>
                                    <input type="number" step="0.01" id="harga_dasar" name="harga_dasar" class="form-control" value="{{ old('harga_dasar') }}">
                                </div>
                                <div class="form-group col-6">
                                    <label for="ppn_percent">PPN %</label>
                                    <input type="number" step="0.01" id="ppn_percent" name="ppn_percent" class="form-control" value="{{ old('ppn_percent') }}">
                                </div>
                            </div>
                            <button type="submit" class="cm-btn cm-btn-primary w-100">
                                <i class="fas fa-plus-circle"></i>
                                Buat Invoice
                            </button>
                        </form>
                    </div>
                </div>

                @if($showMonthlyDebtRecap)
                    <div class="cm-side-card">
                        <div class="cm-side-card-header">
                            <div>
                                <h3 class="cm-side-card-title">Rekap Tunggakan Bulanan</h3>
                                <p class="cm-side-card-subtitle">Ringkasan cepat invoice terhutang per periode.</p>
                            </div>
                        </div>
                        <div class="cm-side-card-body">
                            <div class="cm-summary-list">
                                @forelse($monthlyDebt as $monthDebt)
                                    <div class="cm-summary-item">
                                        <div class="cm-summary-label">{{ $monthDebt['month_label'] }}</div>
                                        <div class="cm-summary-value">
                                            {{ $monthDebt['invoice_count'] }} invoice
                                            <div class="text-muted small">Rp {{ number_format($monthDebt['total_amount'], 0, ',', '.') }}</div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-muted">Belum ada invoice terhutang.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @else
                    <div class="cm-side-card">
                        <div class="cm-side-card-header">
                            <div>
                                <h3 class="cm-side-card-title">Catatan Operasional</h3>
                                <p class="cm-side-card-subtitle">Arahkan tindakan berikutnya dengan cepat.</p>
                            </div>
                        </div>
                        <div class="cm-side-card-body">
                            <div class="cm-summary-list">
                                <div class="cm-summary-item">
                                    <div class="cm-summary-label">Renew</div>
                                    <div class="cm-summary-value">Dipakai saat layanan perlu aktif dulu sebelum pembayaran masuk.</div>
                                </div>
                                <div class="cm-summary-item">
                                    <div class="cm-summary-label">Bayar</div>
                                    <div class="cm-summary-value">Tandai invoice lunas dan aktifkan status pelanggan bila terkait PPP.</div>
                                </div>
                                <div class="cm-summary-item">
                                    <div class="cm-summary-label">Detail</div>
                                    <div class="cm-summary-value">Buka histori invoice, pembayaran, dan status pelanggan terkait.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="modal fade" id="invoice-pay-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="invoice-pay-form" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Konfirmasi Pembayaran</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-light border">
                            <div class="font-weight-bold" id="invoice-pay-modal-number">-</div>
                            <div class="text-muted small" id="invoice-pay-modal-customer">-</div>
                            <div class="mt-2">Total: <strong id="invoice-pay-modal-total">Rp 0</strong></div>
                        </div>

                        <div class="form-group">
                            <label for="invoice_payment_method">Metode Pembayaran</label>
                            <select id="invoice_payment_method" name="payment_method" class="form-control">
                                <option value="cash">Tunai</option>
                                <option value="transfer">Transfer</option>
                                <option value="other">Lainnya</option>
                            </select>
                        </div>
                        <div class="form-group" data-pay-field="cash">
                            <label for="invoice_cash_received">Tunai Diterima</label>
                            <input type="number" step="0.01" id="invoice_cash_received" name="cash_received" class="form-control">
                        </div>
                        <div class="form-group" data-pay-field="transfer">
                            <label for="invoice_transfer_amount">Nominal Transfer</label>
                            <input type="number" step="0.01" id="invoice_transfer_amount" name="transfer_amount" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="invoice_payment_note">Catatan</label>
                            <textarea id="invoice_payment_note" name="payment_note" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Tandai Lunas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('invoice-pay-modal');
    const form = document.getElementById('invoice-pay-form');
    const paymentMethod = document.getElementById('invoice_payment_method');
    const cashField = document.querySelector('[data-pay-field="cash"]');
    const transferField = document.querySelector('[data-pay-field="transfer"]');
    const cashInput = document.getElementById('invoice_cash_received');
    const transferInput = document.getElementById('invoice_transfer_amount');

    function formatRupiah(value) {
        const number = Number(value || 0);
        return 'Rp ' + number.toLocaleString('id-ID');
    }

    function syncPaymentFieldVisibility() {
        const method = paymentMethod.value;
        cashField.style.display = method === 'cash' ? '' : 'none';
        transferField.style.display = method === 'transfer' ? '' : 'none';
    }

    paymentMethod?.addEventListener('change', syncPaymentFieldVisibility);
    syncPaymentFieldVisibility();

    $('#invoice-pay-modal').on('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const action = button.getAttribute('data-pay-action');
        const invoiceNumber = button.getAttribute('data-invoice-number');
        const customerName = button.getAttribute('data-customer-name');
        const total = button.getAttribute('data-total');

        form.action = action;
        document.getElementById('invoice-pay-modal-number').textContent = invoiceNumber || '-';
        document.getElementById('invoice-pay-modal-customer').textContent = customerName || '-';
        document.getElementById('invoice-pay-modal-total').textContent = formatRupiah(total);
        cashInput.value = total || '';
        transferInput.value = total || '';
        document.getElementById('invoice_payment_note').value = '';
        paymentMethod.value = 'cash';
        syncPaymentFieldVisibility();
    });
});
</script>
@endpush
