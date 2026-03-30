@extends('layouts.admin')

@section('title', 'Pembayaran')

@section('content')
    @include('partials.customer-management-shell-styles')

    <div class="cm-page">
        <div class="cm-header">
            <div class="cm-header-main">
                <div class="cm-header-icon" style="background:linear-gradient(135deg,#059669,#0ea5e9);">
                    <i class="fas fa-money-check-alt"></i>
                </div>
                <div class="cm-header-copy">
                    <p class="cm-kicker">Payment Management</p>
                    <h1 class="cm-title">Pembayaran</h1>
                    <p class="cm-subtitle">Riwayat pembayaran invoice pelanggan self-hosted dengan pola tampilan yang lebih dekat ke tenant SaaS.</p>
                </div>
            </div>
            <div class="cm-header-actions">
                <a href="{{ route('super-admin.invoices.index') }}" class="cm-btn cm-btn-muted">
                    <i class="fas fa-file-invoice"></i>
                    Buka Invoice
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="cm-metric h-100">
                    <p class="cm-metric-label">Total Pembayaran</p>
                    <p class="cm-metric-value">{{ $paymentStats['total_payment'] }}</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="cm-metric h-100">
                    <p class="cm-metric-label">Status Paid</p>
                    <p class="cm-metric-value">{{ $paymentStats['paid_payment'] }}</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="cm-metric h-100">
                    <p class="cm-metric-label">Pembayaran Tunai</p>
                    <p class="cm-metric-value">{{ $paymentStats['cash_payment'] }}</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="cm-metric h-100">
                    <p class="cm-metric-label">Nominal Tercatat</p>
                    <p class="cm-metric-value">Rp {{ number_format($paymentStats['total_amount'], 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        <div class="cm-main-card">
            <div class="cm-main-card-header">
                <div>
                    <h2 class="cm-main-card-title">Riwayat Pembayaran</h2>
                    <p class="cm-main-card-subtitle">Operator bisa langsung lompat ke detail pembayaran atau invoice terkait dari tabel ini.</p>
                </div>
            </div>
            <div class="cm-main-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>No. Pembayaran</th>
                                <th>Invoice</th>
                                <th>Metode</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Waktu</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $payment)
                                <tr>
                                    <td>
                                        <div class="font-weight-bold">{{ $payment->payment_number }}</div>
                                        <div class="text-muted small">{{ $payment->payment_channel ?: '-' }}</div>
                                    </td>
                                    <td>
                                        @if($payment->invoice)
                                            <div class="font-weight-bold">{{ $payment->invoice->invoice_number }}</div>
                                            <div class="text-muted small">{{ $payment->invoice->customer_name }}</div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ strtoupper($payment->payment_method ?? '-') }}</td>
                                    <td class="font-weight-bold">{{ $payment->formatted_amount }}</td>
                                    <td>
                                        <span class="cm-badge {{ $payment->status === 'paid' ? 'cm-badge-success' : 'cm-badge-neutral' }}">
                                            {{ strtoupper($payment->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $payment->paid_at?->format('d-m-Y H:i') ?: $payment->created_at->format('d-m-Y H:i') }}</td>
                                    <td class="text-right">
                                        <div class="btn-group btn-group-sm">
                                            @if($payment->invoice)
                                                <a href="{{ route('super-admin.invoices.show', $payment->invoice) }}" class="btn btn-outline-primary btn-sm" title="Invoice">
                                                    <i class="fas fa-file-invoice"></i>
                                                </a>
                                            @endif
                                            <a href="{{ route('super-admin.payments.show', $payment) }}" class="btn btn-info btn-sm" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Belum ada pembayaran.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
