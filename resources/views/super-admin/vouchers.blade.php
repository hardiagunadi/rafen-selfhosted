@extends('layouts.admin')

@section('title', 'Voucher')

@section('content')
    <div class="container-fluid">
        <div class="mb-3">
            <h1 class="h3 mb-1">Voucher Internal</h1>
            <p class="text-muted mb-0">Generate voucher hotspot secara global untuk instalasi self-hosted single-tenant.</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row">
            <div class="col-md-4">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $stats['unused'] }}</h3>
                        <p>Unused</p>
                    </div>
                    <div class="icon"><i class="fas fa-ticket-alt"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $stats['used'] }}</h3>
                        <p>Used</p>
                    </div>
                    <div class="icon"><i class="fas fa-wifi"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3>{{ $stats['expired'] }}</h3>
                        <p>Expired</p>
                    </div>
                    <div class="icon"><i class="fas fa-clock"></i></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Buat Batch Voucher</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('super-admin.vouchers.store') }}" method="POST">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="hotspot_profile_id">Paket Hotspot</label>
                            <select id="hotspot_profile_id" name="hotspot_profile_id" class="form-control">
                                <option value="">- Pilih Paket -</option>
                                @foreach($hotspotProfiles as $hotspotProfile)
                                    <option value="{{ $hotspotProfile->id }}" @selected((string) old('hotspot_profile_id') === (string) $hotspotProfile->id)>{{ $hotspotProfile->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="batch_name">Nama Batch</label>
                            <input type="text" id="batch_name" name="batch_name" class="form-control" value="{{ old('batch_name', 'VC-'.now()->format('Ymd-His')) }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="jumlah">Jumlah</label>
                            <input type="number" id="jumlah" name="jumlah" class="form-control" min="1" max="1000" value="{{ old('jumlah', 10) }}">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Generate Voucher</button>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Daftar Voucher</h3>
                    <form action="{{ route('super-admin.vouchers.index') }}" method="GET" class="form-inline" style="gap: 0.5rem;">
                        <select name="status" class="form-control form-control-sm">
                            <option value="">Semua Status</option>
                            @foreach(['unused', 'used', 'expired'] as $status)
                                <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ strtoupper($status) }}</option>
                            @endforeach
                        </select>
                        <select name="batch" class="form-control form-control-sm">
                            <option value="">Semua Batch</option>
                            @foreach($batches as $batch)
                                <option value="{{ $batch }}" @selected($selectedBatch === $batch)>{{ $batch }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-outline-secondary btn-sm">Filter</button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                @if($vouchers->isEmpty())
                    <div class="text-muted">Belum ada voucher yang cocok dengan filter.</div>
                @else
                    <div class="d-flex flex-wrap mb-3" style="gap: 0.5rem;">
                        @foreach($batches as $batch)
                            <a href="{{ route('super-admin.vouchers.print', ['batch' => $batch]) }}" class="btn btn-outline-dark btn-sm" target="_blank" rel="noopener">
                                Cetak {{ $batch }}
                            </a>
                        @endforeach
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-3">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">
                                        <input type="checkbox" id="toggle-all-vouchers">
                                    </th>
                                    <th>Kode</th>
                                    <th>Batch</th>
                                    <th>Paket</th>
                                    <th>Status</th>
                                    <th>Expired</th>
                                    <th style="width: 100px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($vouchers as $voucher)
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="ids[]" value="{{ $voucher->id }}" form="bulk-delete-form" @disabled(!$voucher->isUnused())>
                                        </td>
                                        <td><code>{{ $voucher->code }}</code></td>
                                        <td>{{ $voucher->batch_name ?: '-' }}</td>
                                        <td>{{ $voucher->hotspotProfile?->name ?: '-' }}</td>
                                        <td>
                                            <span class="badge {{ $voucher->status === 'unused' ? 'badge-success' : ($voucher->status === 'used' ? 'badge-info' : 'badge-secondary') }}">
                                                {{ strtoupper($voucher->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $voucher->expired_at?->format('Y-m-d H:i') ?: '-' }}</td>
                                        <td>
                                            <form action="{{ route('super-admin.vouchers.destroy', $voucher) }}" method="POST" onsubmit="return confirm('Hapus voucher ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-xs" @disabled(!$voucher->isUnused())>Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <form id="bulk-delete-form" action="{{ route('super-admin.vouchers.bulk-destroy') }}" method="POST" onsubmit="return confirm('Hapus voucher unused yang dipilih?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm">Hapus Voucher Terpilih</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function () {
            $('#toggle-all-vouchers').on('change', function () {
                $('input[name="ids[]"]:not(:disabled)').prop('checked', $(this).is(':checked'));
            });
        });
    </script>
@endpush
