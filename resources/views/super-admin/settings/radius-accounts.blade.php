@extends('layouts.admin')

@section('title', 'Radius Accounts')

@section('content')
    <div class="container">
        <div class="mb-3">
            <h1 class="h3 mb-1">Akun RADIUS</h1>
            <p class="text-muted mb-0">Kelola akun PPPoE dan Hotspot yang terhubung ke router MikroTik self-hosted.</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Tambah Akun RADIUS</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('super-admin.settings.radius-accounts.store') }}" method="POST">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-2">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username') }}">
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group col-md-2">
                            <label for="password">Password</label>
                            <input type="text" id="password" name="password" class="form-control @error('password') is-invalid @enderror" value="{{ old('password') }}">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group col-md-2">
                            <label for="service">Layanan</label>
                            <select id="service" name="service" class="form-control @error('service') is-invalid @enderror">
                                <option value="pppoe" @selected(old('service', 'pppoe') === 'pppoe')>PPPoE</option>
                                <option value="hotspot" @selected(old('service') === 'hotspot')>Hotspot</option>
                            </select>
                            @error('service')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group col-md-2">
                            <label for="ipv4_address">IP PPPoE</label>
                            <input type="text" id="ipv4_address" name="ipv4_address" class="form-control @error('ipv4_address') is-invalid @enderror" value="{{ old('ipv4_address') }}">
                            @error('ipv4_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group col-md-2">
                            <label for="rate_limit">Rate Limit</label>
                            <input type="text" id="rate_limit" name="rate_limit" class="form-control" value="{{ old('rate_limit') }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="profile">Profile</label>
                            <input type="text" id="profile" name="profile" class="form-control" value="{{ old('profile') }}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="mikrotik_connection_id">Koneksi MikroTik</label>
                            <select id="mikrotik_connection_id" name="mikrotik_connection_id" class="form-control @error('mikrotik_connection_id') is-invalid @enderror">
                                <option value="">- Opsional -</option>
                                @foreach($mikrotikConnections as $connection)
                                    <option value="{{ $connection->id }}" @selected((string) old('mikrotik_connection_id') === (string) $connection->id)>{{ $connection->name }}</option>
                                @endforeach
                            </select>
                            @error('mikrotik_connection_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group col-md-7">
                            <label for="notes">Catatan</label>
                            <input type="text" id="notes" name="notes" class="form-control" value="{{ old('notes') }}">
                        </div>
                        <div class="form-group col-md-1 d-flex align-items-end">
                            <div class="form-check">
                                <input type="checkbox" id="is_active" name="is_active" value="1" class="form-check-input" @checked(old('is_active', true))>
                                <label for="is_active" class="form-check-label">Aktif</label>
                            </div>
                        </div>
                        <div class="form-group col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-block">Simpan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title mb-0">Daftar Akun RADIUS</h3>
            </div>
            <div class="card-body">
                @if($radiusAccounts->isEmpty())
                    <div class="text-muted">Belum ada akun RADIUS.</div>
                @else
                    <div class="d-flex flex-column" style="gap: 1rem;">
                        @foreach($radiusAccounts as $radiusAccount)
                            <div class="border rounded p-3">
                                <form action="{{ route('super-admin.settings.radius-accounts.update', $radiusAccount) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-row">
                                        <div class="form-group col-md-2">
                                            <label>Username</label>
                                            <input type="text" name="username" class="form-control" value="{{ $radiusAccount->username }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Password</label>
                                            <input type="text" name="password" class="form-control" value="{{ $radiusAccount->password }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Layanan</label>
                                            <select name="service" class="form-control">
                                                <option value="pppoe" @selected($radiusAccount->service === 'pppoe')>PPPoE</option>
                                                <option value="hotspot" @selected($radiusAccount->service === 'hotspot')>Hotspot</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>IP PPPoE</label>
                                            <input type="text" name="ipv4_address" class="form-control" value="{{ $radiusAccount->ipv4_address }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Rate Limit</label>
                                            <input type="text" name="rate_limit" class="form-control" value="{{ $radiusAccount->rate_limit }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Profile</label>
                                            <input type="text" name="profile" class="form-control" value="{{ $radiusAccount->profile }}">
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-3">
                                            <label>Koneksi MikroTik</label>
                                            <select name="mikrotik_connection_id" class="form-control">
                                                <option value="">- Opsional -</option>
                                                @foreach($mikrotikConnections as $connection)
                                                    <option value="{{ $connection->id }}" @selected($radiusAccount->mikrotik_connection_id === $connection->id)>{{ $connection->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-7">
                                            <label>Catatan</label>
                                            <input type="text" name="notes" class="form-control" value="{{ $radiusAccount->notes }}">
                                        </div>
                                        <div class="form-group col-md-1 d-flex align-items-end">
                                            <div class="form-check">
                                                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active_radius_{{ $radiusAccount->id }}" @checked($radiusAccount->is_active)>
                                                <label class="form-check-label" for="is_active_radius_{{ $radiusAccount->id }}">Aktif</label>
                                            </div>
                                        </div>
                                        <div class="form-group col-md-1 d-flex align-items-end">
                                            <button type="submit" class="btn btn-outline-primary btn-block">Simpan</button>
                                        </div>
                                    </div>
                                </form>
                                <div class="mt-2 d-flex flex-wrap align-items-center" style="gap: 0.5rem;">
                                    <span class="badge {{ $radiusAccount->is_active ? 'badge-success' : 'badge-secondary' }}">
                                        {{ $radiusAccount->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                    <span class="small text-muted">
                                        Router: {{ $radiusAccount->mikrotikConnection?->name ?? '-' }}
                                    </span>
                                    <form action="{{ route('super-admin.settings.radius-accounts.destroy', $radiusAccount) }}" method="POST" class="mb-0" onsubmit="return confirm('Hapus akun RADIUS ini?');">
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
