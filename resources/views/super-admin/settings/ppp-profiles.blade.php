@extends('layouts.admin')

@section('title', 'Paket PPP')

@section('content')
    <div class="container">
        <div class="mb-3">
            <h1 class="h3 mb-1">Paket PPP</h1>
            <p class="text-muted mb-0">Kelola paket langganan PPPoE dengan relasi ke profile group dan bandwidth secara global.</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Tambah Paket PPP</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('super-admin.settings.ppp-profiles.store') }}" method="POST">
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
                        <div class="form-group col-md-1">
                            <label for="masa_aktif">Durasi</label>
                            <input type="number" id="masa_aktif" name="masa_aktif" class="form-control" value="{{ old('masa_aktif', 1) }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="satuan">Satuan</label>
                            <select id="satuan" name="satuan" class="form-control">
                                @foreach(['bulan', 'hari', 'minggu', 'jam', 'menit'] as $satuan)
                                    <option value="{{ $satuan }}" @selected(old('satuan', 'bulan') === $satuan)>{{ ucfirst($satuan) }}</option>
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
                            <label for="harga_modal">Harga Modal</label>
                            <input type="number" step="0.01" id="harga_modal" name="harga_modal" class="form-control" value="{{ old('harga_modal', 0) }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="harga_promo">Harga Jual</label>
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
                @if($pppProfiles->isEmpty())
                    <div class="text-muted">Belum ada paket PPP.</div>
                @else
                    <div class="d-flex flex-column" style="gap: 1rem;">
                        @foreach($pppProfiles as $pppProfile)
                            <div class="border rounded p-3">
                                <form action="{{ route('super-admin.settings.ppp-profiles.update', $pppProfile) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-row">
                                        <div class="form-group col-md-3">
                                            <label>Nama</label>
                                            <input type="text" name="name" class="form-control" value="{{ $pppProfile->name }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Profile Group</label>
                                            <select name="profile_group_id" class="form-control">
                                                <option value="">- Opsional -</option>
                                                @foreach($profileGroups as $profileGroup)
                                                    <option value="{{ $profileGroup->id }}" @selected($pppProfile->profile_group_id === $profileGroup->id)>{{ $profileGroup->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Bandwidth</label>
                                            <select name="bandwidth_profile_id" class="form-control">
                                                <option value="">- Opsional -</option>
                                                @foreach($bandwidthProfiles as $bandwidthProfile)
                                                    <option value="{{ $bandwidthProfile->id }}" @selected($pppProfile->bandwidth_profile_id === $bandwidthProfile->id)>{{ $bandwidthProfile->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-1">
                                            <label>Durasi</label>
                                            <input type="number" name="masa_aktif" class="form-control" value="{{ $pppProfile->masa_aktif }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Satuan</label>
                                            <select name="satuan" class="form-control">
                                                @foreach(['bulan', 'hari', 'minggu', 'jam', 'menit'] as $satuan)
                                                    <option value="{{ $satuan }}" @selected($pppProfile->satuan === $satuan)>{{ ucfirst($satuan) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Parent Queue</label>
                                            <input type="text" name="parent_queue" class="form-control" value="{{ $pppProfile->parent_queue }}">
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-2">
                                            <label>Harga Modal</label>
                                            <input type="number" step="0.01" name="harga_modal" class="form-control" value="{{ $pppProfile->harga_modal }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Harga Jual</label>
                                            <input type="number" step="0.01" name="harga_promo" class="form-control" value="{{ $pppProfile->harga_promo }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>PPN</label>
                                            <input type="number" step="0.01" name="ppn" class="form-control" value="{{ $pppProfile->ppn }}">
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center" style="gap: 0.5rem;">
                                        <button type="submit" class="btn btn-outline-primary btn-sm">Simpan</button>
                                </form>
                                <form action="{{ route('super-admin.settings.ppp-profiles.destroy', $pppProfile) }}" method="POST" class="mb-0" onsubmit="return confirm('Hapus paket PPP ini?');">
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
