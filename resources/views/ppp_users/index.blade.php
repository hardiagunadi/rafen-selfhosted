@extends('layouts.admin')

@section('title', 'User PPP')

@section('content')
    <div class="card" style="overflow: visible;">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap:.75rem; overflow: visible;">
            <div class="btn-group">
                <div class="dropdown">
                    <button class="btn btn-success btn-sm dropdown-toggle" type="button" data-toggle="dropdown" data-display="static" aria-expanded="false">
                        <i class="fas fa-bars"></i> Manajemen Pelanggan
                    </button>
                    <div class="dropdown-menu dropdown-menu-left" style="min-width: 260px;">
                        <a class="dropdown-item" href="{{ route('super-admin.settings.ppp-users.create') }}">Tambah Pelanggan</a>
                        <a class="dropdown-item" href="{{ route('super-admin.settings.ppp-users.index') }}">List Pelanggan</a>
                        <div class="dropdown-divider"></div>
                        <div class="dropdown-header text-danger text-uppercase">Aksi Checkbox (Massal)</div>
                        <a class="dropdown-item text-danger bulk-delete-action" href="#">Hapus Terpilih</a>
                    </div>
                </div>
            </div>
            <div>
                <h4 class="mb-0">User PPP</h4>
            </div>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0 pl-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row text-center mb-3">
                <div class="col-md-3 col-sm-6 mb-2">
                    <div class="p-3 border rounded h-100 d-flex align-items-center">
                        <div class="mr-3 text-info"><i class="fas fa-users fa-2x"></i></div>
                        <div class="text-left">
                            <div class="small text-uppercase text-muted">Registrasi Bulan Ini</div>
                            <div class="h5 mb-0">{{ $stats['registrasi_bulan_ini'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-2">
                    <div class="p-3 border rounded h-100 d-flex align-items-center">
                        <div class="mr-3 text-success"><i class="fas fa-recycle fa-2x"></i></div>
                        <div class="text-left">
                            <div class="small text-uppercase text-muted">Update Bulan Ini</div>
                            <div class="h5 mb-0">{{ $stats['renewal_bulan_ini'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-2">
                    <div class="p-3 border rounded h-100 d-flex align-items-center">
                        <div class="mr-3 text-warning"><i class="fas fa-exclamation-triangle fa-2x"></i></div>
                        <div class="text-left">
                            <div class="small text-uppercase text-muted">Pelanggan Isolir</div>
                            <div class="h5 mb-0">{{ $stats['pelanggan_isolir'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-2">
                    <div class="p-3 border rounded h-100 d-flex align-items-center">
                        <div class="mr-3 text-danger"><i class="fas fa-ban fa-2x"></i></div>
                        <div class="text-left">
                            <div class="small text-uppercase text-muted">Akun Disable</div>
                            <div class="h5 mb-0">{{ $stats['akun_disable'] }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <form method="GET" class="mb-3">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-5">
                        <label for="search">Cari Pelanggan</label>
                        <input type="text" id="search" name="search" value="{{ $search }}" class="form-control" placeholder="Nama, ID pelanggan, atau username">
                    </div>
                    <div class="form-group col-md-2">
                        <label for="per_page">Tampilkan</label>
                        <select id="per_page" name="per_page" class="form-control">
                            @foreach([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-auto">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i>Cari</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:40px;"><input type="checkbox" id="select-all"></th>
                            <th>ID Pelanggan</th>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Paket</th>
                            <th>ODP</th>
                            <th>Jatuh Tempo</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pppUsers as $pppUser)
                            <tr>
                                <td><input type="checkbox" name="ids[]" value="{{ $pppUser->id }}" form="bulk-delete-form"></td>
                                <td>
                                    <a href="#" class="toggle-status-btn badge badge-{{ $pppUser->status_akun === 'enable' ? 'success' : 'danger' }}" data-toggle-url="{{ route('super-admin.settings.ppp-users.toggle-status', $pppUser) }}">
                                        {{ $pppUser->customer_id ?? '-' }}
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ route('super-admin.settings.ppp-users.edit', $pppUser) }}" class="font-weight-bold text-dark">{{ $pppUser->customer_name }}</a>
                                </td>
                                <td>{{ $pppUser->username ?? '-' }}</td>
                                <td>{{ $pppUser->profile?->name ?? '-' }}</td>
                                <td>{{ $pppUser->odp?->code ?? ($pppUser->odp_pop ?: '-') }}</td>
                                <td>{{ $pppUser->jatuh_tempo?->format('Y-m-d') ?? '-' }}</td>
                                <td>
                                    <span class="badge badge-{{ $pppUser->status_akun === 'enable' ? 'success' : ($pppUser->status_akun === 'isolir' ? 'warning' : 'secondary') }}">
                                        {{ strtoupper($pppUser->status_akun ?? '-') }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('super-admin.settings.ppp-users.edit', $pppUser) }}" class="btn btn-warning text-white" title="Edit">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <form action="{{ route('super-admin.settings.ppp-users.destroy', $pppUser) }}" method="POST" onsubmit="return confirm('Hapus user PPP ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">Belum ada user PPP.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $pppUsers->links() }}
            </div>
        </div>
    </div>

    <form id="bulk-delete-form" action="{{ route('super-admin.settings.ppp-users.bulk-destroy') }}" method="POST">
        @csrf
        @method('DELETE')
    </form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('select-all')?.addEventListener('change', function () {
        document.querySelectorAll('input[name="ids[]"][form="bulk-delete-form"]').forEach((checkbox) => {
            checkbox.checked = this.checked;
        });
    });

    document.querySelector('.bulk-delete-action')?.addEventListener('click', function (event) {
        event.preventDefault();
        const selected = Array.from(document.querySelectorAll('input[name="ids[]"][form="bulk-delete-form"]:checked'));
        if (!selected.length) {
            window.alert('Pilih pelanggan terlebih dahulu.');
            return;
        }
        if (window.confirm('Hapus ' + selected.length + ' pelanggan PPP terpilih?')) {
            document.getElementById('bulk-delete-form')?.submit();
        }
    });

    document.querySelectorAll('.toggle-status-btn').forEach((button) => {
        button.addEventListener('click', async function (event) {
            event.preventDefault();
            const response = await fetch(this.dataset.toggleUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            });
            if (!response.ok) {
                return;
            }
            const payload = await response.json();
            const isEnable = payload.status === 'enable';
            this.classList.remove('badge-success', 'badge-danger');
            this.classList.add(isEnable ? 'badge-success' : 'badge-danger');
        });
    });
});
</script>
@endpush
