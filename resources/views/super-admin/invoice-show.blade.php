@extends('layouts.admin')

@section('title', 'Detail Invoice')

@section('content')
    <div class="container">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 pl-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 mb-1">Detail Invoice</h1>
                <p class="text-muted mb-0">{{ $invoice->invoice_number }} untuk {{ $invoice->customer_name }}</p>
            </div>
            <a href="{{ route('super-admin.invoices.index') }}" class="btn btn-outline-secondary">Kembali</a>
        </div>

        <div class="row">
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Ringkasan Tagihan</h3>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="small text-muted">Pelanggan</div>
                                <div class="font-weight-bold">{{ $invoice->customer_name }}</div>
                                <div class="text-muted">{{ $invoice->customer_id }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="small text-muted">Paket</div>
                                <div class="font-weight-bold">{{ $invoice->paket_langganan ?: '-' }}</div>
                                <div class="text-muted">{{ strtoupper(str_replace('_', '/', $invoice->tipe_service ?? '-')) }}</div>
                            </div>
                        </div>
                        <table class="table table-sm">
                            <tr>
                                <td class="text-muted">Harga Dasar</td>
                                <td class="text-right">Rp {{ number_format($invoice->harga_dasar, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">PPN</td>
                                <td class="text-right">{{ number_format($invoice->ppn_percent, 0, ',', '.') }}% / Rp {{ number_format($invoice->ppn_amount, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Jatuh Tempo</td>
                                <td class="text-right">{{ $invoice->due_date?->format('d-m-Y') ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Status</td>
                                <td class="text-right">
                                    <span class="badge {{ $invoice->status === 'paid' ? 'badge-success' : ($invoice->isOverdue() ? 'badge-danger' : 'badge-warning') }}">
                                        {{ strtoupper($invoice->status) }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted font-weight-bold">Total</td>
                                <td class="text-right font-weight-bold">Rp {{ number_format($invoice->total, 0, ',', '.') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Aksi Invoice</h3>
                    </div>
                    <div class="card-body">
                        @if($invoice->isPaid())
                            <div class="alert alert-success mb-3">
                                Invoice sudah dibayar{{ $invoice->paid_at ? ' pada '.$invoice->paid_at->format('d-m-Y H:i') : '' }}.
                            </div>
                            @if($invoice->payment)
                                <a href="{{ route('super-admin.payments.show', $invoice->payment) }}" class="btn btn-outline-primary btn-block">Lihat Pembayaran</a>
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
                                <div class="form-group">
                                    <label for="cash_received">Tunai Diterima</label>
                                    <input type="number" step="0.01" id="cash_received" name="cash_received" class="form-control" value="{{ old('cash_received', $invoice->total) }}">
                                </div>
                                <div class="form-group">
                                    <label for="transfer_amount">Nominal Transfer</label>
                                    <input type="number" step="0.01" id="transfer_amount" name="transfer_amount" class="form-control" value="{{ old('transfer_amount', $invoice->total) }}">
                                </div>
                                <div class="form-group">
                                    <label for="payment_note">Catatan</label>
                                    <textarea id="payment_note" name="payment_note" class="form-control" rows="2">{{ old('payment_note') }}</textarea>
                                </div>
                                <button type="submit" class="btn btn-success btn-block">Tandai Lunas</button>
                            </form>

                            <form action="{{ route('super-admin.invoices.renew', $invoice) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-outline-primary btn-block" onclick="return confirm('Perpanjang layanan tanpa pembayaran?');">
                                    Perpanjang Tanpa Pembayaran
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Pelanggan PPP</h3>
                    </div>
                    <div class="card-body">
                        <div class="small text-muted">Nama</div>
                        <div class="font-weight-bold">{{ $invoice->pppUser?->customer_name ?: '-' }}</div>
                        <div class="small text-muted mt-3">Username PPP</div>
                        <div>{{ $invoice->pppUser?->username ?: '-' }}</div>
                        <div class="small text-muted mt-3">Status Akun</div>
                        <div>{{ strtoupper($invoice->pppUser?->status_akun ?? '-') }}</div>
                        <div class="small text-muted mt-3">Jatuh Tempo Layanan</div>
                        <div>{{ $invoice->pppUser?->jatuh_tempo?->format('d-m-Y') ?: '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
