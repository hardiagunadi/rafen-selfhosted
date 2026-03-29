@extends('layouts.admin')

@section('title', 'OLT')

@section('content')
    <div class="container">
        <div class="mb-3">
            <h1 class="h3 mb-1">Monitoring OLT</h1>
            <p class="text-muted mb-0">Kelola koneksi OLT, deteksi profil SNMP, dan simpan hasil polling ONU.</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Tambah Koneksi OLT</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('super-admin.settings.olt.store') }}" method="POST">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="name">Nama</label>
                            <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group col-md-3">
                            <label for="olt_model">Model</label>
                            <select id="olt_model" name="olt_model" class="form-control">
                                <option value="">Pilih model</option>
                                @foreach($availableModels as $model)
                                    <option value="{{ $model }}" @selected(old('olt_model') === $model)>{{ $model }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="host">Host / IP</label>
                            <input type="text" id="host" name="host" class="form-control @error('host') is-invalid @enderror" value="{{ old('host') }}">
                            @error('host')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group col-md-1">
                            <label for="snmp_port">SNMP Port</label>
                            <input type="number" id="snmp_port" name="snmp_port" class="form-control" value="{{ old('snmp_port', 161) }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="snmp_community">Read Community</label>
                            <input type="text" id="snmp_community" name="snmp_community" class="form-control" value="{{ old('snmp_community', 'public') }}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-2">
                            <label for="snmp_write_community">Write Community</label>
                            <input type="text" id="snmp_write_community" name="snmp_write_community" class="form-control" value="{{ old('snmp_write_community') }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="oid_reboot_onu">OID Reboot ONU</label>
                            <input type="text" id="oid_reboot_onu" name="oid_reboot_onu" class="form-control @error('oid_reboot_onu') is-invalid @enderror" value="{{ old('oid_reboot_onu') }}">
                            @error('oid_reboot_onu')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group col-md-2">
                            <label for="snmp_timeout">Timeout</label>
                            <input type="number" id="snmp_timeout" name="snmp_timeout" class="form-control" value="{{ old('snmp_timeout', 5) }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="snmp_retries">Retries</label>
                            <input type="number" id="snmp_retries" name="snmp_retries" class="form-control" value="{{ old('snmp_retries', 1) }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="vendor">Vendor</label>
                            <select id="vendor" name="vendor" class="form-control">
                                <option value="hsgq">HSGQ</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2 d-flex align-items-end">
                            <div class="form-check">
                                <input type="checkbox" id="is_active" name="is_active" value="1" class="form-check-input" @checked(old('is_active', true))>
                                <label for="is_active" class="form-check-label">Aktif</label>
                            </div>
                        </div>
                        <div class="form-group col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-block">Simpan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title mb-0">Koneksi OLT</h3>
            </div>
            <div class="card-body">
                @if($connections->isEmpty())
                    <div class="text-muted">Belum ada koneksi OLT.</div>
                @else
                    <div class="d-flex flex-column" style="gap: 1rem;">
                        @foreach($connections as $connection)
                            <div class="border rounded p-3 {{ $selectedConnection?->is($connection) ? 'border-primary' : 'border-light' }}">
                                <form action="{{ route('super-admin.settings.olt.update', $connection) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-row">
                                        <div class="form-group col-md-2">
                                            <label>Nama</label>
                                            <input type="text" name="name" class="form-control" value="{{ $connection->name }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Model</label>
                                            <select name="olt_model" class="form-control">
                                                <option value="">Pilih model</option>
                                                @foreach($availableModels as $model)
                                                    <option value="{{ $model }}" @selected($connection->olt_model === $model)>{{ $model }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Host / IP</label>
                                            <input type="text" name="host" class="form-control" value="{{ $connection->host }}">
                                        </div>
                                        <div class="form-group col-md-1">
                                            <label>Port</label>
                                            <input type="number" name="snmp_port" class="form-control" value="{{ $connection->snmp_port }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Read Community</label>
                                            <input type="text" name="snmp_community" class="form-control" value="{{ $connection->snmp_community }}">
                                        </div>
                                        <div class="form-group col-md-1">
                                            <label>Timeout</label>
                                            <input type="number" name="snmp_timeout" class="form-control" value="{{ $connection->snmp_timeout }}">
                                        </div>
                                        <div class="form-group col-md-1">
                                            <label>Retries</label>
                                            <input type="number" name="snmp_retries" class="form-control" value="{{ $connection->snmp_retries }}">
                                        </div>
                                        <div class="form-group col-md-1 d-flex align-items-end">
                                            <div class="form-check">
                                                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active_{{ $connection->id }}" @checked($connection->is_active)>
                                                <label class="form-check-label" for="is_active_{{ $connection->id }}">Aktif</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-2">
                                            <label>OID Serial</label>
                                            <input type="text" name="oid_serial" class="form-control" value="{{ $connection->oid_serial }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>OID Nama ONU</label>
                                            <input type="text" name="oid_onu_name" class="form-control" value="{{ $connection->oid_onu_name }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>OID Rx ONU</label>
                                            <input type="text" name="oid_rx_onu" class="form-control" value="{{ $connection->oid_rx_onu }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>OID Distance</label>
                                            <input type="text" name="oid_distance" class="form-control" value="{{ $connection->oid_distance }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>OID Status</label>
                                            <input type="text" name="oid_status" class="form-control" value="{{ $connection->oid_status }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>OID Reboot ONU</label>
                                            <input type="text" name="oid_reboot_onu" class="form-control" value="{{ $connection->oid_reboot_onu }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Write Community</label>
                                            <input type="text" name="snmp_write_community" class="form-control" value="{{ $connection->snmp_write_community }}">
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap align-items-center" style="gap: 0.5rem;">
                                        <button type="submit" class="btn btn-outline-primary btn-sm">Simpan</button>
                                        <a href="{{ route('super-admin.settings.olt.index', ['connection' => $connection->id]) }}" class="btn btn-outline-secondary btn-sm">Lihat Data</a>
                                    </div>
                                </form>
                                <div class="d-flex flex-wrap mt-3" style="gap: 0.5rem;">
                                    <form action="{{ route('super-admin.settings.olt.detect-model', $connection) }}" method="POST" class="mb-0">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-info btn-sm">Deteksi Model</button>
                                    </form>
                                    <form action="{{ route('super-admin.settings.olt.detect-oid', $connection) }}" method="POST" class="mb-0">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-info btn-sm">Deteksi OID</button>
                                    </form>
                                    <form action="{{ route('super-admin.settings.olt.poll', $connection) }}" method="POST" class="mb-0">
                                        @csrf
                                        <button type="submit" class="btn btn-warning btn-sm">Polling Sekarang</button>
                                    </form>
                                    <form action="{{ route('super-admin.settings.olt.destroy', $connection) }}" method="POST" class="mb-0" onsubmit="return confirm('Hapus koneksi OLT ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                                    </form>
                                    <div class="small text-muted d-flex align-items-center">
                                        ONU tersimpan: {{ $connection->onu_optics_count }}
                                        @if($connection->last_polled_at)
                                            <span class="ml-2">Polling terakhir: {{ $connection->last_polled_at->format('d/m/Y H:i:s') }}</span>
                                        @endif
                                        @if($connection->last_poll_message)
                                            <span class="ml-2">{{ $connection->last_poll_message }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        @if($selectedConnection)
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">Ringkasan Port {{ $selectedConnection->name }}</h3>
                </div>
                <div class="card-body">
                    @if($summaryRows->isEmpty())
                        <div class="text-muted">Belum ada data ONU. Jalankan polling untuk memuat data optik.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Port</th>
                                        <th>Total ONU</th>
                                        <th>Online</th>
                                        <th>Offline</th>
                                        <th>Avg Rx ONU</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($summaryRows as $row)
                                        <tr>
                                            <td>{{ $row['port_id'] }}</td>
                                            <td>{{ $row['total'] }}</td>
                                            <td>{{ $row['online'] }}</td>
                                            <td>{{ $row['offline'] }}</td>
                                            <td>{{ $row['avg_rx_onu_dbm'] !== null ? number_format($row['avg_rx_onu_dbm'], 2).' dBm' : '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">ONU Tersimpan</h3>
                </div>
                <div class="card-body">
                    @if($selectedConnection->onuOptics()->count() === 0)
                        <div class="text-muted">Belum ada hasil polling ONU.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>PON</th>
                                        <th>ONU</th>
                                        <th>Serial</th>
                                        <th>Nama</th>
                                        <th>Distance</th>
                                        <th>Rx ONU</th>
                                        <th>Status</th>
                                        <th class="text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($selectedConnection->onuOptics()->orderBy('pon_interface')->orderBy('onu_number')->get() as $onu)
                                        <tr>
                                            <td>{{ $onu->pon_interface ?? '-' }}</td>
                                            <td>{{ $onu->onu_number ?? '-' }}</td>
                                            <td>{{ $onu->serial_number ?? '-' }}</td>
                                            <td>{{ $onu->onu_name ?? '-' }}</td>
                                            <td>{{ $onu->distance_m !== null ? number_format($onu->distance_m).' m' : '-' }}</td>
                                            <td>{{ $onu->rx_onu_dbm !== null ? number_format((float) $onu->rx_onu_dbm, 2).' dBm' : '-' }}</td>
                                            <td>
                                                <span class="badge {{ $onu->status === 'online' ? 'badge-success' : 'badge-secondary' }}">
                                                    {{ $onu->status ?? '-' }}
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <form action="{{ route('super-admin.settings.olt.onu-reboot', $selectedConnection) }}" method="POST" class="mb-0" onsubmit="return confirm('Kirim reboot ke ONU ini?');">
                                                    @csrf
                                                    <input type="hidden" name="onu_index" value="{{ $onu->onu_index }}">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">Reboot ONU</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection
