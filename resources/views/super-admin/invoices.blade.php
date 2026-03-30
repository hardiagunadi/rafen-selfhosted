@extends('layouts.admin')

@section('title', $pageTitle)

@section('content')
    <div class="container">
        <div class="mb-3">
            <h1 class="h3 mb-1">{{ $pageTitle }}</h1>
            <p class="text-muted mb-0">{{ $pageDescription }}</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if($showMonthlyDebtRecap)
            <div class="row mb-3">
                <div class="col-md-3 col-sm-6 mb-2">
                    <div class="p-3 border rounded h-100">
                        <div class="small text-uppercase text-muted">Invoice Terhutang</div>
                        <div class="h5 mb-0">{{ $unpaidSummary['invoice_count'] }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-2">
                    <div class="p-3 border rounded h-100">
                        <div class="small text-uppercase text-muted">Total Terhutang</div>
                        <div class="h5 mb-0">Rp {{ number_format($unpaidSummary['total_amount'], 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-2">
                    <div class="p-3 border rounded h-100">
                        <div class="small text-uppercase text-muted">Bulan Terhutang</div>
                        <div class="h5 mb-0">{{ $unpaidSummary['month_count'] }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-2">
                    <div class="p-3 border rounded h-100">
                        <div class="small text-uppercase text-muted">Periode Terlama</div>
                        <div class="h5 mb-0">{{ $unpaidSummary['oldest_month_label'] }}</div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">Rekap Invoice Terhutang per Bulan</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Bulan</th>
                                    <th>Jumlah Invoice</th>
                                    <th class="text-right">Total Terhutang</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($monthlyDebt as $monthDebt)
                                    <tr>
                                        <td>{{ $monthDebt['month_label'] }}</td>
                                        <td>{{ $monthDebt['invoice_count'] }} invoice</td>
                                        <td class="text-right font-weight-bold">Rp {{ number_format($monthDebt['total_amount'], 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">Belum ada invoice terhutang.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0">Tambah Invoice</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('super-admin.invoices.store') }}" method="POST">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="ppp_user_id">Pelanggan PPP</label>
                            <select id="ppp_user_id" name="ppp_user_id" class="form-control">
                                @foreach($pppUsers as $pppUser)
                                    <option value="{{ $pppUser->id }}">
                                        {{ $pppUser->customer_id }} - {{ $pppUser->customer_name }}{{ $pppUser->profile ? ' / '.$pppUser->profile->name : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="due_date">Jatuh Tempo</label>
                            <input type="date" id="due_date" name="due_date" class="form-control" value="{{ old('due_date', now()->toDateString()) }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="harga_dasar">Harga Dasar</label>
                            <input type="number" step="0.01" id="harga_dasar" name="harga_dasar" class="form-control" value="{{ old('harga_dasar') }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="ppn_percent">PPN %</label>
                            <input type="number" step="0.01" id="ppn_percent" name="ppn_percent" class="form-control" value="{{ old('ppn_percent') }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="paket_langganan">Nama Paket</label>
                            <input type="text" id="paket_langganan" name="paket_langganan" class="form-control" value="{{ old('paket_langganan') }}">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Buat Invoice</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Daftar Invoice</h3>
                <div class="d-flex" style="gap: .5rem;">
                    <a href="{{ route('super-admin.invoices.index') }}" class="btn btn-sm btn-outline-secondary">Semua</a>
                    <a href="{{ route('super-admin.invoices.unpaid') }}" class="btn btn-sm btn-outline-secondary">Belum Lunas</a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Invoice</th>
                                <th>Pelanggan</th>
                                <th>Paket</th>
                                <th>Jatuh Tempo</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoices as $invoice)
                                <tr>
                                    <td>
                                        <div class="font-weight-bold">{{ $invoice->invoice_number }}</div>
                                        <div class="text-muted small">{{ $invoice->created_at->format('d-m-Y H:i') }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $invoice->customer_name }}</div>
                                        <div class="text-muted small">{{ $invoice->customer_id }}</div>
                                    </td>
                                    <td>{{ $invoice->paket_langganan ?: '-' }}</td>
                                    <td>{{ $invoice->due_date?->format('d-m-Y') ?: '-' }}</td>
                                    <td class="font-weight-bold">{{ $invoice->formatted_total }}</td>
                                    <td>
                                        <span class="badge {{ $invoice->status === 'paid' ? 'badge-success' : ($invoice->isOverdue() ? 'badge-danger' : 'badge-warning') }}">
                                            {{ strtoupper($invoice->status) }}
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <div class="d-flex justify-content-end" style="gap: .5rem;">
                                            <a href="{{ route('super-admin.invoices.show', $invoice) }}" class="btn btn-outline-info btn-sm">Detail</a>
                                            <form action="{{ route('super-admin.invoices.destroy', $invoice) }}" method="POST" onsubmit="return confirm('Hapus invoice ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-3">Belum ada invoice.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
