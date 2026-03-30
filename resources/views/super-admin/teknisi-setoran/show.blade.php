@extends('layouts.admin')

@section('title', 'Detail Setoran Teknisi')

@section('content')
    <div class="container py-3">
        <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
            <div>
                <h1 class="h3 mb-1">Detail Setoran Teknisi</h1>
                <p class="text-muted mb-0">{{ $teknisiSetoran->teknisi?->name ?? '-' }} - {{ $teknisiSetoran->period_date?->translatedFormat('d F Y') }}</p>
            </div>
            <a href="{{ route('teknisi-setoran.index') }}" class="btn btn-outline-secondary btn-sm mt-2 mt-md-0">Kembali</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row">
            <div class="col-lg-4 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Info Setoran</h2>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted">Teknisi</td>
                                <td><strong>{{ $teknisiSetoran->teknisi?->name ?? '-' }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Tanggal</td>
                                <td><strong>{{ $teknisiSetoran->period_date?->translatedFormat('d F Y') }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Jumlah Nota</td>
                                <td><strong>{{ $teknisiSetoran->total_invoices }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Total Tagihan</td>
                                <td><strong>Rp {{ number_format((float) $teknisiSetoran->total_tagihan, 0, ',', '.') }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Total Tunai</td>
                                <td><strong class="text-success">Rp {{ number_format((float) $teknisiSetoran->total_cash, 0, ',', '.') }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Status</td>
                                <td>{{ ucfirst($teknisiSetoran->status) }}</td>
                            </tr>
                        </table>

                        <hr>

                        @php($currentUser = auth()->user())

                        @if($teknisiSetoran->status === 'draft' && ($currentUser->isSuperAdmin() || $currentUser->role === \App\Models\User::ROLE_ADMINISTRATOR || $currentUser->role === \App\Models\User::ROLE_TEKNISI))
                            <form method="POST" action="{{ route('teknisi-setoran.submit', $teknisiSetoran) }}" class="mb-2">
                                @csrf
                                <button type="submit" class="btn btn-warning btn-block" onclick="return confirm('Submit setoran ini?')">Submit ke Keuangan</button>
                            </form>
                        @endif

                        @if($teknisiSetoran->status === 'submitted' && ($currentUser->isSuperAdmin() || in_array($currentUser->role, [\App\Models\User::ROLE_ADMINISTRATOR, \App\Models\User::ROLE_KEUANGAN], true)))
                            <form method="POST" action="{{ route('teknisi-setoran.verify', $teknisiSetoran) }}">
                                @csrf
                                <div class="form-group">
                                    <label for="notes">Catatan Verifikasi</label>
                                    <textarea id="notes" name="notes" rows="3" class="form-control" placeholder="Opsional">{{ old('notes') }}</textarea>
                                </div>
                                <button type="submit" class="btn btn-success btn-block">Verifikasi Setoran</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-8 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Daftar Invoice</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Pelanggan</th>
                                    <th class="text-right">Total</th>
                                    <th class="text-right">Tunai</th>
                                    <th>Catatan</th>
                                    <th>Waktu Bayar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($invoices as $invoice)
                                    <tr>
                                        <td>{{ $invoice->invoice_number }}</td>
                                        <td>
                                            <div>{{ $invoice->customer_name }}</div>
                                            <div class="small text-muted">{{ $invoice->customer_id }}</div>
                                        </td>
                                        <td class="text-right">Rp {{ number_format((float) $invoice->total, 0, ',', '.') }}</td>
                                        <td class="text-right text-success">Rp {{ number_format((float) $invoice->cash_received, 0, ',', '.') }}</td>
                                        <td>{{ $invoice->payment_note ?: '-' }}</td>
                                        <td>{{ $invoice->paid_at?->format('H:i') ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">Tidak ada invoice untuk periode ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
