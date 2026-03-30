@extends('layouts.admin')

@section('title', $pageTitle)

@section('content')
    @php($summary = $report['summary'] ?? [])

    <div class="container-fluid">
        <div class="mb-3">
            <h1 class="h3 mb-1">{{ $pageTitle }}</h1>
            <p class="text-muted mb-0">Laporan keuangan single-tenant berbasis invoice berbayar, transaksi voucher, fee pembayaran, dan pengeluaran manual.</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('super-admin.reports.income') }}">
                    <div class="form-row">
                        <div class="form-group col-md-2">
                            <label for="report">Jenis Report</label>
                            <select id="report" name="report" class="form-control">
                                <option value="daily" @selected($reportType === 'daily')>Harian</option>
                                <option value="period" @selected($reportType === 'period')>Periode</option>
                                <option value="expense" @selected($reportType === 'expense')>Pengeluaran</option>
                                <option value="profit_loss" @selected($reportType === 'profit_loss')>Laba Rugi</option>
                                <option value="bhp_uso" @selected($reportType === 'bhp_uso')>BHP | USO</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="tipe_user">Tipe User</label>
                            <select id="tipe_user" name="tipe_user" class="form-control">
                                <option value="semua" @selected(($filters['tipe_user'] ?? '') === 'semua')>Semua</option>
                                <option value="customer" @selected(($filters['tipe_user'] ?? '') === 'customer')>Customer</option>
                                <option value="voucher" @selected(($filters['tipe_user'] ?? '') === 'voucher')>Voucher</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="service_type">Tipe Service</label>
                            <select id="service_type" name="service_type" class="form-control">
                                <option value="" @selected(($filters['service_type'] ?? '') === '')>Semua</option>
                                <option value="pppoe" @selected(($filters['service_type'] ?? '') === 'pppoe')>PPPoE</option>
                                <option value="hotspot" @selected(($filters['service_type'] ?? '') === 'hotspot')>Hotspot</option>
                                <option value="voucher" @selected(($filters['service_type'] ?? '') === 'voucher')>Voucher</option>
                            </select>
                        </div>
                        @if($reportType === 'daily')
                            <div class="form-group col-md-2">
                                <label for="date">Tanggal</label>
                                <input type="date" id="date" name="date" class="form-control" value="{{ $filters['date'] ?? now()->toDateString() }}">
                            </div>
                        @else
                            <div class="form-group col-md-2">
                                <label for="start_date">Mulai</label>
                                <input type="date" id="start_date" name="start_date" class="form-control" value="{{ $filters['start_date'] ?? now()->startOfMonth()->toDateString() }}">
                            </div>
                            <div class="form-group col-md-2">
                                <label for="end_date">Selesai</label>
                                <input type="date" id="end_date" name="end_date" class="form-control" value="{{ $filters['end_date'] ?? now()->endOfMonth()->toDateString() }}">
                            </div>
                        @endif
                        <div class="form-group col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-block">Tampilkan</button>
                        </div>
                    </div>

                    @if($reportType === 'bhp_uso' || $reportType === 'profit_loss')
                        <div class="form-row">
                            <div class="form-group col-md-2">
                                <label for="bhp_rate">Tarif BHP (%)</label>
                                <input type="number" step="0.01" id="bhp_rate" name="bhp_rate" class="form-control" value="{{ $filters['bhp_rate'] ?? 0.5 }}">
                            </div>
                            <div class="form-group col-md-2">
                                <label for="uso_rate">Tarif USO (%)</label>
                                <input type="number" step="0.01" id="uso_rate" name="uso_rate" class="form-control" value="{{ $filters['uso_rate'] ?? 1.25 }}">
                            </div>
                            <div class="form-group col-md-3">
                                <label for="bad_debt_deduction">Potongan Piutang</label>
                                <input type="number" step="0.01" id="bad_debt_deduction" name="bad_debt_deduction" class="form-control" value="{{ $filters['bad_debt_deduction'] ?? 0 }}">
                            </div>
                            <div class="form-group col-md-3">
                                <label for="interconnection_deduction">Potongan Interkoneksi</label>
                                <input type="number" step="0.01" id="interconnection_deduction" name="interconnection_deduction" class="form-control" value="{{ $filters['interconnection_deduction'] ?? 0 }}">
                            </div>
                        </div>
                    @endif
                </form>
            </div>
        </div>

        <div class="alert alert-light border mt-3">
            Periode laporan: <strong>{{ $report['period']['label'] ?? '-' }}</strong>
        </div>

        @if(in_array($reportType, ['daily', 'period'], true))
            <div class="row">
                <div class="col-md-4">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>Rp {{ number_format($summary['total_income'] ?? 0, 0, ',', '.') }}</h3>
                            <p>Total Pendapatan</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>Rp {{ number_format($summary['customer_income'] ?? 0, 0, ',', '.') }}</h3>
                            <p>Customer</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3>Rp {{ number_format($summary['voucher_income'] ?? 0, 0, ',', '.') }}</h3>
                            <p>Voucher</p>
                        </div>
                    </div>
                </div>
            </div>
        @elseif($reportType === 'expense')
            <div class="alert alert-warning">
                Total Pengeluaran:
                <strong>Rp {{ number_format($summary['total_expense'] ?? 0, 0, ',', '.') }}</strong>
                | Gateway:
                <strong>Rp {{ number_format($summary['gateway_expense'] ?? 0, 0, ',', '.') }}</strong>
                | Manual:
                <strong>Rp {{ number_format($summary['manual_expense'] ?? 0, 0, ',', '.') }}</strong>
                | BHP:
                <strong>Rp {{ number_format($summary['bhp_amount'] ?? 0, 0, ',', '.') }}</strong>
                | USO:
                <strong>Rp {{ number_format($summary['uso_amount'] ?? 0, 0, ',', '.') }}</strong>
            </div>
        @elseif($reportType === 'profit_loss')
            <div class="alert alert-info">
                Pendapatan Kotor:
                <strong>Rp {{ number_format($summary['gross_revenue'] ?? 0, 0, ',', '.') }}</strong>
                | Total Biaya:
                <strong>Rp {{ number_format($summary['total_expense'] ?? 0, 0, ',', '.') }}</strong>
                | Laba Bersih:
                <strong>Rp {{ number_format($summary['net_profit'] ?? 0, 0, ',', '.') }}</strong>
            </div>
        @elseif($reportType === 'bhp_uso')
            <div class="alert alert-secondary">
                Dasar Pendapatan:
                <strong>Rp {{ number_format($summary['revenue_basis'] ?? 0, 0, ',', '.') }}</strong>
                | BHP:
                <strong>Rp {{ number_format($summary['bhp_amount'] ?? 0, 0, ',', '.') }}</strong>
                | USO:
                <strong>Rp {{ number_format($summary['uso_amount'] ?? 0, 0, ',', '.') }}</strong>
                | Total Kewajiban:
                <strong>Rp {{ number_format($summary['total_obligation'] ?? 0, 0, ',', '.') }}</strong>
            </div>
        @endif

        @if($reportType === 'expense')
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">Tambah Pengeluaran Manual</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('super-admin.reports.expenses.store') }}">
                        @csrf
                        <div class="form-row">
                            <div class="form-group col-md-2">
                                <label for="expense_date">Tanggal</label>
                                <input type="date" id="expense_date" name="expense_date" class="form-control" value="{{ now()->toDateString() }}">
                            </div>
                            <div class="form-group col-md-3">
                                <label for="category">Kategori</label>
                                <input type="text" id="category" name="category" class="form-control" placeholder="Contoh: Gaji Teknisi">
                            </div>
                            <div class="form-group col-md-2">
                                <label for="expense_service_type">Service</label>
                                <select id="expense_service_type" name="service_type" class="form-control">
                                    <option value="general">General</option>
                                    <option value="pppoe">PPPoE</option>
                                    <option value="hotspot">Hotspot</option>
                                    <option value="voucher">Voucher</option>
                                </select>
                            </div>
                            <div class="form-group col-md-2">
                                <label for="amount">Nominal</label>
                                <input type="number" id="amount" name="amount" class="form-control" min="1" step="0.01">
                            </div>
                            <div class="form-group col-md-3">
                                <label for="payment_method">Metode Bayar</label>
                                <input type="text" id="payment_method" name="payment_method" class="form-control" placeholder="cash / transfer">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label for="reference">Referensi</label>
                                <input type="text" id="reference" name="reference" class="form-control">
                            </div>
                            <div class="form-group col-md-7">
                                <label for="description">Keterangan</label>
                                <input type="text" id="description" name="description" class="form-control">
                            </div>
                            <div class="form-group col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary btn-block">Simpan</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Detail Laporan</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Referensi</th>
                                <th>Kategori</th>
                                <th>Tipe</th>
                                <th class="text-right">Jumlah (IDR)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($report['items'] as $item)
                                <tr>
                                    <td>{{ $item['time'] ?? '-' }}</td>
                                    <td>{{ $item['reference'] ?? '-' }}</td>
                                    <td>{{ $item['category'] ?? '-' }}</td>
                                    <td>{{ strtoupper((string) ($item['service'] ?? $item['expense_type'] ?? '-')) }}</td>
                                    <td class="text-right">{{ number_format((float) ($item['amount'] ?? 0), 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Belum ada data untuk filter ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
