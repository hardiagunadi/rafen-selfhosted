@extends('layouts.admin')

@section('title', 'Data ODP')

@section('content')
    @include('partials.customer-management-shell-styles')

    <div class="cm-page">
        <div class="cm-header">
            <div class="cm-header-main">
                <div class="cm-header-icon" style="background:linear-gradient(135deg,#0f766e,#14b8a6);">
                    <i class="fas fa-network-wired"></i>
                </div>
                <div class="cm-header-copy">
                    <p class="cm-kicker">ODP Management</p>
                    <h1 class="cm-title">Data ODP</h1>
                    <p class="cm-subtitle">Master Optical Distribution Point dengan flow list yang lebih dekat ke tenant SaaS.</p>
                </div>
            </div>
            <div class="cm-header-actions">
                <a href="{{ route('super-admin.odps.create') }}" class="cm-btn cm-btn-primary">
                    <i class="fas fa-plus-circle"></i>
                    Tambah ODP
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div id="odps-feedback"></div>

        <div class="row">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="cm-metric h-100">
                    <p class="cm-metric-label">Total ODP</p>
                    <p class="cm-metric-value">{{ $stats['total_odp'] }}</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="cm-metric h-100">
                    <p class="cm-metric-label">ODP Active</p>
                    <p class="cm-metric-value">{{ $stats['active_odp'] }}</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="cm-metric h-100">
                    <p class="cm-metric-label">Maintenance</p>
                    <p class="cm-metric-value">{{ $stats['maintenance_odp'] }}</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="cm-metric h-100">
                    <p class="cm-metric-label">Port Pakai / Kapasitas</p>
                    <p class="cm-metric-value">{{ $stats['used_ports'] }} / {{ $stats['capacity_ports'] }}</p>
                </div>
            </div>
        </div>

        <div class="cm-main-card">
            <div class="cm-main-card-header">
                <div>
                    <h2 class="cm-main-card-title">Daftar ODP</h2>
                    <p class="cm-main-card-subtitle">Cari, edit, dan hapus ODP dari satu tabel seperti pola data master di tenant SaaS.</p>
                </div>
            </div>
            <div class="cm-main-card-body p-0">
                <div class="table-responsive">
                    <table id="odps-table" class="table table-hover mb-0" style="width:100%;">
                        <thead class="thead-light">
                            <tr>
                                <th>Kode ODP</th>
                                <th>Nama</th>
                                <th>Area</th>
                                <th>Koordinat</th>
                                <th>Port</th>
                                <th>Status</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const feedbackContainer = document.getElementById('odps-feedback');
    const showFeedback = (message, level = 'success') => {
        if (!feedbackContainer || !message) {
            return;
        }

        feedbackContainer.innerHTML = '<div class="alert alert-' + level + '">' + message + '</div>';
    };

    const statusBadge = (status) => {
        if (status === 'ACTIVE') {
            return '<span class="cm-badge cm-badge-success">ACTIVE</span>';
        }
        if (status === 'MAINTENANCE') {
            return '<span class="cm-badge cm-badge-warning">MAINTENANCE</span>';
        }
        return '<span class="cm-badge cm-badge-neutral">INACTIVE</span>';
    };

    const table = $('#odps-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: @json(route('super-admin.odps.datatable')),
        columns: [
            { data: 'code', orderable: false },
            { data: 'name' },
            { data: 'area' },
            { data: 'coordinates', orderable: false, searchable: false },
            { data: 'ports', orderable: false, searchable: false },
            { data: 'status', orderable: false, render: function (value) { return statusBadge(value); } },
            { data: 'aksi', orderable: false, searchable: false, className: 'text-right' },
        ],
        pageLength: 20,
        language: {
            search: 'Cari:',
            lengthMenu: 'Tampilkan _MENU_ data',
            info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
            infoEmpty: 'Tidak ada data',
            infoFiltered: '(disaring dari _MAX_ total data)',
            zeroRecords: 'Tidak ada data yang cocok.',
            emptyTable: 'Belum ada data ODP.',
            paginate: { first: 'Pertama', last: 'Terakhir', next: 'Selanjutnya', previous: 'Sebelumnya' },
            processing: 'Memuat...',
        },
        order: [[1, 'asc']],
    });

    $(document).on('click', '[data-ajax-delete]', async function () {
        if (this.hasAttribute('disabled')) {
            showFeedback('ODP yang sudah terhubung ke pelanggan tidak bisa dihapus.', 'warning');
            return;
        }

        if (!window.confirm('Hapus data ODP ini?')) {
            return;
        }

        const response = await fetch(this.dataset.ajaxDelete, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        });

        const result = await response.json().catch(() => ({}));

        if (!response.ok) {
            showFeedback(result.status || 'Proses hapus gagal.', 'danger');
            return;
        }

        showFeedback(result.status || 'Data ODP berhasil dihapus.');
        table.ajax.reload(null, false);
    });
});
</script>
@endpush
