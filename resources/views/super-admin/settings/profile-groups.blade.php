@extends('layouts.admin')

@section('title', 'Profile Group')

@section('content')
    <div class="container">
        <div class="mb-3">
            <h1 class="h3 mb-1">Profile Group</h1>
            <p class="text-muted mb-0">Kelola grup profile IP pool untuk PPPoE dan hotspot di mode single-tenant.</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Tambah Profile Group</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('super-admin.settings.profile-groups.store') }}" method="POST">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="name">Nama</label>
                            <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="mikrotik_connection_id">Router</label>
                            <select id="mikrotik_connection_id" name="mikrotik_connection_id" class="form-control">
                                <option value="">Semua Router</option>
                                @foreach($mikrotikConnections as $mikrotikConnection)
                                    <option value="{{ $mikrotikConnection->id }}" @selected((string) old('mikrotik_connection_id') === (string) $mikrotikConnection->id)>{{ $mikrotikConnection->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="type">Tipe</label>
                            <select id="type" name="type" class="form-control">
                                <option value="pppoe" @selected(old('type', 'pppoe') === 'pppoe')>PPPoE</option>
                                <option value="hotspot" @selected(old('type') === 'hotspot')>Hotspot</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="ip_pool_mode">Mode Pool</label>
                            <select id="ip_pool_mode" name="ip_pool_mode" class="form-control">
                                <option value="group_only" @selected(old('ip_pool_mode', 'group_only') === 'group_only')>Group Only</option>
                                <option value="sql" @selected(old('ip_pool_mode') === 'sql')>SQL</option>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="ip_pool_name">Nama Pool</label>
                            <input type="text" id="ip_pool_name" name="ip_pool_name" class="form-control" value="{{ old('ip_pool_name') }}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-2">
                            <label for="ip_address">IP Address</label>
                            <input type="text" id="ip_address" name="ip_address" class="form-control" value="{{ old('ip_address') }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="netmask">Netmask</label>
                            <input type="text" id="netmask" name="netmask" class="form-control" value="{{ old('netmask') }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="range_start">Range Start</label>
                            <input type="text" id="range_start" name="range_start" class="form-control" value="{{ old('range_start') }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="range_end">Range End</label>
                            <input type="text" id="range_end" name="range_end" class="form-control" value="{{ old('range_end') }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="dns_servers">DNS Servers</label>
                            <input type="text" id="dns_servers" name="dns_servers" class="form-control" value="{{ old('dns_servers') }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="parent_queue">Parent Queue</label>
                            <input type="text" id="parent_queue" name="parent_queue" class="form-control" value="{{ old('parent_queue') }}">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title mb-0">Daftar Group</h3>
            </div>
            <div class="card-body">
                @if($profileGroups->isEmpty())
                    <div class="text-muted">Belum ada profile group.</div>
                @else
                    <div class="d-flex flex-column" style="gap: 1rem;">
                        @foreach($profileGroups as $profileGroup)
                            <div class="border rounded p-3">
                                <form action="{{ route('super-admin.settings.profile-groups.update', $profileGroup) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-row">
                                        <div class="form-group col-md-3">
                                            <label>Nama</label>
                                            <input type="text" name="name" class="form-control" value="{{ $profileGroup->name }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Router</label>
                                            <select name="mikrotik_connection_id" class="form-control">
                                                <option value="">Semua Router</option>
                                                @foreach($mikrotikConnections as $mikrotikConnection)
                                                    <option value="{{ $mikrotikConnection->id }}" @selected($profileGroup->mikrotik_connection_id === $mikrotikConnection->id)>{{ $mikrotikConnection->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Tipe</label>
                                            <select name="type" class="form-control">
                                                <option value="pppoe" @selected($profileGroup->type === 'pppoe')>PPPoE</option>
                                                <option value="hotspot" @selected($profileGroup->type === 'hotspot')>Hotspot</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Mode Pool</label>
                                            <select name="ip_pool_mode" class="form-control">
                                                <option value="group_only" @selected($profileGroup->ip_pool_mode === 'group_only')>Group Only</option>
                                                <option value="sql" @selected($profileGroup->ip_pool_mode === 'sql')>SQL</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label>Nama Pool</label>
                                            <input type="text" name="ip_pool_name" class="form-control" value="{{ $profileGroup->ip_pool_name }}">
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-2">
                                            <label>IP Address</label>
                                            <input type="text" name="ip_address" class="form-control" value="{{ $profileGroup->ip_address }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Netmask</label>
                                            <input type="text" name="netmask" class="form-control" value="{{ $profileGroup->netmask }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Range Start</label>
                                            <input type="text" name="range_start" class="form-control" value="{{ $profileGroup->range_start }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Range End</label>
                                            <input type="text" name="range_end" class="form-control" value="{{ $profileGroup->range_end }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>DNS Servers</label>
                                            <input type="text" name="dns_servers" class="form-control" value="{{ $profileGroup->dns_servers }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Parent Queue</label>
                                            <input type="text" name="parent_queue" class="form-control" value="{{ $profileGroup->parent_queue }}">
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center" style="gap: 0.5rem;">
                                        <button type="submit" class="btn btn-outline-primary btn-sm">Simpan</button>
                                </form>
                                <form action="{{ route('super-admin.settings.profile-groups.destroy', $profileGroup) }}" method="POST" class="mb-0" onsubmit="return confirm('Hapus profile group ini?');">
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
