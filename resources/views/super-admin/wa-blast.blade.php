@extends('layouts.admin')

@section('title', 'WA Blast')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">WA Blast Single-Tenant</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-{{ $settings->is_enabled ? 'success' : 'warning' }}">
                            Gateway {{ $settings->is_enabled ? 'aktif' : 'belum aktif' }}.
                            @if (! $settings->is_enabled || $settings->resolvedAuthToken() === '')
                                Lengkapi dulu pengaturan di halaman WhatsApp Gateway sebelum mengirim blast.
                            @endif
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="blast-type">Tipe Pelanggan</label>
                                <select id="blast-type" class="form-control">
                                    <option value="ppp">PPPoE</option>
                                    <option value="hotspot">Hotspot</option>
                                    <option value="all">Semua</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="blast-status-akun">Status Akun</label>
                                <select id="blast-status-akun" class="form-control">
                                    <option value="">Semua Status</option>
                                    <option value="enable">Enable</option>
                                    <option value="disable">Disable</option>
                                    <option value="isolir">Isolir</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="blast-status-bayar">Status Bayar</label>
                                <select id="blast-status-bayar" class="form-control">
                                    <option value="">Semua Status</option>
                                    <option value="sudah_bayar">Sudah Bayar</option>
                                    <option value="belum_bayar">Belum Bayar</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6" id="ppp-profile-wrap">
                                <label for="blast-ppp-profile">Filter Paket PPP</label>
                                <select id="blast-ppp-profile" class="form-control">
                                    <option value="">Semua Paket PPP</option>
                                    @foreach ($pppProfiles as $profile)
                                        <option value="{{ $profile->id }}">{{ $profile->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6" id="hotspot-profile-wrap">
                                <label for="blast-hotspot-profile">Filter Paket Hotspot</label>
                                <select id="blast-hotspot-profile" class="form-control">
                                    <option value="">Semua Paket Hotspot</option>
                                    @foreach ($hotspotProfiles as $profile)
                                        <option value="{{ $profile->id }}">{{ $profile->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="blast-search">Cari Pelanggan Spesifik</label>
                            <div class="input-group">
                                <input id="blast-search" type="text" class="form-control" placeholder="Nama, customer ID, username, atau nomor HP">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" onclick="searchBlastRecipients()">Cari</button>
                                </div>
                            </div>
                            <small class="text-muted">Kosongkan untuk kirim berdasarkan filter umum.</small>
                        </div>

                        <div id="blast-search-results" class="list-group mb-3"></div>

                        <div class="form-group">
                            <label>Penerima Spesifik Terpilih</label>
                            <div id="blast-selected-recipients"></div>
                        </div>

                        <div class="form-group">
                            <label for="blast-message">Pesan</label>
                            <textarea id="blast-message" class="form-control" rows="5" placeholder="Tulis pesan broadcast di sini"></textarea>
                            <small class="text-muted"><span id="blast-message-count">0</span> karakter</small>
                        </div>

                        <div class="d-flex flex-wrap" style="gap: 0.5rem;">
                            <button type="button" class="btn btn-info" onclick="previewBlast()">Preview Penerima</button>
                            <button type="button" class="btn btn-success" id="blast-send-button" onclick="sendBlast()">Kirim Blast</button>
                        </div>

                        <div id="blast-preview" class="mt-3"></div>
                        <div id="blast-result" class="mt-3"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Log Blast Terbaru</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Pelanggan</th>
                                        <th>Nomor</th>
                                        <th>Status</th>
                                        <th>Waktu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($recentLogs as $log)
                                        <tr>
                                            <td>{{ $log->customer_name ?: '-' }}</td>
                                            <td>{{ $log->phone ?: '-' }}</td>
                                            <td>
                                                <span class="badge badge-{{ $log->status === 'sent' ? 'success' : 'danger' }}">
                                                    {{ $log->status }}
                                                </span>
                                            </td>
                                            <td>{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">Belum ada log blast.</td>
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

@push('scripts')
    <script>
        const blastRoutes = {
            preview: @json(route('super-admin.wa-blast.preview')),
            send: @json(route('super-admin.wa-blast.send')),
            customerOptions: @json(route('super-admin.wa-blast.customer-options')),
        };

        const selectedBlastRecipients = new Map();

        function blastAlert(targetId, message, type) {
            const target = document.getElementById(targetId);

            if (! target) {
                return;
            }

            target.innerHTML = '<div class="alert alert-' + type + ' mb-0">' + message + '</div>';
        }

        function blastParams() {
            return {
                type: document.getElementById('blast-type').value,
                status_akun: document.getElementById('blast-status-akun').value,
                status_bayar: document.getElementById('blast-status-bayar').value,
                ppp_profile_id: document.getElementById('blast-ppp-profile').value,
                hotspot_profile_id: document.getElementById('blast-hotspot-profile').value,
                recipient_keys: Array.from(selectedBlastRecipients.keys()),
            };
        }

        function blastQueryString(params) {
            const query = new URLSearchParams();

            Object.entries(params).forEach(function ([key, value]) {
                if (Array.isArray(value)) {
                    value.forEach(function (item) {
                        query.append(key + '[]', item);
                    });

                    return;
                }

                if (value !== null && value !== '') {
                    query.append(key, value);
                }
            });

            return query.toString();
        }

        function renderSelectedBlastRecipients() {
            const target = document.getElementById('blast-selected-recipients');

            if (! target) {
                return;
            }

            if (selectedBlastRecipients.size === 0) {
                target.innerHTML = '<span class="text-muted small">Belum ada penerima spesifik yang dipilih.</span>';

                return;
            }

            target.innerHTML = '';

            selectedBlastRecipients.forEach(function (label, key) {
                const pill = document.createElement('span');
                pill.className = 'badge badge-success mr-2 mb-2';
                pill.textContent = label + ' ';
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-xs btn-link text-white p-0 ml-1';
                button.textContent = '×';
                button.onclick = function () {
                    removeBlastRecipient(key);
                };
                pill.appendChild(button);
                target.appendChild(pill);
            });
        }

        function removeBlastRecipient(key) {
            selectedBlastRecipients.delete(key);
            renderSelectedBlastRecipients();
        }

        function updateBlastProfileVisibility() {
            const type = document.getElementById('blast-type').value;
            document.getElementById('ppp-profile-wrap').style.display = type === 'hotspot' ? 'none' : '';
            document.getElementById('hotspot-profile-wrap').style.display = type === 'ppp' ? 'none' : '';
        }

        function searchBlastRecipients() {
            const keyword = document.getElementById('blast-search').value.trim();
            const type = document.getElementById('blast-type').value;
            const target = document.getElementById('blast-search-results');

            if (keyword.length < 2) {
                blastAlert('blast-result', 'Ketik minimal 2 karakter untuk mencari pelanggan.', 'warning');

                return;
            }

            target.innerHTML = '<div class="list-group-item text-muted">Mencari pelanggan...</div>';

            fetch(blastRoutes.customerOptions + '?' + blastQueryString({q: keyword, type: type}), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    const results = data.results || [];

                    if (results.length === 0) {
                        target.innerHTML = '<div class="list-group-item text-muted">Tidak ada pelanggan yang cocok.</div>';

                        return;
                    }

                    target.innerHTML = '';

                    results.forEach(function (result) {
                        const item = document.createElement('button');
                        item.type = 'button';
                        item.className = 'list-group-item list-group-item-action';
                        item.textContent = result.text;
                        item.onclick = function () {
                            selectedBlastRecipients.set(result.id, result.text);
                            renderSelectedBlastRecipients();
                        };
                        target.appendChild(item);
                    });
                })
                .catch(function () {
                    blastAlert('blast-result', 'Gagal mencari pelanggan.', 'danger');
                });
        }

        function previewBlast() {
            blastAlert('blast-preview', 'Memuat preview penerima...', 'info');

            fetch(blastRoutes.preview + '?' + blastQueryString(blastParams()), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    const count = Number(data.count || 0);
                    const recipients = (data.recipients || []).slice(0, 8).map(function (recipient) {
                        return recipient.name + ' (' + recipient.phone + ')';
                    });
                    const suffix = (data.recipients || []).length > 8 ? '<br><small>Masih ada penerima lain di luar preview singkat ini.</small>' : '';
                    const preview = recipients.length > 0 ? '<br><small>' + recipients.join('<br>') + '</small>' + suffix : '';

                    blastAlert('blast-preview', '<strong>' + count + ' penerima</strong> cocok dengan filter.' + preview, 'info');
                })
                .catch(function () {
                    blastAlert('blast-preview', 'Gagal memuat preview penerima.', 'danger');
                });
        }

        function sendBlast() {
            const message = document.getElementById('blast-message').value.trim();
            const button = document.getElementById('blast-send-button');

            if (message === '') {
                blastAlert('blast-result', 'Pesan blast tidak boleh kosong.', 'warning');

                return;
            }

            button.disabled = true;
            blastAlert('blast-result', 'Sedang mengirim blast...', 'info');

            fetch(blastRoutes.send, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify(Object.assign(blastParams(), {message: message})),
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    const type = data.success ? 'success' : 'danger';
                    const failed = (data.results || []).filter(function (item) {
                        return item.status !== true;
                    });
                    const failedHtml = failed.length > 0
                        ? '<hr><small>' + failed.map(function (item) {
                            return (item.phone || '-') + ': ' + (item.reason || 'Gagal terkirim');
                        }).join('<br>') + '</small>'
                        : '';

                    blastAlert('blast-result', data.message + failedHtml, type);
                })
                .catch(function () {
                    blastAlert('blast-result', 'Terjadi kesalahan saat mengirim blast.', 'danger');
                })
                .finally(function () {
                    button.disabled = false;
                });
        }

        document.getElementById('blast-type').addEventListener('change', updateBlastProfileVisibility);
        document.getElementById('blast-message').addEventListener('input', function () {
            document.getElementById('blast-message-count').textContent = this.value.length;
        });

        updateBlastProfileVisibility();
        renderSelectedBlastRecipients();
    </script>
@endpush
