@extends('layouts.admin')

@section('title', 'FreeRADIUS')

@section('content')
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 mb-1">FreeRADIUS</h1>
                <p class="text-muted mb-0">Kelola NAS clients, sinkronisasi file `clients.conf`, dan kontrol service lokal.</p>
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
                        <h3 class="card-title">Status Sinkronisasi</h3>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-3">
                            <dt class="col-sm-5">Clients Path</dt>
                            <dd class="col-sm-7">{{ $clientsPath ?: '-' }}</dd>
                            <dt class="col-sm-5">Status File</dt>
                            <dd class="col-sm-7">{{ $syncStatus['message'] }}</dd>
                            <dt class="col-sm-5">Update Terakhir</dt>
                            <dd class="col-sm-7">{{ $syncStatus['updated_at'] ?? '-' }}</dd>
                            <dt class="col-sm-5">Ukuran File</dt>
                            <dd class="col-sm-7">{{ $syncStatus['size'] !== null ? $syncStatus['size'].' bytes' : '-' }}</dd>
                        </dl>

                        <div class="d-flex flex-wrap gap-2">
                            <form action="{{ route('super-admin.settings.freeradius.sync') }}" method="POST" class="mr-2 mb-2">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">Sync NAS Clients</button>
                            </form>
                            <form action="{{ route('super-admin.settings.freeradius.service', 'reload') }}" method="POST" class="mr-2 mb-2">
                                @csrf
                                <button type="submit" class="btn btn-outline-primary btn-sm">Reload Service</button>
                            </form>
                            <form action="{{ route('super-admin.settings.freeradius.service', 'restart') }}" method="POST" class="mb-2">
                                @csrf
                                <button type="submit" class="btn btn-warning btn-sm">Restart Service</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Service Lokal</h3>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Status</dt>
                            <dd class="col-sm-7">{{ $serviceStatus['message'] ?? '-' }}</dd>
                            <dt class="col-sm-5">Output</dt>
                            <dd class="col-sm-7"><code>{{ $serviceStatus['output'] ?: '-' }}</code></dd>
                            <dt class="col-sm-5">Log Path</dt>
                            <dd class="col-sm-7">{{ $logPath ?: '-' }}</dd>
                        </dl>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Tambah NAS</h3>
                    </div>
                    <form action="{{ route('super-admin.settings.freeradius.nas.store') }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="form-group">
                                <label for="name">Nama</label>
                                <input id="name" type="text" name="name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="shortname">Shortname</label>
                                <input id="shortname" type="text" name="shortname" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="ip_address">IP Address</label>
                                <input id="ip_address" type="text" name="ip_address" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="secret">Secret</label>
                                <input id="secret" type="text" name="secret" class="form-control" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="auth_port">Auth Port</label>
                                    <input id="auth_port" type="number" name="auth_port" class="form-control" value="1812">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="acct_port">Acct Port</label>
                                    <input id="acct_port" type="number" name="acct_port" class="form-control" value="1813">
                                </div>
                            </div>
                            <div class="custom-control custom-switch mb-2">
                                <input id="require_message_authenticator" type="checkbox" name="require_message_authenticator" value="1" class="custom-control-input" checked>
                                <label class="custom-control-label" for="require_message_authenticator">Require Message Authenticator</label>
                            </div>
                            <div class="custom-control custom-switch mb-2">
                                <input id="is_active" type="checkbox" name="is_active" value="1" class="custom-control-input" checked>
                                <label class="custom-control-label" for="is_active">NAS aktif</label>
                            </div>
                            <div class="form-group mb-0">
                                <label for="notes">Catatan</label>
                                <textarea id="notes" name="notes" rows="3" class="form-control"></textarea>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">Tambah NAS</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Daftar NAS Clients</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>IP</th>
                                        <th>Shortname</th>
                                        <th>Status</th>
                                        <th class="text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($radiusNasClients as $radiusNas)
                                        <tr>
                                            <td>{{ $radiusNas->name }}</td>
                                            <td><code>{{ $radiusNas->ip_address }}</code></td>
                                            <td><code>{{ $radiusNas->shortname }}</code></td>
                                            <td>{{ $radiusNas->is_active ? 'active' : 'inactive' }}</td>
                                            <td class="text-right">
                                                <button type="button" class="btn btn-outline-secondary btn-xs mr-2" data-toggle="collapse" data-target="#edit-radius-nas-{{ $radiusNas->id }}">Edit</button>
                                                <form action="{{ route('super-admin.settings.freeradius.nas.destroy', $radiusNas) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-xs">Hapus</button>
                                                </form>
                                            </td>
                                        </tr>
                                        <tr class="collapse" id="edit-radius-nas-{{ $radiusNas->id }}">
                                            <td colspan="5" class="bg-light">
                                                <form action="{{ route('super-admin.settings.freeradius.nas.update', $radiusNas) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="form-row">
                                                        <div class="form-group col-md-3">
                                                            <label>Nama</label>
                                                            <input type="text" name="name" class="form-control form-control-sm" value="{{ $radiusNas->name }}" required>
                                                        </div>
                                                        <div class="form-group col-md-2">
                                                            <label>Shortname</label>
                                                            <input type="text" name="shortname" class="form-control form-control-sm" value="{{ $radiusNas->shortname }}" required>
                                                        </div>
                                                        <div class="form-group col-md-2">
                                                            <label>IP</label>
                                                            <input type="text" name="ip_address" class="form-control form-control-sm" value="{{ $radiusNas->ip_address }}" required>
                                                        </div>
                                                        <div class="form-group col-md-2">
                                                            <label>Auth</label>
                                                            <input type="number" name="auth_port" class="form-control form-control-sm" value="{{ $radiusNas->auth_port }}" required>
                                                        </div>
                                                        <div class="form-group col-md-2">
                                                            <label>Acct</label>
                                                            <input type="number" name="acct_port" class="form-control form-control-sm" value="{{ $radiusNas->acct_port }}" required>
                                                        </div>
                                                        <div class="form-group col-md-12">
                                                            <label>Secret</label>
                                                            <input type="text" name="secret" class="form-control form-control-sm" value="{{ $radiusNas->secret }}" required>
                                                        </div>
                                                        <div class="form-group col-md-12">
                                                            <label>Catatan</label>
                                                            <textarea name="notes" rows="2" class="form-control form-control-sm">{{ $radiusNas->notes }}</textarea>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex flex-wrap gap-3 mb-2">
                                                        <div class="custom-control custom-switch mr-3">
                                                            <input id="require-message-auth-{{ $radiusNas->id }}" type="checkbox" name="require_message_authenticator" value="1" class="custom-control-input" @checked($radiusNas->require_message_authenticator)>
                                                            <label class="custom-control-label" for="require-message-auth-{{ $radiusNas->id }}">Require Message Authenticator</label>
                                                        </div>
                                                        <div class="custom-control custom-switch">
                                                            <input id="radius-active-{{ $radiusNas->id }}" type="checkbox" name="is_active" value="1" class="custom-control-input" @checked($radiusNas->is_active)>
                                                            <label class="custom-control-label" for="radius-active-{{ $radiusNas->id }}">NAS aktif</label>
                                                        </div>
                                                    </div>
                                                    <button type="submit" class="btn btn-primary btn-sm">Simpan Perubahan</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">Belum ada NAS client FreeRADIUS.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Log FreeRADIUS Terbaru</h3>
                    </div>
                    <div class="card-body">
                        @if ($logPayload['error'])
                            <div class="alert alert-warning mb-0">{{ $logPayload['error'] }}</div>
                        @else
                            <pre class="mb-0" style="max-height: 420px; overflow:auto;">{{ implode("\n", $logPayload['lines']) }}</pre>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
