@extends('layouts.admin')

@section('title', 'Lisensi Sistem')

@section('content')
@php
    $license = $snapshot['license'];
    $appUrl = (string) config('app.url');
    $appUrlHost = (string) parse_url($appUrl, PHP_URL_HOST);
    $serverName = php_uname('n');
    $activationAccessMode = match (true) {
        $appUrlHost === '', in_array($appUrlHost, ['localhost', '127.0.0.1'], true) => 'fingerprint_only',
        filter_var($appUrlHost, FILTER_VALIDATE_IP) !== false => 'ip_based',
        default => 'domain_based',
    };
    $statusClass = match ($license->status) {
        'active' => 'success',
        'grace' => 'warning',
        'restricted', 'invalid', 'missing' => 'danger',
        default => 'secondary',
    };

    $formatAccessMode = static function (?string $value): string {
        return match ($value) {
            'fingerprint_only' => 'Fingerprint Only',
            'ip_based' => 'IP-Based',
            'domain_based' => 'Domain-Based',
            'hybrid' => 'Hybrid',
            null, '' => 'Belum Dicatat',
            default => ucwords(str_replace('_', ' ', $value)),
        };
    };

    $activationRequestPayload = app(\App\Services\LicenseActivationRequestService::class)->makePayload();
    $activationRequestJson = json_encode($activationRequestPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $operatorChecklist = [
        [
            'title' => 'Pastikan server sudah terpasang dan APP_URL sudah sesuai',
            'description' => 'Boleh tetap IP-based. Domain final tidak wajib ada di tahap awal.',
            'done' => $appUrl !== '',
        ],
        [
            'title' => 'Unduh atau copy activation request',
            'description' => 'Kirim activation request atau activation summary ke vendor/SaaS issuer.',
            'done' => true,
        ],
        [
            'title' => 'Minta issue lisensi dengan mode akses yang sesuai',
            'description' => 'Gunakan Fingerprint Only atau IP-Based bila domain final belum siap.',
            'done' => filled($license->license_id),
        ],
        [
            'title' => 'Upload file lisensi ke instance self-hosted ini',
            'description' => 'Format yang didukung: .json, .txt, atau .lic.',
            'done' => $snapshot['file_exists'],
        ],
        [
            'title' => 'Verifikasi lisensi sudah aktif',
            'description' => 'Pastikan status lisensi berubah menjadi Aktif atau Grace Period.',
            'done' => $snapshot['is_valid'],
        ],
    ];
@endphp
<div class="container-fluid">
    <div class="row mb-3 align-items-center">
        <div class="col">
            <h4 class="mb-0"><i class="fas fa-certificate mr-2 text-primary"></i>Lisensi Sistem</h4>
            <small class="text-muted">Kelola lisensi instance untuk deployment self-hosted Rafen.</small>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    @endif

    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><i class="fas fa-info-circle mr-1"></i> Status Lisensi</div>
                <div class="card-body">
                    <div class="mb-3">
                        <span class="badge badge-{{ $statusClass }} px-3 py-2">{{ $snapshot['status_label'] }}</span>
                        @if($snapshot['is_enforced'])
                            <span class="badge badge-primary px-3 py-2">Enforcement Aktif</span>
                        @else
                            <span class="badge badge-secondary px-3 py-2">Enforcement Nonaktif</span>
                        @endif
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <tbody>
                                <tr>
                                    <th class="w-25">License ID</th>
                                    <td>{{ $license->license_id ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Customer</th>
                                    <td>{{ $license->customer_name ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Instance</th>
                                    <td>{{ $license->instance_name ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Berlaku Sampai</th>
                                    <td>{{ $license->expires_at?->format('Y-m-d') ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Support Sampai</th>
                                    <td>{{ $license->support_until?->format('Y-m-d') ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Grace Period</th>
                                    <td>{{ $license->grace_days }} hari</td>
                                </tr>
                                <tr>
                                    <th>Verifikasi Terakhir</th>
                                    <td>{{ $license->last_verified_at?->format('Y-m-d H:i:s') ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Lokasi File</th>
                                    <td><code>{{ $snapshot['license_path'] }}</code></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    @if($license->validation_error)
                    <div class="alert alert-danger mt-3 mb-0">
                        <strong>Masalah lisensi:</strong> {{ $license->validation_error }}
                    </div>
                    @endif
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><i class="fas fa-tasks mr-1"></i> Operator Checklist</div>
                <div class="card-body">
                    <p class="text-muted mb-3">Gunakan checklist ini saat provisioning self-hosted baru atau saat aktivasi ulang lisensi.</p>
                    <div class="list-group list-group-flush">
                        @foreach($operatorChecklist as $index => $item)
                            <div class="list-group-item px-0">
                                <div class="d-flex align-items-start">
                                    <div class="mr-3 mt-1">
                                        @if($item['done'])
                                            <span class="badge badge-success px-2 py-2"><i class="fas fa-check"></i></span>
                                        @else
                                            <span class="badge badge-secondary px-2 py-2">{{ $index + 1 }}</span>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="font-weight-bold">{{ $item['title'] }}</div>
                                        <div class="text-muted small">{{ $item['description'] }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><i class="fas fa-fingerprint mr-1"></i> Fingerprint Server</div>
                <div class="card-body">
                    <p class="text-muted mb-2">Kirim activation request atau fingerprint ini ke vendor untuk penerbitan lisensi. Flow ini mendukung instalasi IP-based tanpa domain final.</p>
                    <div class="small text-muted mb-3">
                        <div>APP_URL saat ini: <code>{{ $appUrl }}</code></div>
                        <div>Host APP_URL: <code>{{ $appUrlHost ?: '-' }}</code></div>
                        <div>Server name: <code>{{ $serverName }}</code></div>
                    </div>
                    <textarea class="form-control" rows="3" readonly>{{ $snapshot['expected_fingerprint'] }}</textarea>
                    @php
                        $activationSummary = implode(PHP_EOL, [
                            'RAFEN Self-Hosted Activation Request',
                            'APP_URL: '.$appUrl,
                            'APP_URL Host: '.($appUrlHost ?: '-'),
                            'Server Name: '.$serverName,
                            'Access Mode: '.$formatAccessMode($activationAccessMode),
                            'Fingerprint: '.$snapshot['expected_fingerprint'],
                        ]);
                    @endphp
                    <div class="mt-3 d-flex flex-wrap" style="gap:.75rem;">
                        <a href="{{ route('super-admin.settings.license.activation-request') }}" class="btn btn-outline-primary">
                            <i class="fas fa-download mr-1"></i> Unduh Activation Request
                        </a>
                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            id="copyActivationSummaryBtn"
                            data-copy-text="{{ $activationSummary }}"
                        >
                            <i class="fas fa-copy mr-1"></i> Copy Activation Summary
                        </button>
                    </div>
                    <small class="form-text text-muted">Jika domain belum siap, biarkan vendor issue lisensi dengan mode fingerprint-only atau IP-based terlebih dahulu. Domain bisa dicatat kemudian saat deployment sudah stabil.</small>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><i class="fas fa-code mr-1"></i> Preview Activation Request</div>
                <div class="card-body">
                    <p class="text-muted mb-2">Preview ini sama dengan payload yang akan diunduh vendor dari tombol activation request.</p>
                    <textarea class="form-control" rows="12" readonly id="activationRequestPreview">{{ $activationRequestJson }}</textarea>
                    <div class="mt-3">
                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            id="copyActivationRequestJsonBtn"
                            data-copy-text="{{ $activationRequestJson }}"
                        >
                            <i class="fas fa-copy mr-1"></i> Copy Activation Request JSON
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><i class="fas fa-upload mr-1"></i> Upload Lisensi</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('super-admin.settings.license.update') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label>File Lisensi</label>
                            <div class="custom-file">
                                <input type="file" name="license_file" class="custom-file-input @error('license_file') is-invalid @enderror" id="licenseFileInput" required>
                                <label class="custom-file-label" for="licenseFileInput">Pilih file lisensi</label>
                                @error('license_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <small class="form-text text-muted">Format yang diterima: <code>.json</code>, <code>.txt</code>, atau <code>.lic</code>.</small>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Unggah dan Verifikasi
                        </button>
                    </form>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><i class="fas fa-layer-group mr-1"></i> Modul Lisensi</div>
                <div class="card-body">
                    @if(! empty($license->modules))
                        <div class="d-flex flex-wrap" style="gap:.5rem;">
                            @foreach($license->modules as $module)
                                <span class="badge badge-light border px-3 py-2">{{ $module }}</span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted mb-0">Belum ada data modul aktif.</p>
                    @endif
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><i class="fas fa-sliders-h mr-1"></i> Limit Lisensi</div>
                <div class="card-body">
                    @if(! empty($license->limits))
                        <table class="table table-sm mb-0">
                            <tbody>
                                @foreach($license->limits as $limitKey => $limitValue)
                                    <tr>
                                        <th>{{ $limitKey }}</th>
                                        <td>{{ $limitValue }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-muted mb-0">Belum ada data limit lisensi.</p>
                    @endif
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><i class="fas fa-globe mr-1"></i> Mode Akses Lisensi</div>
                <div class="card-body">
                    @php
                        $payload = $license->payload ?? [];
                        $accessMode = $payload['access_mode'] ?? null;
                        $allowedHosts = $payload['allowed_hosts'] ?? $license->domains ?? [];
                    @endphp
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr>
                                <th class="w-25">Mode</th>
                                <td>{{ $formatAccessMode($accessMode) }}</td>
                            </tr>
                            <tr>
                                <th>Host/IP</th>
                                <td>
                                    @if(! empty($allowedHosts) && is_array($allowedHosts))
                                        {{ implode(', ', $allowedHosts) }}
                                    @else
                                        <span class="text-muted">Belum ada host/IP yang dicatat. Lisensi tetap bisa aktif selama fingerprint cocok.</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('licenseFileInput')?.addEventListener('change', function () {
    const fileName = this.files?.[0]?.name || 'Pilih file lisensi';
    const label = this.nextElementSibling;
    if (label) {
        label.textContent = fileName;
    }
});

document.getElementById('copyActivationSummaryBtn')?.addEventListener('click', async function () {
    const originalHtml = this.innerHTML;
    const copyText = this.dataset.copyText || '';

    try {
        if (navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(copyText);
        } else {
            const tempInput = document.createElement('textarea');
            tempInput.value = copyText;
            tempInput.setAttribute('readonly', 'readonly');
            tempInput.style.position = 'absolute';
            tempInput.style.left = '-9999px';
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
        }

        this.innerHTML = '<i class="fas fa-check mr-1"></i> Tersalin';
        this.classList.remove('btn-outline-secondary');
        this.classList.add('btn-success');
    } catch (error) {
        this.innerHTML = '<i class="fas fa-times mr-1"></i> Gagal Copy';
        this.classList.remove('btn-outline-secondary');
        this.classList.add('btn-danger');
    }

    setTimeout(() => {
        this.innerHTML = originalHtml;
        this.classList.remove('btn-success', 'btn-danger');
        this.classList.add('btn-outline-secondary');
    }, 2000);
});

document.getElementById('copyActivationRequestJsonBtn')?.addEventListener('click', async function () {
    const originalHtml = this.innerHTML;
    const copyText = this.dataset.copyText || '';

    try {
        if (navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(copyText);
        } else {
            const tempInput = document.createElement('textarea');
            tempInput.value = copyText;
            tempInput.setAttribute('readonly', 'readonly');
            tempInput.style.position = 'absolute';
            tempInput.style.left = '-9999px';
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
        }

        this.innerHTML = '<i class="fas fa-check mr-1"></i> Tersalin';
        this.classList.remove('btn-outline-secondary');
        this.classList.add('btn-success');
    } catch (error) {
        this.innerHTML = '<i class="fas fa-times mr-1"></i> Gagal Copy';
        this.classList.remove('btn-outline-secondary');
        this.classList.add('btn-danger');
    }

    setTimeout(() => {
        this.innerHTML = originalHtml;
        this.classList.remove('btn-success', 'btn-danger');
        this.classList.add('btn-outline-secondary');
    }, 2000);
});
</script>
@endpush
