@extends('layouts.admin')

@section('title', 'CPE')

@section('content')
    <div class="container">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">Inventory CPE</h3>
                        <div class="d-flex" style="gap: 0.5rem;">
                            <span class="badge {{ $nbiStatus['online'] ? 'badge-success' : 'badge-danger' }}">
                                {{ $nbiStatus['online'] ? 'GenieACS Online' : 'GenieACS Offline' }}
                            </span>
                            <form action="{{ route('super-admin.settings.cpe.sync') }}" method="POST" class="mb-0">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm">Sync dari Radius</button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="text-muted">{{ $nbiStatus['message'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Link Device Manual</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('super-admin.settings.cpe.link') }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="radius_account_id">Akun Radius</label>
                                <select id="radius_account_id" name="radius_account_id" class="form-control @error('radius_account_id') is-invalid @enderror">
                                    <option value="">Pilih akun</option>
                                    @foreach($availableRadiusAccounts as $radiusAccount)
                                        <option value="{{ $radiusAccount->id }}" @selected(old('radius_account_id') == $radiusAccount->id)>
                                            {{ $radiusAccount->username }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('radius_account_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="device_id">Device ID GenieACS</label>
                                <input type="text" id="device_id" name="device_id" class="form-control @error('device_id') is-invalid @enderror" value="{{ old('device_id') }}" placeholder="AA11BB-ONU-12345678">
                                @error('device_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-outline-primary btn-sm">Hubungkan Device</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Device Belum Terhubung</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Device ID</th>
                                        <th>PPPoE</th>
                                        <th>Model</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($unlinkedDevices as $device)
                                        <tr>
                                            <td>{{ $device['device_id'] }}</td>
                                            <td>{{ $device['pppoe_username'] ?: '-' }}</td>
                                            <td>{{ trim(($device['manufacturer'] ?: '-').' '.($device['model'] ?: '')) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">Tidak ada device unlinked.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Device Tertaut</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Device</th>
                                        <th>Status</th>
                                        <th>Last Seen</th>
                                        <th class="text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($linkedDevices as $device)
                                        <tr>
                                            <td>
                                                <div class="font-weight-bold">{{ $device->radiusAccount?->username ?? '-' }}</div>
                                                <div class="small text-muted">{{ $device->serial_number ?: '-' }}</div>
                                            </td>
                                            <td>
                                                <div>{{ $device->manufacturer ?: '-' }} {{ $device->model ?: '' }}</div>
                                                <div class="small text-muted">{{ $device->genieacs_device_id }}</div>
                                            </td>
                                            <td>
                                                <span class="badge {{ $device->status === 'online' ? 'badge-success' : 'badge-secondary' }}">
                                                    {{ strtoupper($device->status ?: 'unknown') }}
                                                </span>
                                            </td>
                                            <td>{{ $device->last_seen_at?->diffForHumans() ?? '-' }}</td>
                                            <td class="text-right">
                                                <div class="d-flex justify-content-end" style="gap: 0.5rem;">
                                                    <form action="{{ route('super-admin.settings.cpe.reboot', $device) }}" method="POST" class="mb-0">
                                                        @csrf
                                                        <button type="submit" class="btn btn-outline-warning btn-sm">Reboot</button>
                                                    </form>
                                                    <form action="{{ route('super-admin.settings.cpe.destroy', $device) }}" method="POST" class="mb-0" onsubmit="return confirm('Hapus link device ini?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger btn-sm">Unlink</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Belum ada device CPE tertaut.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($linkedDevices->isNotEmpty())
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title mb-0">Operasi CPE</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @foreach($linkedDevices as $device)
                                    @php
                                        $firstWifi = $device->cached_params['wifi_networks'][0] ?? [];
                                        $wanConnections = $device->cached_params['wan_connections'] ?? [];
                                        $isWifiTarget = (string) old('wifi_device_id') === (string) $device->id;
                                        $isPppoeTarget = (string) old('pppoe_device_id') === (string) $device->id;
                                    @endphp
                                    <div class="col-xl-6 d-flex">
                                        <div class="card card-outline card-secondary flex-fill">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <div>
                                                    <div class="font-weight-bold">{{ $device->radiusAccount?->username ?? $device->genieacs_device_id }}</div>
                                                    <div class="small text-muted">{{ $device->manufacturer ?: '-' }} {{ $device->model ?: '' }}</div>
                                                </div>
                                                <form action="{{ route('super-admin.settings.cpe.refresh', $device) }}" method="POST" class="mb-0">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-primary btn-sm">Refresh</button>
                                                </form>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <h6 class="font-weight-bold">Update PPPoE</h6>
                                                        <form action="{{ route('super-admin.settings.cpe.update-pppoe', $device) }}" method="POST">
                                                            @csrf
                                                            <input type="hidden" name="pppoe_device_id" value="{{ $device->id }}">
                                                            <div class="form-group">
                                                                <label>Username</label>
                                                                <input
                                                                    type="text"
                                                                    name="username"
                                                                    class="form-control form-control-sm {{ $isPppoeTarget && $errors->has('username') ? 'is-invalid' : '' }}"
                                                                    value="{{ $isPppoeTarget ? old('username') : ($device->radiusAccount?->username ?? ($device->cached_params['pppoe_username'] ?? '')) }}"
                                                                >
                                                                @if($isPppoeTarget)
                                                                    @error('username')
                                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                                    @enderror
                                                                @endif
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Password</label>
                                                                <input
                                                                    type="text"
                                                                    name="password"
                                                                    class="form-control form-control-sm {{ $isPppoeTarget && $errors->has('password') ? 'is-invalid' : '' }}"
                                                                    value="{{ $isPppoeTarget ? old('password') : ($device->radiusAccount?->password ?? '') }}"
                                                                >
                                                                @if($isPppoeTarget)
                                                                    @error('password')
                                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                                    @enderror
                                                                @endif
                                                            </div>
                                                            <button type="submit" class="btn btn-outline-success btn-sm" @disabled(! $device->radiusAccount)>
                                                                Update PPPoE
                                                            </button>
                                                            @if(! $device->radiusAccount)
                                                                <div class="small text-muted mt-2">Hubungkan device ke akun Radius terlebih dahulu.</div>
                                                            @endif
                                                        </form>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h6 class="font-weight-bold">Update WiFi</h6>
                                                        <form action="{{ route('super-admin.settings.cpe.update-wifi', $device) }}" method="POST">
                                                            @csrf
                                                            <input type="hidden" name="wifi_device_id" value="{{ $device->id }}">
                                                            <div class="form-group">
                                                                <label>SSID</label>
                                                                <input
                                                                    type="text"
                                                                    name="ssid"
                                                                    class="form-control form-control-sm {{ $isWifiTarget && $errors->has('ssid') ? 'is-invalid' : '' }}"
                                                                    value="{{ $isWifiTarget ? old('ssid') : ($firstWifi['ssid'] ?? '') }}"
                                                                >
                                                                @if($isWifiTarget)
                                                                    @error('ssid')
                                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                                    @enderror
                                                                @endif
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Password</label>
                                                                <input
                                                                    type="text"
                                                                    name="password"
                                                                    class="form-control form-control-sm {{ $isWifiTarget && $errors->has('password') ? 'is-invalid' : '' }}"
                                                                    value="{{ $isWifiTarget ? old('password') : '' }}"
                                                                    placeholder="Kosongkan jika password tidak diubah"
                                                                >
                                                                @if($isWifiTarget)
                                                                    @error('password')
                                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                                    @enderror
                                                                @endif
                                                                <small class="text-muted">Isi hanya jika password WiFi juga ingin diubah.</small>
                                                            </div>
                                                            <button type="submit" class="btn btn-outline-info btn-sm">Update WiFi</button>
                                                        </form>
                                                    </div>
                                                </div>

                                                <div class="mt-3">
                                                    <h6 class="font-weight-bold">WAN Info</h6>
                                                    @if($wanConnections !== [])
                                                        <div class="table-responsive">
                                                            <table class="table table-sm mb-0">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Nama</th>
                                                                        <th>Username</th>
                                                                        <th>Status</th>
                                                                        <th>MAC</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach($wanConnections as $wanConnection)
                                                                        <tr>
                                                                            <td>{{ $wanConnection['name'] ?? ($wanConnection['type'] ?? '-') }}</td>
                                                                            <td>{{ $wanConnection['username'] ?? '-' }}</td>
                                                                            <td>{{ $wanConnection['status'] ?? '-' }}</td>
                                                                            <td>{{ $wanConnection['mac_address'] ?? '-' }}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    @else
                                                        <div class="text-muted small">Belum ada data WAN di cache device.</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
