@extends('layouts.admin')

@section('title', 'ODP')

@section('content')
    <div class="container-fluid">
        <div class="mb-3">
            <h1 class="h3 mb-1">Inventaris ODP</h1>
            <p class="text-muted mb-0">Kelola daftar ODP global untuk instalasi self-hosted single-tenant.</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Tambah ODP</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('super-admin.odps.store') }}" method="POST">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-2">
                            <label for="odp_code">Kode ODP</label>
                            <div class="input-group">
                                <input type="text" id="odp_code" name="code" class="form-control" value="{{ old('code') }}">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" id="generate-odp-code">Auto</button>
                                </div>
                            </div>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="odp_name">Nama ODP</label>
                            <input type="text" id="odp_name" name="name" class="form-control" value="{{ old('name') }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="odp_area">Area</label>
                            <input type="text" id="odp_area" name="area" class="form-control" value="{{ old('area') }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="odp_capacity_ports">Kapasitas Port</label>
                            <input type="number" id="odp_capacity_ports" name="capacity_ports" class="form-control" value="{{ old('capacity_ports', 8) }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="odp_status">Status</label>
                            <select id="odp_status" name="status" class="form-control">
                                <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                                <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                                <option value="maintenance" @selected(old('status') === 'maintenance')>Maintenance</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="odp_latitude">Latitude</label>
                            <input type="text" id="odp_latitude" name="latitude" class="form-control" value="{{ old('latitude') }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="odp_longitude">Longitude</label>
                            <input type="text" id="odp_longitude" name="longitude" class="form-control" value="{{ old('longitude') }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="odp_notes">Catatan</label>
                            <textarea id="odp_notes" name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan ODP</button>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title mb-0">Daftar ODP</h3>
            </div>
            <div class="card-body">
                @if($odps->isEmpty())
                    <div class="text-muted">Belum ada ODP.</div>
                @else
                    <div class="d-flex flex-column" style="gap: 1rem;">
                        @foreach($odps as $odp)
                            <div class="border rounded p-3">
                                <form action="{{ route('super-admin.odps.update', $odp) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-row">
                                        <div class="form-group col-md-2">
                                            <label>Kode ODP</label>
                                            <input type="text" name="code" class="form-control" value="{{ $odp->code }}">
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label>Nama ODP</label>
                                            <input type="text" name="name" class="form-control" value="{{ $odp->name }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Area</label>
                                            <input type="text" name="area" class="form-control" value="{{ $odp->area }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Kapasitas Port</label>
                                            <input type="number" name="capacity_ports" class="form-control" value="{{ $odp->capacity_ports }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Status</label>
                                            <select name="status" class="form-control">
                                                <option value="active" @selected($odp->status === 'active')>Active</option>
                                                <option value="inactive" @selected($odp->status === 'inactive')>Inactive</option>
                                                <option value="maintenance" @selected($odp->status === 'maintenance')>Maintenance</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-1">
                                            <label>Pelanggan</label>
                                            <div class="form-control-plaintext">{{ $odp->ppp_users_count }}</div>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-3">
                                            <label>Latitude</label>
                                            <input type="text" name="latitude" class="form-control" value="{{ $odp->latitude }}">
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label>Longitude</label>
                                            <input type="text" name="longitude" class="form-control" value="{{ $odp->longitude }}">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Catatan</label>
                                            <textarea name="notes" class="form-control" rows="2">{{ $odp->notes }}</textarea>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center" style="gap: 0.5rem;">
                                        <button type="submit" class="btn btn-outline-primary btn-sm">Simpan</button>
                                </form>
                                <form action="{{ route('super-admin.odps.destroy', $odp) }}" method="POST" class="mb-0" onsubmit="return confirm('Hapus ODP ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                                </form>
                                        <span class="badge {{ $odp->status === 'active' ? 'badge-success' : ($odp->status === 'maintenance' ? 'badge-warning' : 'badge-secondary') }}">
                                            {{ strtoupper($odp->status) }}
                                        </span>
                                    </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function () {
            $('#generate-odp-code').on('click', function () {
                $.get(@json(route('super-admin.odps.generate-code')), {
                    area_name: $('#odp_area').val()
                }).done(function (response) {
                    $('#odp_code').val(response.code);
                });
            });
        });
    </script>
@endpush
