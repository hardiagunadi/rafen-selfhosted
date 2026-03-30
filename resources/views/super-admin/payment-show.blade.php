@extends('layouts.admin')

@section('title', 'Detail Pembayaran')

@section('content')
    @include('partials.customer-management-shell-styles')

    <div class="cm-page">
        <div class="cm-header">
            <div class="cm-header-main">
                <div class="cm-header-icon" style="background:linear-gradient(135deg,#059669,#0ea5e9);">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="cm-header-copy">
                    <p class="cm-kicker">Payment Detail</p>
                    <h1 class="cm-title">Detail Pembayaran</h1>
                    <p class="cm-subtitle">{{ $payment->payment_number }}</p>
                </div>
            </div>
            <div class="cm-header-actions">
                <a href="{{ route('super-admin.payments.index') }}" class="cm-btn cm-btn-muted">
                    <i class="fas fa-arrow-left"></i>
                    Kembali ke Pembayaran
                </a>
                @if($payment->invoice)
                    <a href="{{ route('super-admin.invoices.show', $payment->invoice) }}" class="cm-btn cm-btn-primary">
                        <i class="fas fa-file-invoice"></i>
                        Buka Invoice
                    </a>
                @endif
            </div>
        </div>

        <div class="cm-layout">
            <div class="cm-main-card">
                <div class="cm-main-card-header">
                    <div>
                        <h2 class="cm-main-card-title">Rincian Pembayaran</h2>
                        <p class="cm-main-card-subtitle">Format dibuat lebih mudah dipindai agar konsisten dengan area billing lainnya.</p>
                    </div>
                </div>
                <div class="cm-main-card-body">
                    <div class="cm-summary-list">
                        <div class="cm-summary-item">
                            <div class="cm-summary-label">No. Pembayaran</div>
                            <div class="cm-summary-value">{{ $payment->payment_number }}</div>
                        </div>
                        <div class="cm-summary-item">
                            <div class="cm-summary-label">Metode</div>
                            <div class="cm-summary-value">{{ strtoupper($payment->payment_method ?? '-') }}</div>
                        </div>
                        <div class="cm-summary-item">
                            <div class="cm-summary-label">Channel</div>
                            <div class="cm-summary-value">{{ $payment->payment_channel ?: '-' }}</div>
                        </div>
                        <div class="cm-summary-item">
                            <div class="cm-summary-label">Amount</div>
                            <div class="cm-summary-value">Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</div>
                        </div>
                        <div class="cm-summary-item">
                            <div class="cm-summary-label">Total</div>
                            <div class="cm-summary-value">Rp {{ number_format((float) $payment->total_amount, 0, ',', '.') }}</div>
                        </div>
                        <div class="cm-summary-item">
                            <div class="cm-summary-label">Status</div>
                            <div class="cm-summary-value">
                                <span class="cm-badge {{ $payment->status === 'paid' ? 'cm-badge-success' : 'cm-badge-neutral' }}">
                                    {{ strtoupper($payment->status) }}
                                </span>
                            </div>
                        </div>
                        <div class="cm-summary-item">
                            <div class="cm-summary-label">Waktu Bayar</div>
                            <div class="cm-summary-value">{{ $payment->paid_at?->format('d-m-Y H:i') ?: '-' }}</div>
                        </div>
                        <div class="cm-summary-item">
                            <div class="cm-summary-label">Catatan</div>
                            <div class="cm-summary-value">{{ $payment->notes ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="cm-side-stack">
                <div class="cm-side-card">
                    <div class="cm-side-card-header">
                        <div>
                            <h3 class="cm-side-card-title">Invoice Terkait</h3>
                            <p class="cm-side-card-subtitle">Referensi cepat ke tagihan asal pembayaran ini.</p>
                        </div>
                    </div>
                    <div class="cm-side-card-body">
                        @if($payment->invoice)
                            <div class="cm-summary-list">
                                <div class="cm-summary-item">
                                    <div class="cm-summary-label">Invoice</div>
                                    <div class="cm-summary-value">{{ $payment->invoice->invoice_number }}</div>
                                </div>
                                <div class="cm-summary-item">
                                    <div class="cm-summary-label">Pelanggan</div>
                                    <div class="cm-summary-value">{{ $payment->invoice->customer_name }}</div>
                                </div>
                                <div class="cm-summary-item">
                                    <div class="cm-summary-label">Total Invoice</div>
                                    <div class="cm-summary-value">Rp {{ number_format((float) $payment->invoice->total, 0, ',', '.') }}</div>
                                </div>
                            </div>

                            <a href="{{ route('super-admin.invoices.show', $payment->invoice) }}" class="cm-btn cm-btn-primary w-100 mt-3">
                                <i class="fas fa-eye"></i>
                                Buka Invoice
                            </a>
                        @else
                            <div class="text-muted">Belum ada invoice yang terhubung ke pembayaran ini.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
