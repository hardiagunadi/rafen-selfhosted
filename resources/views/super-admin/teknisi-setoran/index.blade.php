@extends('layouts.admin')

@section('title', 'Rekonsiliasi Nota Teknisi')

@section('content')
    <div class="container py-3">
        <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
            <div>
                <h1 class="h3 mb-1">Rekonsiliasi Nota Teknisi</h1>
                <p class="text-muted mb-0">Rekap pembayaran tunai yang dikoleksi teknisi lapangan.</p>
            </div>
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
                        <h2 class="h5 mb-0">Buat Setoran</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('teknisi-setoran.store') }}">
                            @csrf
                            @if(! auth()->user()->isSuperAdmin() && auth()->user()->role === \App\Models\User::ROLE_TEKNISI)
                                <div class="alert alert-info mb-3">Setoran akan dibuat untuk akun teknisi yang sedang login.</div>
                            @else
                                <div class="form-group">
                                    <label for="teknisi_id">Teknisi</label>
                                    <select id="teknisi_id" name="teknisi_id" class="form-control" required>
                                        <option value="">Pilih Teknisi</option>
                                        @foreach($teknisis as $teknisi)
                                            <option value="{{ $teknisi->id }}" @selected(old('teknisi_id') == $teknisi->id)>{{ $teknisi->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="form-group">
                                <label for="period_date">Tanggal Periode</label>
                                <input id="period_date" type="date" name="period_date" value="{{ old('period_date', now()->toDateString()) }}" class="form-control" required>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">Buat Setoran</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 mb-3">
                <div class="card">
                    <div class="card-header">
                        <form method="GET" action="{{ route('teknisi-setoran.index') }}" class="form-inline">
                            <select name="status" class="form-control form-control-sm mr-2">
                                <option value="">Semua Status</option>
                                <option value="draft" @selected($selectedStatus === 'draft')>Draft</option>
                                <option value="submitted" @selected($selectedStatus === 'submitted')>Disubmit</option>
                                <option value="verified" @selected($selectedStatus === 'verified')>Terverifikasi</option>
                            </select>
                            <button type="submit" class="btn btn-outline-secondary btn-sm">Filter</button>
                        </form>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Teknisi</th>
                                    <th class="text-center">Nota</th>
                                    <th class="text-right">Tagihan</th>
                                    <th class="text-right">Tunai</th>
                                    <th>Status</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($setorans as $setoran)
                                    <tr>
                                        <td>{{ $setoran->period_date?->format('Y-m-d') }}</td>
                                        <td>{{ $setoran->teknisi?->name ?? '-' }}</td>
                                        <td class="text-center">{{ $setoran->total_invoices }}</td>
                                        <td class="text-right">Rp {{ number_format((float) $setoran->total_tagihan, 0, ',', '.') }}</td>
                                        <td class="text-right text-success">Rp {{ number_format((float) $setoran->total_cash, 0, ',', '.') }}</td>
                                        <td>
                                            @if($setoran->status === 'draft')
                                                <span class="badge badge-secondary">Draft</span>
                                            @elseif($setoran->status === 'submitted')
                                                <span class="badge badge-warning">Disubmit</span>
                                            @else
                                                <span class="badge badge-success">Terverifikasi</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('teknisi-setoran.show', $setoran) }}" class="btn btn-info btn-sm">Detail</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">Belum ada setoran teknisi.</td>
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
