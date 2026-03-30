@extends('layouts.admin')

@section('title', 'Pembayaran')

@section('content')
    <div class="container">
        <div class="mb-3">
            <h1 class="h3 mb-1">Pembayaran</h1>
            <p class="text-muted mb-0">Riwayat pembayaran manual untuk invoice pelanggan self-hosted.</p>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
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
                                    <td>{{ $payment->payment_number }}</td>
                                    <td>{{ $payment->invoice?->invoice_number ?: '-' }}</td>
                                    <td>{{ strtoupper($payment->payment_method ?? '-') }}</td>
                                    <td>{{ $payment->formatted_amount }}</td>
                                    <td>
                                        <span class="badge {{ $payment->status === 'paid' ? 'badge-success' : 'badge-secondary' }}">
                                            {{ strtoupper($payment->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $payment->paid_at?->format('d-m-Y H:i') ?: $payment->created_at->format('d-m-Y H:i') }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('super-admin.payments.show', $payment) }}" class="btn btn-outline-info btn-sm">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-3">Belum ada pembayaran.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
