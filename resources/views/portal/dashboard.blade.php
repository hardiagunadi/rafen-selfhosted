@extends('portal.layout')

@section('title', 'Dashboard Portal')

@section('content')
    <div class="mb-3">
        <h1 class="h3 mb-1">Halo, {{ $pppUser->customer_name ?: $pppUser->username }}</h1>
        <p class="text-muted mb-0">Ringkasan layanan internet Anda.</p>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h3 class="card-title mb-0">Informasi Akun</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tr>
                            <th class="pl-3" style="width: 150px;">Status</th>
                            <td>
                                <span class="badge {{ $pppUser->status_akun === 'enable' ? 'badge-success' : ($pppUser->status_akun === 'isolir' ? 'badge-warning' : 'badge-secondary') }}">
                                    {{ strtoupper($pppUser->status_akun) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th class="pl-3">ID Pelanggan</th>
                            <td>{{ $pppUser->customer_id ?: '-' }}</td>
                        </tr>
                        <tr>
                            <th class="pl-3">Username</th>
                            <td>{{ $pppUser->username }}</td>
                        </tr>
                        <tr>
                            <th class="pl-3">Paket</th>
                            <td>{{ $pppUser->profile?->name ?: '-' }}</td>
                        </tr>
                        <tr>
                            <th class="pl-3">Nomor HP</th>
                            <td>{{ $pppUser->nomor_hp ?: '-' }}</td>
                        </tr>
                        <tr>
                            <th class="pl-3">Jatuh Tempo</th>
                            <td>{{ $pppUser->jatuh_tempo?->format('d-m-Y') ?: '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h3 class="card-title mb-0">Tagihan Terakhir</h3>
                </div>
                <div class="card-body">
                    @if($latestInvoice)
                        <table class="table table-sm mb-3">
                            <tr>
                                <th style="width: 140px;">No. Invoice</th>
                                <td>{{ $latestInvoice->invoice_number }}</td>
                            </tr>
                            <tr>
                                <th>Total</th>
                                <td><strong>Rp {{ number_format($latestInvoice->total, 0, ',', '.') }}</strong></td>
                            </tr>
                            <tr>
                                <th>Jatuh Tempo</th>
                                <td>{{ $latestInvoice->due_date?->format('d/m/Y') ?: '-' }}</td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    <span class="badge {{ $latestInvoice->status === 'paid' ? 'badge-success' : 'badge-warning' }}">
                                        {{ $latestInvoice->status === 'paid' ? 'LUNAS' : 'BELUM BAYAR' }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                        <a href="{{ route('portal.invoices') }}" class="btn btn-primary btn-block">Lihat Semua Tagihan</a>
                    @else
                        <p class="text-muted text-center py-4 mb-0">Belum ada tagihan untuk akun ini.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Monitor Trafik</h3>
            <button type="button" id="btnToggleTraffic" class="btn btn-outline-primary btn-sm">Cek Trafik</button>
        </div>
        <div id="trafficPanel" class="card-body" style="display: none;">
            <div id="trafficStatus" class="alert alert-light border mb-3">Menyiapkan monitor trafik...</div>
            <div class="row text-center">
                <div class="col-md-3 col-6 mb-3">
                    <div class="text-muted small">Download</div>
                    <div id="trafficRx" class="h5 mb-0 text-success">-</div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="text-muted small">Upload</div>
                    <div id="trafficTx" class="h5 mb-0 text-primary">-</div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="text-muted small">Total Download</div>
                    <div id="trafficBytesIn" class="font-weight-bold">-</div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="text-muted small">Total Upload</div>
                    <div id="trafficBytesOut" class="font-weight-bold">-</div>
                </div>
            </div>
            <div class="small text-muted mt-2">
                <span id="trafficMeta">Snapshot trafik diambil dari cache RADIUS self-hosted.</span>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-white">
            <h3 class="card-title mb-0">Ganti Nama & Password WiFi</h3>
        </div>
        <div class="card-body">
            <div id="wifiAlert" class="d-none alert mb-3"></div>
            @if($linkedCpeDevice)
                @php($currentSsid = data_get($linkedCpeDevice->cached_params, 'wifi_networks.0.ssid'))
                <div class="form-group">
                    <label for="wifiSsid">Nama WiFi</label>
                    <input id="wifiSsid" type="text" class="form-control" maxlength="32" value="{{ $currentSsid ?: '' }}" placeholder="Nama WiFi baru">
                </div>
                <div class="form-group">
                    <label for="wifiPassword">Password WiFi</label>
                    <input id="wifiPassword" type="password" class="form-control" maxlength="63" placeholder="Kosongkan jika tidak diubah">
                    <small class="text-muted">Minimal 8 karakter bila diisi.</small>
                </div>
                <button type="button" id="btnSaveWifi" class="btn btn-primary">Simpan Pengaturan WiFi</button>
            @else
                <div class="alert alert-warning mb-0">
                    Modem Anda belum terhubung ke sistem TR-069. Gunakan form bantuan di bawah untuk meminta teknisi mengubah WiFi.
                </div>
            @endif
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-white">
            <h3 class="card-title mb-0">Pengaduan / Bantuan</h3>
        </div>
        <div class="card-body">
            <div id="ticketAlert" class="d-none alert mb-3"></div>
            <div class="form-group">
                <label for="ticketType">Tipe Pengaduan</label>
                <select id="ticketType" class="form-control">
                    <option value="complaint">Komplain</option>
                    <option value="troubleshoot">Internet Bermasalah</option>
                    <option value="installation">Instalasi</option>
                    <option value="other">Lainnya</option>
                </select>
            </div>
            <div class="form-group">
                <label for="ticketSubject">Subjek</label>
                <input id="ticketSubject" type="text" class="form-control" placeholder="Contoh: Internet sering putus">
            </div>
            <div class="form-group">
                <label for="ticketMessage">Detail Keluhan</label>
                <textarea id="ticketMessage" class="form-control" rows="4" placeholder="Ceritakan masalah yang sedang Anda alami..."></textarea>
            </div>
            <button type="button" id="btnSubmitTicket" class="btn btn-warning">Kirim Pengaduan</button>
        </div>
    </div>
@endsection

@push('js')
    <script>
        (function () {
            var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            var trafficUrl = @json(route('portal.traffic'));
            var wifiUrl = @json(route('portal.wifi.update'));
            var ticketUrl = @json(route('portal.tickets.store'));
            var trafficTimer = null;
            var previousTrafficSample = null;

            function formatBits(bitsPerSecond) {
                if (bitsPerSecond >= 1000000) {
                    return (bitsPerSecond / 1000000).toFixed(2) + ' Mbps';
                }

                if (bitsPerSecond >= 1000) {
                    return (bitsPerSecond / 1000).toFixed(1) + ' Kbps';
                }

                return Math.max(bitsPerSecond, 0).toFixed(0) + ' bps';
            }

            function formatBytes(bytes) {
                if (bytes >= 1073741824) {
                    return (bytes / 1073741824).toFixed(2) + ' GB';
                }

                if (bytes >= 1048576) {
                    return (bytes / 1048576).toFixed(1) + ' MB';
                }

                if (bytes >= 1024) {
                    return (bytes / 1024).toFixed(1) + ' KB';
                }

                return bytes + ' B';
            }

            function showAlert(elementId, kind, message) {
                var element = document.getElementById(elementId);

                if (!element) {
                    return;
                }

                element.className = 'alert alert-' + kind + ' mb-3';
                element.textContent = message;
                element.classList.remove('d-none');
            }

            function requestJson(method, url, payload) {
                return window.fetch(url, {
                    method: method,
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: payload ? JSON.stringify(payload) : undefined,
                }).then(function (response) {
                    return response.json().then(function (json) {
                        return { ok: response.ok, status: response.status, json: json };
                    });
                });
            }

            function updateTrafficView(payload) {
                var status = document.getElementById('trafficStatus');
                var meta = document.getElementById('trafficMeta');

                if (!payload.is_active) {
                    if (status) {
                        status.className = 'alert alert-warning mb-3';
                        status.textContent = 'Sesi PPPoE sedang tidak aktif di cache RADIUS.';
                    }

                    previousTrafficSample = null;
                    document.getElementById('trafficRx').textContent = '-';
                    document.getElementById('trafficTx').textContent = '-';
                    document.getElementById('trafficBytesIn').textContent = '-';
                    document.getElementById('trafficBytesOut').textContent = '-';

                    if (meta) {
                        meta.textContent = 'Belum ada snapshot trafik aktif untuk akun ini.';
                    }

                    return;
                }

                var rxBitsPerSecond = 0;
                var txBitsPerSecond = 0;

                if (previousTrafficSample && payload.sampled_at && previousTrafficSample.sampled_at) {
                    var seconds = (payload.sampled_at - previousTrafficSample.sampled_at) / 1000;

                    if (seconds > 0) {
                        rxBitsPerSecond = ((payload.bytes_in - previousTrafficSample.bytes_in) * 8) / seconds;
                        txBitsPerSecond = ((payload.bytes_out - previousTrafficSample.bytes_out) * 8) / seconds;
                    }
                }

                previousTrafficSample = payload;

                if (status) {
                    status.className = 'alert alert-success mb-3';
                    status.textContent = 'Sesi aktif untuk username ' + payload.username + '.';
                }

                document.getElementById('trafficRx').textContent = formatBits(rxBitsPerSecond);
                document.getElementById('trafficTx').textContent = formatBits(txBitsPerSecond);
                document.getElementById('trafficBytesIn').textContent = formatBytes(payload.bytes_in || 0);
                document.getElementById('trafficBytesOut').textContent = formatBytes(payload.bytes_out || 0);

                if (meta) {
                    meta.textContent = 'Uptime: ' + (payload.uptime || '-') + ' • IP: ' + (payload.ipv4_address || '-') + ' • Snapshot terbaru dari cache RADIUS.';
                }
            }

            function fetchTraffic() {
                requestJson('GET', trafficUrl).then(function (result) {
                    updateTrafficView(result.json || {});
                }).catch(function () {
                    showAlert('trafficStatus', 'danger', 'Gagal mengambil snapshot trafik terbaru.');
                });
            }

            var trafficButton = document.getElementById('btnToggleTraffic');
            var trafficPanel = document.getElementById('trafficPanel');

            if (trafficButton && trafficPanel) {
                trafficButton.addEventListener('click', function () {
                    var isOpen = trafficPanel.style.display !== 'none';

                    if (isOpen) {
                        trafficPanel.style.display = 'none';
                        trafficButton.textContent = 'Cek Trafik';

                        if (trafficTimer) {
                            window.clearInterval(trafficTimer);
                            trafficTimer = null;
                        }

                        previousTrafficSample = null;

                        return;
                    }

                    trafficPanel.style.display = 'block';
                    trafficButton.textContent = 'Tutup Trafik';
                    fetchTraffic();
                    trafficTimer = window.setInterval(fetchTraffic, 5000);
                });
            }

            var wifiButton = document.getElementById('btnSaveWifi');

            if (wifiButton) {
                wifiButton.addEventListener('click', function () {
                    requestJson('POST', wifiUrl, {
                        ssid: document.getElementById('wifiSsid').value,
                        password: document.getElementById('wifiPassword').value,
                    }).then(function (result) {
                        showAlert('wifiAlert', result.ok ? 'success' : 'danger', result.json.message || 'Permintaan selesai.');
                    }).catch(function () {
                        showAlert('wifiAlert', 'danger', 'Gagal mengirim perubahan WiFi ke server.');
                    });
                });
            }

            var ticketButton = document.getElementById('btnSubmitTicket');

            if (ticketButton) {
                ticketButton.addEventListener('click', function () {
                    requestJson('POST', ticketUrl, {
                        type: document.getElementById('ticketType').value,
                        subject: document.getElementById('ticketSubject').value,
                        message: document.getElementById('ticketMessage').value,
                    }).then(function (result) {
                        var message = result.json.message || 'Permintaan selesai.';

                        if (result.ok && result.json.ticket && result.json.ticket.public_url) {
                            message += ' Pantau progres di: ' + result.json.ticket.public_url;
                            document.getElementById('ticketSubject').value = '';
                            document.getElementById('ticketMessage').value = '';
                        }

                        showAlert('ticketAlert', result.ok ? 'success' : 'danger', message);
                    }).catch(function () {
                        showAlert('ticketAlert', 'danger', 'Gagal mengirim pengaduan Anda.');
                    });
                });
            }
        })();
    </script>
@endpush
