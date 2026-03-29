@extends('layouts.admin')

@section('title', 'MikroTik')

@section('content')
    <div class="container">
        <div class="mb-3">
            <h1 class="h3 mb-1">Koneksi MikroTik</h1>
            <p class="text-muted mb-0">Kelola router MikroTik, cek status API, dan simpan kredensial dasar untuk integrasi self-hosted.</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Tambah Koneksi MikroTik</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('super-admin.settings.mikrotik.store') }}" method="POST">
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
                            <label for="host">Host / IP</label>
                            <input type="text" id="host" name="host" class="form-control @error('host') is-invalid @enderror" value="{{ old('host') }}">
                            @error('host')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group col-md-2">
                            <label for="username">Username API</label>
                            <input type="text" id="username" name="username" class="form-control" value="{{ old('username') }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="password">Password API</label>
                            <input type="text" id="password" name="password" class="form-control" value="{{ old('password') }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="radius_secret">Radius Secret</label>
                            <input type="text" id="radius_secret" name="radius_secret" class="form-control" value="{{ old('radius_secret') }}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-1">
                            <label for="api_port">API</label>
                            <input type="number" id="api_port" name="api_port" class="form-control" value="{{ old('api_port', 8728) }}">
                        </div>
                        <div class="form-group col-md-1">
                            <label for="api_ssl_port">API SSL</label>
                            <input type="number" id="api_ssl_port" name="api_ssl_port" class="form-control" value="{{ old('api_ssl_port', 8729) }}">
                        </div>
                        <div class="form-group col-md-1">
                            <label for="api_timeout">Timeout</label>
                            <input type="number" id="api_timeout" name="api_timeout" class="form-control" value="{{ old('api_timeout', 10) }}">
                        </div>
                        <div class="form-group col-md-1">
                            <label for="auth_port">Auth</label>
                            <input type="number" id="auth_port" name="auth_port" class="form-control" value="{{ old('auth_port', 1812) }}">
                        </div>
                        <div class="form-group col-md-1">
                            <label for="acct_port">Acct</label>
                            <input type="number" id="acct_port" name="acct_port" class="form-control" value="{{ old('acct_port', 1813) }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="ros_version">ROS Version</label>
                            <select id="ros_version" name="ros_version" class="form-control">
                                @foreach(['auto', '6', '7'] as $version)
                                    <option value="{{ $version }}" @selected(old('ros_version', 'auto') === $version)>{{ strtoupper($version) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="timezone">Timezone</label>
                            <input type="text" id="timezone" name="timezone" class="form-control" value="{{ old('timezone', '+07:00 Asia/Jakarta') }}">
                        </div>
                        <div class="form-group col-md-1 d-flex align-items-end">
                            <div class="form-check">
                                <input type="checkbox" id="use_ssl" name="use_ssl" value="1" class="form-check-input" @checked(old('use_ssl'))>
                                <label for="use_ssl" class="form-check-label">SSL</label>
                            </div>
                        </div>
                        <div class="form-group col-md-1 d-flex align-items-end">
                            <div class="form-check">
                                <input type="checkbox" id="is_active" name="is_active" value="1" class="form-check-input" @checked(old('is_active', true))>
                                <label for="is_active" class="form-check-label">Aktif</label>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex" style="gap: 0.5rem;">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <button type="button" class="btn btn-outline-info" data-test-form>Test Koneksi</button>
                    </div>
                    <div class="small text-muted mt-2" data-test-result></div>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title mb-0">Daftar Koneksi</h3>
            </div>
            <div class="card-body">
                @if($connections->isEmpty())
                    <div class="text-muted">Belum ada koneksi MikroTik.</div>
                @else
                    <div class="d-flex flex-column" style="gap: 1rem;">
                        @foreach($connections as $connection)
                            <div class="border rounded p-3">
                                <form action="{{ route('super-admin.settings.mikrotik.update', $connection) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-row">
                                        <div class="form-group col-md-2">
                                            <label>Nama</label>
                                            <input type="text" name="name" class="form-control" value="{{ $connection->name }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Host / IP</label>
                                            <input type="text" name="host" class="form-control" value="{{ $connection->host }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Username</label>
                                            <input type="text" name="username" class="form-control" value="{{ $connection->username }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Password</label>
                                            <input type="text" name="password" class="form-control" value="{{ $connection->password }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Radius Secret</label>
                                            <input type="text" name="radius_secret" class="form-control" value="{{ $connection->radius_secret }}">
                                        </div>
                                        <div class="form-group col-md-1">
                                            <label>API</label>
                                            <input type="number" name="api_port" class="form-control" value="{{ $connection->api_port }}">
                                        </div>
                                        <div class="form-group col-md-1">
                                            <label>SSL</label>
                                            <input type="number" name="api_ssl_port" class="form-control" value="{{ $connection->api_ssl_port }}">
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-1">
                                            <label>Timeout</label>
                                            <input type="number" name="api_timeout" class="form-control" value="{{ $connection->api_timeout }}">
                                        </div>
                                        <div class="form-group col-md-1">
                                            <label>Auth</label>
                                            <input type="number" name="auth_port" class="form-control" value="{{ $connection->auth_port }}">
                                        </div>
                                        <div class="form-group col-md-1">
                                            <label>Acct</label>
                                            <input type="number" name="acct_port" class="form-control" value="{{ $connection->acct_port }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>ROS</label>
                                            <select name="ros_version" class="form-control">
                                                @foreach(['auto', '6', '7'] as $version)
                                                    <option value="{{ $version }}" @selected($connection->ros_version === $version)>{{ strtoupper($version) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Timezone</label>
                                            <input type="text" name="timezone" class="form-control" value="{{ $connection->timezone }}">
                                        </div>
                                        <div class="form-group col-md-1 d-flex align-items-end">
                                            <div class="form-check">
                                                <input type="checkbox" name="use_ssl" value="1" class="form-check-input" id="use_ssl_{{ $connection->id }}" @checked($connection->use_ssl)>
                                                <label class="form-check-label" for="use_ssl_{{ $connection->id }}">SSL</label>
                                            </div>
                                        </div>
                                        <div class="form-group col-md-1 d-flex align-items-end">
                                            <div class="form-check">
                                                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active_{{ $connection->id }}" @checked($connection->is_active)>
                                                <label class="form-check-label" for="is_active_{{ $connection->id }}">Aktif</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap align-items-center" style="gap: 0.5rem;">
                                        <button type="submit" class="btn btn-outline-primary btn-sm">Simpan</button>
                                        <button type="button" class="btn btn-outline-info btn-sm" data-ping-url="{{ route('super-admin.settings.mikrotik.ping', $connection) }}">Ping Sekarang</button>
                                    </div>
                                </form>
                                <form action="{{ route('super-admin.settings.mikrotik.destroy', $connection) }}" method="POST" class="mt-2" onsubmit="return confirm('Hapus koneksi MikroTik ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                                </form>
                                <div class="mt-2 d-flex flex-wrap align-items-center" style="gap: 0.5rem;">
                                    <span class="badge @if($connection->is_online === true) badge-success @elseif($connection->ping_unstable) badge-warning @elseif($connection->is_online === false) badge-danger @else badge-secondary @endif">
                                        @if($connection->is_online === true)
                                            Terhubung
                                        @elseif($connection->ping_unstable)
                                            Tidak Stabil
                                        @elseif($connection->is_online === false)
                                            Tidak Terhubung
                                        @else
                                            Belum Dicek
                                        @endif
                                    </span>
                                    <span class="small text-muted">
                                        {{ $connection->last_ping_message ?? 'Belum ada hasil ping.' }}
                                        @if($connection->last_ping_at)
                                            ({{ $connection->last_ping_at->format('d/m/Y H:i:s') }})
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function () {
            const csrfToken = $('meta[name="csrf-token"]').attr('content');

            $('[data-test-form]').on('click', function () {
                const form = $(this).closest('form');

                $.post({
                    url: @json(route('super-admin.settings.mikrotik.test')),
                    data: {
                        host: form.find('[name="host"]').val(),
                        api_timeout: form.find('[name="api_timeout"]').val(),
                        api_port: form.find('[name="api_port"]').val(),
                        api_ssl_port: form.find('[name="api_ssl_port"]').val(),
                        use_ssl: form.find('[name="use_ssl"]').is(':checked') ? 1 : 0,
                    },
                    headers: {'X-CSRF-TOKEN': csrfToken},
                }).done(function (response) {
                    form.find('[data-test-result]').text(response.message).removeClass('text-danger').addClass('text-success');
                }).fail(function (xhr) {
                    const message = xhr.responseJSON?.message || xhr.responseJSON?.error || 'Test koneksi gagal.';
                    form.find('[data-test-result]').text(message).removeClass('text-success').addClass('text-danger');
                });
            });

            $('[data-ping-url]').on('click', function () {
                $.post({
                    url: $(this).data('ping-url'),
                    headers: {'X-CSRF-TOKEN': csrfToken},
                }).always(function () {
                    window.location.reload();
                });
            });
        });
    </script>
@endpush
