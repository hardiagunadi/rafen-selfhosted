@extends('layouts.admin')

@section('title', 'Paket Hotspot')

@section('content')
    <div class="container">
        <div class="mb-3">
            <h1 class="h3 mb-1">Paket Hotspot</h1>
            <p class="text-muted mb-0">Kelola paket hotspot secara global untuk instalasi self-hosted single-tenant.</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Tambah Paket Hotspot</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('super-admin.settings.hotspot-profiles.store') }}" method="POST">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="name">Nama</label>
                            <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="profile_group_id">Profile Group</label>
                            <select id="profile_group_id" name="profile_group_id" class="form-control">
                                <option value="">- Opsional -</option>
                                @foreach($profileGroups as $profileGroup)
                                    <option value="{{ $profileGroup->id }}" @selected((string) old('profile_group_id') === (string) $profileGroup->id)>{{ $profileGroup->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="bandwidth_profile_id">Bandwidth</label>
                            <select id="bandwidth_profile_id" name="bandwidth_profile_id" class="form-control">
                                <option value="">- Opsional -</option>
                                @foreach($bandwidthProfiles as $bandwidthProfile)
                                    <option value="{{ $bandwidthProfile->id }}" @selected((string) old('bandwidth_profile_id') === (string) $bandwidthProfile->id)>{{ $bandwidthProfile->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="profile_type">Tipe Profil</label>
                            <select id="profile_type" name="profile_type" class="form-control">
                                <option value="unlimited" @selected(old('profile_type', 'unlimited') === 'unlimited')>Unlimited</option>
                                <option value="limited" @selected(old('profile_type') === 'limited')>Limited</option>
                            </select>
                        </div>
                        <div class="form-group col-md-1">
                            <label for="shared_users">Shared</label>
                            <input type="number" id="shared_users" name="shared_users" class="form-control" value="{{ old('shared_users', 1) }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="prioritas">Prioritas</label>
                            <select id="prioritas" name="prioritas" class="form-control">
                                @foreach(['default', 'prioritas1', 'prioritas2', 'prioritas3', 'prioritas4', 'prioritas5', 'prioritas6', 'prioritas7', 'prioritas8'] as $prioritas)
                                    <option value="{{ $prioritas }}" @selected(old('prioritas', 'default') === $prioritas)>{{ ucfirst($prioritas) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-2">
                            <label for="masa_aktif_value">Masa Aktif</label>
                            <input type="number" id="masa_aktif_value" name="masa_aktif_value" class="form-control" value="{{ old('masa_aktif_value', 1) }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="masa_aktif_unit">Satuan</label>
                            <select id="masa_aktif_unit" name="masa_aktif_unit" class="form-control">
                                @foreach(['menit', 'jam', 'hari', 'bulan'] as $unit)
                                    <option value="{{ $unit }}" @selected(old('masa_aktif_unit', 'hari') === $unit)>{{ ucfirst($unit) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="limit_type">Limit Type</label>
                            <select id="limit_type" name="limit_type" class="form-control">
                                <option value="">- Tidak Dipakai -</option>
                                <option value="time" @selected(old('limit_type') === 'time')>Time</option>
                                <option value="quota" @selected(old('limit_type') === 'quota')>Quota</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="time_limit_value">Time Limit</label>
                            <input type="number" id="time_limit_value" name="time_limit_value" class="form-control" value="{{ old('time_limit_value') }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="time_limit_unit">Time Unit</label>
                            <select id="time_limit_unit" name="time_limit_unit" class="form-control">
                                <option value="">-</option>
                                @foreach(['menit', 'jam', 'hari', 'bulan'] as $unit)
                                    <option value="{{ $unit }}" @selected(old('time_limit_unit') === $unit)>{{ ucfirst($unit) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="parent_queue">Parent Queue</label>
                            <input type="text" id="parent_queue" name="parent_queue" class="form-control" value="{{ old('parent_queue') }}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-2">
                            <label for="quota_limit_value">Quota Limit</label>
                            <input type="number" step="0.01" id="quota_limit_value" name="quota_limit_value" class="form-control" value="{{ old('quota_limit_value') }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="quota_limit_unit">Quota Unit</label>
                            <select id="quota_limit_unit" name="quota_limit_unit" class="form-control">
                                <option value="">-</option>
                                <option value="mb" @selected(old('quota_limit_unit') === 'mb')>MB</option>
                                <option value="gb" @selected(old('quota_limit_unit') === 'gb')>GB</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="harga_jual">Harga Jual</label>
                            <input type="number" step="0.01" id="harga_jual" name="harga_jual" class="form-control" value="{{ old('harga_jual', 0) }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="harga_promo">Harga Promo</label>
                            <input type="number" step="0.01" id="harga_promo" name="harga_promo" class="form-control" value="{{ old('harga_promo', 0) }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="ppn">PPN</label>
                            <input type="number" step="0.01" id="ppn" name="ppn" class="form-control" value="{{ old('ppn', 11) }}">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title mb-0">Daftar Paket</h3>
            </div>
            <div class="card-body">
                @if($hotspotProfiles->isEmpty())
                    <div class="text-muted">Belum ada paket Hotspot.</div>
                @else
                    <div class="d-flex flex-column" style="gap: 1rem;">
                        @foreach($hotspotProfiles as $hotspotProfile)
                            <div class="border rounded p-3">
                                <form action="{{ route('super-admin.settings.hotspot-profiles.update', $hotspotProfile) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-row">
                                        <div class="form-group col-md-3">
                                            <label>Nama</label>
                                            <input type="text" name="name" class="form-control" value="{{ $hotspotProfile->name }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Profile Group</label>
                                            <select name="profile_group_id" class="form-control">
                                                <option value="">- Opsional -</option>
                                                @foreach($profileGroups as $profileGroup)
                                                    <option value="{{ $profileGroup->id }}" @selected($hotspotProfile->profile_group_id === $profileGroup->id)>{{ $profileGroup->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Bandwidth</label>
                                            <select name="bandwidth_profile_id" class="form-control">
                                                <option value="">- Opsional -</option>
                                                @foreach($bandwidthProfiles as $bandwidthProfile)
                                                    <option value="{{ $bandwidthProfile->id }}" @selected($hotspotProfile->bandwidth_profile_id === $bandwidthProfile->id)>{{ $bandwidthProfile->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Tipe Profil</label>
                                            <select name="profile_type" class="form-control">
                                                <option value="unlimited" @selected($hotspotProfile->profile_type === 'unlimited')>Unlimited</option>
                                                <option value="limited" @selected($hotspotProfile->profile_type === 'limited')>Limited</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-1">
                                            <label>Shared</label>
                                            <input type="number" name="shared_users" class="form-control" value="{{ $hotspotProfile->shared_users }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Prioritas</label>
                                            <select name="prioritas" class="form-control">
                                                @foreach(['default', 'prioritas1', 'prioritas2', 'prioritas3', 'prioritas4', 'prioritas5', 'prioritas6', 'prioritas7', 'prioritas8'] as $prioritas)
                                                    <option value="{{ $prioritas }}" @selected($hotspotProfile->prioritas === $prioritas)>{{ ucfirst($prioritas) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-2">
                                            <label>Masa Aktif</label>
                                            <input type="number" name="masa_aktif_value" class="form-control" value="{{ $hotspotProfile->masa_aktif_value }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Satuan</label>
                                            <select name="masa_aktif_unit" class="form-control">
                                                <option value="">-</option>
                                                @foreach(['menit', 'jam', 'hari', 'bulan'] as $unit)
                                                    <option value="{{ $unit }}" @selected($hotspotProfile->masa_aktif_unit === $unit)>{{ ucfirst($unit) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Limit Type</label>
                                            <select name="limit_type" class="form-control">
                                                <option value="">- Tidak Dipakai -</option>
                                                <option value="time" @selected($hotspotProfile->limit_type === 'time')>Time</option>
                                                <option value="quota" @selected($hotspotProfile->limit_type === 'quota')>Quota</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Time Limit</label>
                                            <input type="number" name="time_limit_value" class="form-control" value="{{ $hotspotProfile->time_limit_value }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Time Unit</label>
                                            <select name="time_limit_unit" class="form-control">
                                                <option value="">-</option>
                                                @foreach(['menit', 'jam', 'hari', 'bulan'] as $unit)
                                                    <option value="{{ $unit }}" @selected($hotspotProfile->time_limit_unit === $unit)>{{ ucfirst($unit) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Parent Queue</label>
                                            <input type="text" name="parent_queue" class="form-control" value="{{ $hotspotProfile->parent_queue }}">
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-2">
                                            <label>Quota Limit</label>
                                            <input type="number" step="0.01" name="quota_limit_value" class="form-control" value="{{ $hotspotProfile->quota_limit_value }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Quota Unit</label>
                                            <select name="quota_limit_unit" class="form-control">
                                                <option value="">-</option>
                                                <option value="mb" @selected($hotspotProfile->quota_limit_unit === 'mb')>MB</option>
                                                <option value="gb" @selected($hotspotProfile->quota_limit_unit === 'gb')>GB</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Harga Jual</label>
                                            <input type="number" step="0.01" name="harga_jual" class="form-control" value="{{ $hotspotProfile->harga_jual }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Harga Promo</label>
                                            <input type="number" step="0.01" name="harga_promo" class="form-control" value="{{ $hotspotProfile->harga_promo }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>PPN</label>
                                            <input type="number" step="0.01" name="ppn" class="form-control" value="{{ $hotspotProfile->ppn }}">
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center" style="gap: 0.5rem;">
                                        <button type="submit" class="btn btn-outline-primary btn-sm">Simpan</button>
                                </form>
                                <form action="{{ route('super-admin.settings.hotspot-profiles.destroy', $hotspotProfile) }}" method="POST" class="mb-0" onsubmit="return confirm('Hapus paket Hotspot ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                                </form>
                                    </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
