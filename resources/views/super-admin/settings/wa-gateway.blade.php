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
                            <dd class="col-sm-7" id="wa-service-status">{{ $serviceStatus['pm2_status'] ?? '-' }}</dd>
                            <dt class="col-sm-5">URL</dt>
                            <dd class="col-sm-7">{{ $serviceStatus['url'] ?? '-' }}</dd>
                            <dt class="col-sm-5">PM2 Home</dt>
                            <dd class="col-sm-7">{{ $serviceStatus['pm2_home'] ?? '-' }}</dd>
                            <dt class="col-sm-5">Log</dt>
                            <dd class="col-sm-7">{{ $serviceStatus['log_file'] ?? '-' }}</dd>
                        </dl>
                        <div id="wa-service-result" class="mb-3"></div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm mr-2 mb-2" onclick="controlWaService('status')">Refresh Status</button>
                            <button type="button" class="btn btn-success btn-sm mr-2 mb-2" onclick="controlWaService('ensure-running')">Ensure Running</button>
                            <button type="button" class="btn btn-warning btn-sm mb-2" onclick="controlWaService('restart')">Restart Service</button>
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
                                            <td id="device-status-{{ $device->id }}">{{ $device->last_status ?: ($device->is_active ? 'active' : 'inactive') }}</td>
                                            <td>{{ $device->wa_number ?: '-' }}</td>
                                            <td>{{ $device->is_default ? 'Ya' : 'Tidak' }}</td>
                                            <td class="text-right">
                                                <div class="d-flex justify-content-end flex-wrap">
                                                    <button type="button" class="btn btn-outline-secondary btn-xs mr-2 mb-1" onclick="controlDeviceSession({{ $device->id }}, 'status', @js($device->device_name))">Status</button>
                                                    <button type="button" class="btn btn-warning btn-xs mr-2 mb-1" onclick="controlDeviceSession({{ $device->id }}, 'restart', @js($device->device_name))">Restart</button>
                                                    <button type="button" class="btn btn-success btn-xs mr-2 mb-1" onclick="openQrModal({{ $device->id }}, @js($device->device_name))">Scan QR</button>
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

    <div class="modal fade" id="wa-qr-modal" tabindex="-1" aria-labelledby="wa-qr-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="wa-qr-modal-label">Scan QR WhatsApp</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="small text-muted mb-2">Device: <strong id="wa-qr-device-name">-</strong></div>
                    <div id="wa-qr-alert" class="mb-3"></div>
                    <div id="wa-qr-canvas-wrap" class="text-center d-none">
                        <div id="wa-qr-canvas" class="d-inline-block p-2 bg-white border rounded"></div>
                    </div>
                    <div id="wa-qr-countdown" class="small text-primary mt-2"></div>
                    <div id="wa-qr-meta" class="small text-muted mt-2"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-warning btn-sm" onclick="refreshDeviceQrStatus('restart')">Generate QR Baru</button>
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/qrcodejs/qrcode.min.js') }}"></script>
    <script>
        const waSessionRoutes = {
            service: @json(route('super-admin.settings.wa-gateway.service', '__ACTION__')),
            deviceSession: @json(route('super-admin.settings.wa-gateway.devices.session', ['device' => '__DEVICE__', 'action' => '__ACTION__'])),
        };

        const waQrState = {
            deviceId: null,
            deviceName: '',
            pollTimer: null,
        };

        function parseJsonResponse(response) {
            return response.text().then(function (text) {
                try {
                    return JSON.parse(text);
                } catch (error) {
                    if (text.trim().startsWith('<')) {
                        throw new Error('Endpoint mengembalikan HTML, bukan JSON.');
                    }

                    throw new Error('Respons JSON tidak valid.');
                }
            });
        }

        function setInlineAlert(targetId, message, type) {
            const target = document.getElementById(targetId);

            if (! target) {
                return;
            }

            target.innerHTML = '<div class="alert alert-' + type + ' py-2 px-3 mb-0">' + message + '</div>';
        }

        function controlWaService(action) {
            const route = waSessionRoutes.service.replace('__ACTION__', action);

            setInlineAlert('wa-service-result', 'Memproses aksi service...', 'info');

            fetch(route, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({}),
            })
                .then(parseJsonResponse)
                .then(function (data) {
                    if (! data.success) {
                        throw new Error(data.message || 'Aksi service gagal.');
                    }

                    const serviceStatus = document.getElementById('wa-service-status');

                    if (serviceStatus && data.data && data.data.pm2_status) {
                        serviceStatus.textContent = data.data.pm2_status;
                    }

                    setInlineAlert('wa-service-result', data.message || 'Aksi service berhasil.', 'success');
                })
                .catch(function (error) {
                    setInlineAlert('wa-service-result', error.message, 'danger');
                });
        }

        function controlDeviceSession(deviceId, action, deviceName) {
            const route = waSessionRoutes.deviceSession
                .replace('__DEVICE__', String(deviceId))
                .replace('__ACTION__', action);

            setInlineAlert('wa-service-result', 'Memproses sesi untuk ' + deviceName + '...', 'info');

            fetch(route, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({}),
            })
                .then(parseJsonResponse)
                .then(function (data) {
                    if (! data.success) {
                        throw new Error(data.message || 'Aksi sesi device gagal.');
                    }

                    const statusCell = document.getElementById('device-status-' + deviceId);

                    if (statusCell && data.data && data.data.status) {
                        statusCell.textContent = data.data.status;
                    } else if (statusCell && action === 'restart') {
                        statusCell.textContent = 'restarting';
                    }

                    setInlineAlert('wa-service-result', data.message || 'Aksi sesi berhasil.', 'success');
                })
                .catch(function (error) {
                    setInlineAlert('wa-service-result', error.message, 'danger');
                });
        }

        function showQrAlert(message, type) {
            setInlineAlert('wa-qr-alert', message, type);
        }

        function clearQrCanvas() {
            const wrap = document.getElementById('wa-qr-canvas-wrap');
            const canvas = document.getElementById('wa-qr-canvas');
            const meta = document.getElementById('wa-qr-meta');
            const countdown = document.getElementById('wa-qr-countdown');

            if (wrap) {
                wrap.classList.add('d-none');
            }

            if (canvas) {
                canvas.innerHTML = '';
            }

            if (meta) {
                meta.textContent = '';
            }

            if (countdown) {
                countdown.textContent = '';
            }
        }

        function stopQrPolling() {
            if (waQrState.pollTimer) {
                clearInterval(waQrState.pollTimer);
                waQrState.pollTimer = null;
            }
        }

        function renderQrCode(qrText) {
            const wrap = document.getElementById('wa-qr-canvas-wrap');
            const canvas = document.getElementById('wa-qr-canvas');

            if (! wrap || ! canvas) {
                return;
            }

            canvas.innerHTML = '';
            wrap.classList.remove('d-none');

            if (window.QRCode) {
                new QRCode(canvas, {
                    text: qrText,
                    width: 260,
                    height: 260,
                });
            }
        }

        function refreshDeviceQrStatus(action = 'status') {
            if (! waQrState.deviceId) {
                return;
            }

            const route = waSessionRoutes.deviceSession
                .replace('__DEVICE__', String(waQrState.deviceId))
                .replace('__ACTION__', action);

            showQrAlert(action === 'restart' ? 'Meminta QR baru...' : 'Mengecek status sesi...', 'info');

            fetch(route, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({}),
            })
                .then(parseJsonResponse)
                .then(function (data) {
                    if (! data.success) {
                        throw new Error(data.message || 'Gagal membaca status QR device.');
                    }

                    const payload = data.data || {};
                    const status = String(payload.status || '').toLowerCase();
                    const qr = payload.qr || null;
                    const meta = document.getElementById('wa-qr-meta');
                    const statusCell = document.getElementById('device-status-' + waQrState.deviceId);

                    if (statusCell && status !== '') {
                        statusCell.textContent = status;
                    }

                    if (meta) {
                        meta.textContent = status !== '' ? 'Status: ' + status : 'Status: -';
                    }

                    if (status === 'connected') {
                        showQrAlert('Device sudah terhubung.', 'success');
                        stopQrPolling();

                        return;
                    }

                    if (qr) {
                        renderQrCode(String(qr));
                        showQrAlert('QR siap dipindai. Buka WhatsApp > Perangkat tertaut > Tautkan perangkat.', 'success');

                        return;
                    }

                    if (action === 'restart') {
                        showQrAlert('Sesi direstart. Menunggu QR baru muncul...', 'warning');

                        return;
                    }

                    showQrAlert('QR belum tersedia. Coba restart sesi atau generate QR baru.', 'warning');
                })
                .catch(function (error) {
                    showQrAlert(error.message, 'danger');
                });
        }

        function openQrModal(deviceId, deviceName) {
            waQrState.deviceId = Number(deviceId);
            waQrState.deviceName = String(deviceName || 'Device');

            const deviceNameEl = document.getElementById('wa-qr-device-name');

            if (deviceNameEl) {
                deviceNameEl.textContent = waQrState.deviceName;
            }

            clearQrCanvas();
            showQrAlert('Memuat status sesi dan QR...', 'info');

            if (window.jQuery) {
                window.jQuery('#wa-qr-modal').modal('show');
            }

            refreshDeviceQrStatus('status');
            stopQrPolling();

            waQrState.pollTimer = setInterval(function () {
                refreshDeviceQrStatus('status');
            }, 4000);
        }

        if (window.jQuery) {
            window.jQuery('#wa-qr-modal').on('hidden.bs.modal', function () {
                stopQrPolling();
                waQrState.deviceId = null;
                waQrState.deviceName = '';
                clearQrCanvas();
                document.getElementById('wa-qr-alert').innerHTML = '';
            });
        }
    </script>
@endpush
