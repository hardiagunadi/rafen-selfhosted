@extends('layouts.admin')

@section('title', 'WhatsApp Gateway')

@section('content')
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 mb-1">WhatsApp Gateway</h1>
                <p class="text-muted mb-0">Kelola koneksi gateway lokal, device session, dan kirim pesan test.</p>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Pengaturan Gateway</h3>
                    </div>
                    <form action="{{ route('super-admin.settings.wa-gateway.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="form-group">
                                <label for="business_name">Nama Bisnis</label>
                                <input id="business_name" type="text" name="business_name" class="form-control" value="{{ old('business_name', $settings->business_name) }}">
                            </div>
                            <div class="form-group">
                                <label for="business_phone">Nomor Bisnis</label>
                                <input id="business_phone" type="text" name="business_phone" class="form-control" value="{{ old('business_phone', $settings->business_phone) }}">
                            </div>
                            <div class="form-group">
                                <label for="default_test_recipient">Nomor Test Default</label>
                                <input id="default_test_recipient" type="text" name="default_test_recipient" class="form-control" value="{{ old('default_test_recipient', $settings->default_test_recipient) }}">
                            </div>
                            <div class="form-group">
                                <label for="gateway_url">Gateway URL</label>
                                <input id="gateway_url" type="url" name="gateway_url" class="form-control" value="{{ old('gateway_url', $settings->gateway_url ?: $settings->resolvedGatewayUrl()) }}">
                            </div>
                            <div class="form-group">
                                <label for="auth_token">Auth Token</label>
                                <input id="auth_token" type="text" name="auth_token" class="form-control" value="{{ old('auth_token', $settings->auth_token ?: $settings->resolvedAuthToken()) }}">
                            </div>
                            <div class="form-group">
                                <label for="master_key">Master Key</label>
                                <input id="master_key" type="text" name="master_key" class="form-control" value="{{ old('master_key', $settings->master_key ?: $settings->resolvedMasterKey()) }}">
                            </div>
                            <div class="custom-control custom-switch">
                                <input id="is_enabled" type="checkbox" name="is_enabled" value="1" class="custom-control-input" @checked(old('is_enabled', $settings->is_enabled))>
                                <label class="custom-control-label" for="is_enabled">Aktifkan modul WhatsApp Gateway</label>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Service Lokal</h3>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-3">
                            <dt class="col-sm-5">Status</dt>
                            <dd class="col-sm-7">{{ $serviceStatus['pm2_status'] ?? '-' }}</dd>
                            <dt class="col-sm-5">URL</dt>
                            <dd class="col-sm-7">{{ $serviceStatus['url'] ?? '-' }}</dd>
                            <dt class="col-sm-5">PM2 Home</dt>
                            <dd class="col-sm-7">{{ $serviceStatus['pm2_home'] ?? '-' }}</dd>
                            <dt class="col-sm-5">Log</dt>
                            <dd class="col-sm-7">{{ $serviceStatus['log_file'] ?? '-' }}</dd>
                        </dl>
                        <div class="d-flex flex-wrap gap-2">
                            <form action="{{ route('super-admin.settings.wa-gateway.service', 'status') }}" method="POST" class="mr-2 mb-2">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary btn-sm">Refresh Status</button>
                            </form>
                            <form action="{{ route('super-admin.settings.wa-gateway.service', 'ensure-running') }}" method="POST" class="mr-2 mb-2">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">Ensure Running</button>
                            </form>
                            <form action="{{ route('super-admin.settings.wa-gateway.service', 'restart') }}" method="POST" class="mb-2">
                                @csrf
                                <button type="submit" class="btn btn-warning btn-sm">Restart Service</button>
                            </form>
                        </div>
                        <form action="{{ route('super-admin.settings.wa-gateway.test-connection') }}" method="POST" class="mt-3">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary btn-sm">Tes Koneksi Gateway</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Kirim Pesan Test</h3>
                    </div>
                    <form action="{{ route('super-admin.settings.wa-gateway.test-message') }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="form-group">
                                <label for="device_id">Device</label>
                                <select id="device_id" name="device_id" class="form-control">
                                    <option value="">Gunakan device default</option>
                                    @foreach ($devices as $device)
                                        <option value="{{ $device->id }}">{{ $device->device_name }} ({{ $device->session_id }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="recipient_phone">Nomor Tujuan</label>
                                <input id="recipient_phone" type="text" name="recipient_phone" class="form-control" value="{{ old('recipient_phone', $settings->default_test_recipient ?: $settings->business_phone) }}">
                            </div>
                            <div class="form-group">
                                <label for="message">Pesan</label>
                                <textarea id="message" name="message" rows="4" class="form-control">{{ old('message') }}</textarea>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">Kirim Pesan Test</button>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Tambah Device</h3>
                    </div>
                    <form action="{{ route('super-admin.settings.wa-gateway.devices.store') }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="device_name">Nama Device</label>
                                    <input id="device_name" type="text" name="device_name" class="form-control" required>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="session_id">Session ID</label>
                                    <input id="session_id" type="text" name="session_id" class="form-control">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="wa_number">Nomor WA</label>
                                    <input id="wa_number" type="text" name="wa_number" class="form-control">
                                </div>
                            </div>
                            <div class="custom-control custom-switch">
                                <input id="device_is_active" type="checkbox" name="is_active" value="1" class="custom-control-input" checked>
                                <label class="custom-control-label" for="device_is_active">Device aktif</label>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">Tambah Device</button>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Daftar Device</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Device</th>
                                        <th>Session</th>
                                        <th>Status</th>
                                        <th>WA</th>
                                        <th>Default</th>
                                        <th class="text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($devices as $device)
                                        <tr>
                                            <td>{{ $device->device_name }}</td>
                                            <td><code>{{ $device->session_id }}</code></td>
                                            <td>{{ $device->last_status ?: ($device->is_active ? 'active' : 'inactive') }}</td>
                                            <td>{{ $device->wa_number ?: '-' }}</td>
                                            <td>{{ $device->is_default ? 'Ya' : 'Tidak' }}</td>
                                            <td class="text-right">
                                                <div class="d-flex justify-content-end flex-wrap">
                                                    <form action="{{ route('super-admin.settings.wa-gateway.devices.session', [$device, 'status']) }}" method="POST" class="mr-2 mb-1">
                                                        @csrf
                                                        <button type="submit" class="btn btn-outline-secondary btn-xs">Status</button>
                                                    </form>
                                                    <form action="{{ route('super-admin.settings.wa-gateway.devices.session', [$device, 'restart']) }}" method="POST" class="mr-2 mb-1">
                                                        @csrf
                                                        <button type="submit" class="btn btn-warning btn-xs">Restart</button>
                                                    </form>
                                                    @unless($device->is_default)
                                                        <form action="{{ route('super-admin.settings.wa-gateway.devices.default', $device) }}" method="POST" class="mr-2 mb-1">
                                                            @csrf
                                                            <button type="submit" class="btn btn-outline-primary btn-xs">Default</button>
                                                        </form>
                                                    @endunless
                                                    <form action="{{ route('super-admin.settings.wa-gateway.devices.destroy', $device) }}" method="POST" class="mb-1">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger btn-xs">Hapus</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">Belum ada device WhatsApp.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
