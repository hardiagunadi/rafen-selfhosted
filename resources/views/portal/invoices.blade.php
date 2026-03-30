@extends('portal.layout')

@section('title', 'Riwayat Tagihan')

@section('content')
    <div class="mb-3">
        <h1 class="h3 mb-1">Riwayat Tagihan</h1>
        <p class="text-muted mb-0">Daftar invoice untuk akun internet Anda.</p>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>No. Invoice</th>
                            <th>Paket</th>
                            <th>Total</th>
                            <th>Jatuh Tempo</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            <tr>
                                <td>{{ $invoice->invoice_number }}</td>
                                <td>{{ $invoice->paket_langganan ?: '-' }}</td>
                                <td><strong>Rp {{ number_format($invoice->total, 0, ',', '.') }}</strong></td>
                                <td>{{ $invoice->due_date?->format('d/m/Y') ?: '-' }}</td>
                                <td>
                                    <span class="badge {{ $invoice->status === 'paid' ? 'badge-success' : ($invoice->isOverdue() ? 'badge-danger' : 'badge-warning') }}">
                                        {{ $invoice->status === 'paid' ? 'LUNAS' : ($invoice->isOverdue() ? 'OVERDUE' : 'BELUM BAYAR') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">Belum ada tagihan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($invoices->hasPages())
            <div class="card-footer">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>
@endsection
