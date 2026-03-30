@extends('layouts.admin')

@section('title', 'User Hotspot')

@section('content')
    <div class="card" style="overflow: visible;">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap:.75rem; overflow: visible;">
            <div class="btn-group">
                <div class="dropdown">
                    <button class="btn btn-success btn-sm dropdown-toggle" type="button" data-toggle="dropdown" data-display="static" aria-expanded="false">
                        <i class="fas fa-bars"></i> Manajemen Hotspot
                    </button>
                    <div class="dropdown-menu dropdown-menu-left" style="min-width: 220px;">
                        <a class="dropdown-item" href="{{ route('super-admin.settings.hotspot-users.create') }}">Tambah User Hotspot</a>
                        <a class="dropdown-item" href="{{ route('super-admin.settings.hotspot-users.index') }}">List User Hotspot</a>
                        <div class="dropdown-divider"></div>
                        <div class="dropdown-header text-danger text-uppercase">Aksi Massal</div>
                        <a class="dropdown-item text-danger bulk-delete-action" href="#">Hapus Terpilih</a>
                    </div>
                </div>
            </div>
            <div>
                <h4 class="mb-0">User Hotspot</h4>
            </div>
        </div>
        <div class="card-body">
            <div id="hotspot-users-feedback"></div>
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
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
                        <div class="mr-3 text-success"><i class="fas fa-user-check fa-2x"></i></div>
                        <div class="text-left">
                            <div class="small text-uppercase text-muted">Total User</div>
                            <div class="h5 mb-0">{{ $stats['total'] }}</div>
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

            <div class="table-responsive">
                <table id="hotspot-users-table" class="table table-striped table-hover mb-0" style="width:100%;">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:40px;"><input type="checkbox" id="select-all"></th>
                            <th>ID Pelanggan</th>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Profil Hotspot</th>
                            <th>Jatuh Tempo</th>
                            <th>Status</th>
                            <th>Perpanjang</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <form id="bulk-delete-form" action="{{ route('super-admin.settings.hotspot-users.bulk-destroy') }}" method="POST">
        @csrf
        @method('DELETE')
    </form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const feedbackContainer = document.getElementById('hotspot-users-feedback');
    const showFeedback = (message, level = 'success') => {
        if (!feedbackContainer || !message) {
            return;
        }

        feedbackContainer.innerHTML = '<div class="alert alert-' + level + '">' + message + '</div>';
    };

    const table = $('#hotspot-users-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: @json(route('super-admin.settings.hotspot-users.datatable')),
        columns: [
            { data: 'checkbox', orderable: false, searchable: false, width: '40px' },
            { data: 'customer_id', orderable: false },
            { data: 'nama', orderable: false },
            { data: 'username' },
            { data: 'profil', orderable: false },
            { data: 'jatuh_tempo', orderable: false },
            { data: 'status', orderable: false },
            { data: 'perpanjang', orderable: false, searchable: false },
            { data: 'aksi', orderable: false, searchable: false, className: 'text-right' },
        ],
        language: {
            search: 'Cari:',
            lengthMenu: 'Tampilkan _MENU_ data',
            info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
            infoEmpty: 'Tidak ada data',
            infoFiltered: '(disaring dari _MAX_ total data)',
            zeroRecords: 'Tidak ada data yang cocok.',
            emptyTable: 'Belum ada user Hotspot.',
            paginate: { first: 'Pertama', last: 'Terakhir', next: 'Selanjutnya', previous: 'Sebelumnya' },
            processing: 'Memuat...',
        },
    });

    document.getElementById('select-all')?.addEventListener('change', function () {
        document.querySelectorAll('#hotspot-users-table tbody input[name="ids[]"]').forEach((checkbox) => {
            checkbox.checked = this.checked;
        });
    });

    document.querySelector('.bulk-delete-action')?.addEventListener('click', function (event) {
        event.preventDefault();
        const selected = Array.from(document.querySelectorAll('#hotspot-users-table tbody input[name="ids[]"]:checked'));

        if (!selected.length) {
            window.alert('Pilih user terlebih dahulu.');
            return;
        }

        const form = document.getElementById('bulk-delete-form');
        form.querySelectorAll('input[name="ids[]"]').forEach((node) => node.remove());

        selected.forEach((checkbox) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = checkbox.value;
            form.appendChild(input);
        });

        if (window.confirm('Hapus ' + selected.length + ' user Hotspot terpilih?')) {
            form.submit();
        }
    });

    $(document).on('click', '.toggle-status-btn', async function (event) {
        event.preventDefault();
        const response = await fetch(this.dataset.toggleUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        });

        if (response.ok) {
            const result = await response.json().catch(() => ({}));
            showFeedback(result.status ? 'Status akun diubah ke ' + result.status.toUpperCase() + '.' : 'Status akun berhasil diubah.');
            table.ajax.reload(null, false);
        }
    });

    $(document).on('click', '[data-ajax-post]', async function () {
        const confirmMessage = this.dataset.confirm || 'Lanjutkan proses ini?';

        if (!window.confirm(confirmMessage)) {
            return;
        }

        const response = await fetch(this.dataset.ajaxPost, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        });

        const result = await response.json().catch(() => ({}));

        if (!response.ok) {
            showFeedback(result.status || 'Proses gagal dijalankan.', 'danger');
            return;
        }

        showFeedback(result.status || 'Proses berhasil dijalankan.');
        table.ajax.reload(null, false);
    });

    $(document).on('click', '[data-ajax-delete]', async function () {
        if (!window.confirm('Hapus user Hotspot ini?')) {
            return;
        }

        const response = await fetch(this.dataset.ajaxDelete, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        });

        if (response.ok) {
            const result = await response.json().catch(() => ({}));
            showFeedback(result.status || 'User Hotspot berhasil dihapus.');
            table.ajax.reload(null, false);
        }
    });
});
</script>
@endpush
