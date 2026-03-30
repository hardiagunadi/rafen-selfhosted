@extends('layouts.admin')

@section('title', 'Backup Database')

@section('content')
    <div class="container">
        <div class="mb-3">
            <h1 class="h3 mb-1">Backup Database</h1>
            <p class="text-muted mb-0">Kelola snapshot database self-hosted dan restore dari file backup.</p>
        </div>

        <div class="row">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">Buat Backup Baru</h3>
                        <button type="button" class="btn btn-primary btn-sm" id="btn-backup">
                            Backup Sekarang
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="backup-status"></div>
                        <p class="text-muted small mb-0">
                            Backup disimpan sebagai snapshot terkompresi `json.gz` di server.
                        </p>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Restore dari File</h3>
                    </div>
                    <div class="card-body">
                        <form id="restore-form" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label for="restore-file">File Backup</label>
                                <input type="file" id="restore-file" name="file" class="form-control-file" required>
                            </div>
                            <button type="submit" class="btn btn-danger btn-block">Restore Database</button>
                        </form>
                        <div id="restore-status" class="mt-2"></div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Ekspor Transaksi</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('super-admin.tools.export-transactions.download') }}" method="GET">
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="date_from">Dari</label>
                                    <input type="date" id="date_from" name="date_from" class="form-control">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="date_to">Sampai</label>
                                    <input type="date" id="date_to" name="date_to" class="form-control">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="status">Status</label>
                                    <select id="status" name="status" class="form-control">
                                        <option value="">Semua</option>
                                        <option value="paid">Paid</option>
                                        <option value="unpaid">Unpaid</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-outline-primary btn-block">Download CSV</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">File Backup Tersimpan</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Nama File</th>
                                        <th>Ukuran</th>
                                        <th>Dibuat</th>
                                        <th class="text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="backup-table-body">
                                    @forelse($files as $file)
                                        <tr>
                                            <td><code class="small">{{ $file['name'] }}</code></td>
                                            <td class="small">{{ $file['size'] }}</td>
                                            <td class="small text-muted">{{ $file['modified'] }}</td>
                                            <td class="text-right">
                                                <a href="{{ route('super-admin.tools.backup.download', ['file' => $file['name']]) }}" class="btn btn-xs btn-outline-primary">Download</a>
                                                <button type="button" class="btn btn-xs btn-outline-danger btn-delete-backup" data-file="{{ $file['name'] }}">Hapus</button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-3">Belum ada file backup.</td>
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
        $('#btn-backup').on('click', function () {
            const button = $(this);
            const status = $('#backup-status');

            button.prop('disabled', true).text('Memproses...');
            status.html('');

            $.post(@json(route('super-admin.tools.backup.create')), {_token: @json(csrf_token())})
                .done(function (response) {
                    status.html('<div class="alert alert-success py-2 mb-0">' + response.status + ' Reload halaman untuk melihat file baru.</div>');
                })
                .fail(function () {
                    status.html('<div class="alert alert-danger py-2 mb-0">Backup gagal dibuat.</div>');
                })
                .always(function () {
                    button.prop('disabled', false).text('Backup Sekarang');
                });
        });

        $('#restore-form').on('submit', function (event) {
            event.preventDefault();

            if (!confirm('Restore akan menimpa isi database saat ini. Lanjutkan?')) {
                return;
            }

            const status = $('#restore-status');
            const formData = new FormData(this);

            status.html('');

            $.ajax({
                url: @json(route('super-admin.tools.backup.restore')),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {'X-CSRF-TOKEN': @json(csrf_token())},
            }).done(function (response) {
                status.html('<div class="alert alert-success py-2 mb-0">' + response.status + '</div>');
            }).fail(function (xhr) {
                const message = xhr.responseJSON?.error || 'Restore gagal.';
                status.html('<div class="alert alert-danger py-2 mb-0">' + message + '</div>');
            });
        });

        $('.btn-delete-backup').on('click', function () {
            const file = $(this).data('file');
            const row = $(this).closest('tr');

            if (!confirm('Hapus file backup ' + file + '?')) {
                return;
            }

            $.ajax({
                url: @json(route('super-admin.tools.backup.delete')),
                method: 'DELETE',
                data: JSON.stringify({file: file}),
                contentType: 'application/json',
                headers: {'X-CSRF-TOKEN': @json(csrf_token())},
            }).done(function () {
                row.remove();
            });
        });
    </script>
@endpush
