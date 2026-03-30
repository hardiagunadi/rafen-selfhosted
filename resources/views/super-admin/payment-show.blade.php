@extends('layouts.admin')

@section('title', 'Detail Pembayaran')

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 mb-1">Detail Pembayaran</h1>
                <p class="text-muted mb-0">{{ $payment->payment_number }}</p>
            </div>
            <a href="{{ route('super-admin.payments.index') }}" class="btn btn-outline-secondary">Kembali</a>
        </div>

        <div class="card">
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <td class="text-muted">No. Pembayaran</td>
                        <td class="text-right">{{ $payment->payment_number }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Invoice</td>
                        <td class="text-right">
                            @if($payment->invoice)
                                <a href="{{ route('super-admin.invoices.show', $payment->invoice) }}">{{ $payment->invoice->invoice_number }}</a>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Metode</td>
                        <td class="text-right">{{ strtoupper($payment->payment_method ?? '-') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Channel</td>
                        <td class="text-right">{{ $payment->payment_channel ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Amount</td>
                        <td class="text-right">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Total</td>
                        <td class="text-right font-weight-bold">Rp {{ number_format($payment->total_amount, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Status</td>
                        <td class="text-right">{{ strtoupper($payment->status) }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Waktu Bayar</td>
                        <td class="text-right">{{ $payment->paid_at?->format('d-m-Y H:i') ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Catatan</td>
                        <td class="text-right">{{ $payment->notes ?: '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
@endsection
