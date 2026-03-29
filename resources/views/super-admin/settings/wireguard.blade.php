@extends('layouts.admin')

@section('title', 'WireGuard')

@section('content')
<div class="container-fluid">
    <div class="row mb-3 align-items-center">
        <div class="col">
            <h4 class="mb-0"><i class="fas fa-shield-alt mr-2 text-primary"></i>WireGuard</h4>
            <small class="text-muted">Kelola peer WireGuard untuk deployment self-hosted tanpa dependensi tenant SaaS.</small>
        </div>
        <div class="col-auto">
            <form method="POST" action="{{ route('super-admin.settings.wireguard.sync') }}">
                @csrf
                <button type="submit" class="btn btn-outline-primary">
                    <i class="fas fa-sync mr-1"></i> Sinkronkan Konfigurasi
                </button>
            </form>
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
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><i class="fas fa-server mr-1"></i> Informasi Server</div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr>
                                <th class="w-25">Host Publik</th>
                                <td>{{ $wg['host'] !== '' ? $wg['host'] : '-' }}</td>
                            </tr>
                            <tr>
                                <th>Interface</th>
                                <td>{{ $wg['interface'] }}</td>
                            </tr>
                            <tr>
                                <th>Listen Port</th>
                                <td>{{ $wg['listen_port'] }}/udp</td>
                            </tr>
                            <tr>
                                <th>Server IP</th>
                                <td>{{ $wg['server_ip'] }}</td>
                            </tr>
                            <tr>
                                <th>Server Address</th>
                                <td>{{ $wg['server_address'] }}</td>
                            </tr>
                            <tr>
                                <th>Pool</th>
                                <td>{{ $wg['pool_start'] }} - {{ $wg['pool_end'] }}</td>
                            </tr>
                            <tr>
                                <th>Config Path</th>
                                <td><code>{{ $wg['config_path'] }}</code></td>
                            </tr>
                            <tr>
                                <th>Server Public Key</th>
                                <td><code class="d-inline-block" style="word-break: break-all;">{{ $wg['server_public_key'] !== '' ? $wg['server_public_key'] : '-' }}</code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><i class="fas fa-plus-circle mr-1"></i> Tambah Peer</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('super-admin.settings.wireguard.peers.store') }}">
                        @csrf
                        <div class="form-group">
                            <label for="peer_name">Nama Peer</label>
                            <input id="peer_name" type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label for="peer_vpn_ip">IP VPN</label>
                            <input id="peer_vpn_ip" type="text" name="vpn_ip" value="{{ old('vpn_ip') }}" class="form-control @error('vpn_ip') is-invalid @enderror" placeholder="Kosongkan untuk alokasi otomatis">
                            @error('vpn_ip')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label for="peer_extra_allowed_ips">Extra Allowed IPs</label>
                            <input id="peer_extra_allowed_ips" type="text" name="extra_allowed_ips" value="{{ old('extra_allowed_ips') }}" class="form-control @error('extra_allowed_ips') is-invalid @enderror" placeholder="172.16.0.0/16,192.168.10.0/24">
                            @error('extra_allowed_ips')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-check mb-3">
                            <input id="peer_is_active" type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', true))>
                            <label class="form-check-label" for="peer_is_active">Aktifkan peer setelah dibuat</label>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Simpan Peer
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><i class="fas fa-list mr-1"></i> Daftar Peer</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>IP VPN</th>
                                    <th>Public Key</th>
                                    <th>Status</th>
                                    <th>Sync Terakhir</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($peers as $peer)
                                    <tr>
                                        <td>{{ $peer->name }}</td>
                                        <td><code>{{ $peer->vpn_ip ?: '-' }}</code></td>
                                        <td><code style="font-size: 11px; word-break: break-all;">{{ $peer->public_key }}</code></td>
                                        <td>
                                            <span class="badge badge-{{ $peer->is_active ? 'success' : 'secondary' }}">
                                                {{ $peer->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                        <td>{{ $peer->last_synced_at?->format('Y-m-d H:i:s') ?: '-' }}</td>
                                        <td class="text-right">
                                            <div class="d-inline-flex" style="gap: .5rem;">
                                                <form method="POST" action="{{ route('super-admin.settings.wireguard.peers.keygen', $peer) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-warning">Regenerate Key</button>
                                                </form>
                                                <form method="POST" action="{{ route('super-admin.settings.wireguard.peers.destroy', $peer) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus peer ini?')">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">Belum ada peer WireGuard.</td>
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
