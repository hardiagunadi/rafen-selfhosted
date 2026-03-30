@extends('portal.layout')

@section('title', 'Akun Portal')

@section('content')
    <div class="mb-3">
        <h1 class="h3 mb-1">Akun Portal</h1>
        <p class="text-muted mb-0">Informasi pelanggan dan pengaturan password portal.</p>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h3 class="card-title mb-0">Profil Pelanggan</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tr>
                            <th class="pl-3" style="width: 150px;">Nama</th>
                            <td>{{ $pppUser->customer_name ?: '-' }}</td>
                        </tr>
                        <tr>
                            <th class="pl-3">ID Pelanggan</th>
                            <td>{{ $pppUser->customer_id ?: '-' }}</td>
                        </tr>
                        <tr>
                            <th class="pl-3">Email</th>
                            <td>{{ $pppUser->email ?: '-' }}</td>
                        </tr>
                        <tr>
                            <th class="pl-3">Nomor HP</th>
                            <td>{{ $pppUser->nomor_hp ?: '-' }}</td>
                        </tr>
                        <tr>
                            <th class="pl-3">Alamat</th>
                            <td>{{ $pppUser->alamat ?: '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h3 class="card-title mb-0">Ganti Password</h3>
                </div>
                <div class="card-body">
                    <div id="password-alert"></div>
                    <form id="change-password-form">
                        @csrf
                        <div class="form-group">
                            <label for="current_password">Password Lama</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">Password Baru</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password_confirmation">Konfirmasi Password Baru</label>
                            <input type="password" id="new_password_confirmation" name="new_password_confirmation" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Simpan Password Baru</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        $('#change-password-form').on('submit', function (event) {
            event.preventDefault();

            const alertBox = $('#password-alert');

            $.ajax({
                url: @json(route('portal.change-password')),
                method: 'POST',
                data: $(this).serialize(),
                success: function (response) {
                    alertBox.html('<div class="alert alert-success mb-3">' + response.message + '</div>');
                    $('#change-password-form')[0].reset();
                },
                error: function (xhr) {
                    const message = xhr.responseJSON?.message || 'Gagal mengubah password portal.';
                    alertBox.html('<div class="alert alert-danger mb-3">' + message + '</div>');
                }
            });
        });
    </script>
@endpush
