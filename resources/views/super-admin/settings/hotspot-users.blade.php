@extends('layouts.admin')

@section('title', 'Pelanggan Hotspot')

@section('content')
    <div class="container">
        <div class="mb-3">
            <h1 class="h3 mb-1">Pelanggan Hotspot</h1>
            <p class="text-muted mb-0">Kelola pelanggan hotspot secara global untuk instalasi self-hosted single-tenant.</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Tambah Pelanggan Hotspot</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('super-admin.settings.hotspot-users.store') }}" method="POST">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-2">
                            <label for="customer_id">ID Pelanggan</label>
                            <div class="input-group">
                                <input type="text" id="customer_id" name="customer_id" class="form-control" value="{{ old('customer_id') }}">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" data-generate-customer-id>Auto</button>
                                </div>
                            </div>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="customer_name">Nama</label>
                            <input type="text" id="customer_name" name="customer_name" class="form-control" value="{{ old('customer_name') }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="hotspot_profile_id">Paket</label>
                            <select id="hotspot_profile_id" name="hotspot_profile_id" class="form-control">
                                @foreach($hotspotProfiles as $hotspotProfile)
                                    <option value="{{ $hotspotProfile->id }}" @selected((string) old('hotspot_profile_id') === (string) $hotspotProfile->id)>{{ $hotspotProfile->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="profile_group_id">Profile Group</label>
                            <select id="profile_group_id" name="profile_group_id" class="form-control">
                                <option value="">Ikuti Paket</option>
                                @foreach($profileGroups as $profileGroup)
                                    <option value="{{ $profileGroup->id }}" @selected((string) old('profile_group_id') === (string) $profileGroup->id)>{{ $profileGroup->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-2">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" class="form-control" value="{{ old('username') }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="hotspot_password">Password</label>
                            <input type="text" id="hotspot_password" name="hotspot_password" class="form-control" value="{{ old('hotspot_password') }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="metode_login">Metode Login</label>
                            <select id="metode_login" name="metode_login" class="form-control">
                                <option value="username_password" @selected(old('metode_login', 'username_password') === 'username_password')>Username & Password</option>
                                <option value="username_equals_password" @selected(old('metode_login') === 'username_equals_password')>Username = Password</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="status_akun">Akun</label>
                            <select id="status_akun" name="status_akun" class="form-control">
                                @foreach(['enable', 'disable', 'isolir'] as $statusAkun)
                                    <option value="{{ $statusAkun }}" @selected(old('status_akun', 'enable') === $statusAkun)>{{ ucfirst($statusAkun) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="status_bayar">Bayar</label>
                            <select id="status_bayar" name="status_bayar" class="form-control">
                                <option value="belum_bayar" @selected(old('status_bayar', 'belum_bayar') === 'belum_bayar')>Belum</option>
                                <option value="sudah_bayar" @selected(old('status_bayar') === 'sudah_bayar')>Sudah</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="tipe_pembayaran">Tipe</label>
                            <select id="tipe_pembayaran" name="tipe_pembayaran" class="form-control">
                                <option value="prepaid" @selected(old('tipe_pembayaran', 'prepaid') === 'prepaid')>Prepaid</option>
                                <option value="postpaid" @selected(old('tipe_pembayaran') === 'postpaid')>Postpaid</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-2">
                            <label for="status_registrasi">Registrasi</label>
                            <select id="status_registrasi" name="status_registrasi" class="form-control">
                                <option value="aktif" @selected(old('status_registrasi', 'aktif') === 'aktif')>Aktif</option>
                                <option value="on_process" @selected(old('status_registrasi') === 'on_process')>On Process</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="aksi_jatuh_tempo">Aksi Jatuh Tempo</label>
                            <select id="aksi_jatuh_tempo" name="aksi_jatuh_tempo" class="form-control">
                                <option value="isolir" @selected(old('aksi_jatuh_tempo', 'isolir') === 'isolir')>Isolir</option>
                                <option value="tetap_terhubung" @selected(old('aksi_jatuh_tempo') === 'tetap_terhubung')>Tetap Terhubung</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="jatuh_tempo">Jatuh Tempo</label>
                            <input type="date" id="jatuh_tempo" name="jatuh_tempo" class="form-control" value="{{ old('jatuh_tempo') }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="biaya_instalasi">Biaya Instalasi</label>
                            <input type="number" step="0.01" id="biaya_instalasi" name="biaya_instalasi" class="form-control" value="{{ old('biaya_instalasi', 0) }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="nomor_hp">Nomor HP</label>
                            <input type="text" id="nomor_hp" name="nomor_hp" class="form-control" value="{{ old('nomor_hp') }}">
                        </div>
                        <div class="form-group col-md-2 d-flex align-items-end">
                            <div class="form-check">
                                <input type="checkbox" id="tagihkan_ppn" name="tagihkan_ppn" value="1" class="form-check-input" @checked(old('tagihkan_ppn'))>
                                <label for="tagihkan_ppn" class="form-check-label">Tagihkan PPN</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="nik">NIK</label>
                            <input type="text" id="nik" name="nik" class="form-control" value="{{ old('nik') }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="alamat">Alamat</label>
                        <textarea id="alamat" name="alamat" class="form-control" rows="2">{{ old('alamat') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="catatan">Catatan</label>
                        <textarea id="catatan" name="catatan" class="form-control" rows="2">{{ old('catatan') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title mb-0">Daftar Pelanggan</h3>
            </div>
            <div class="card-body">
                @if($hotspotUsers->isEmpty())
                    <div class="text-muted">Belum ada pelanggan Hotspot.</div>
                @else
                    <div class="d-flex flex-column" style="gap: 1rem;">
                        @foreach($hotspotUsers as $hotspotUser)
                            <div class="border rounded p-3">
                                <form action="{{ route('super-admin.settings.hotspot-users.update', $hotspotUser) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-row">
                                        <div class="form-group col-md-2">
                                            <label>ID Pelanggan</label>
                                            <input type="text" name="customer_id" class="form-control" value="{{ $hotspotUser->customer_id }}">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label>Nama</label>
                                            <input type="text" name="customer_name" class="form-control" value="{{ $hotspotUser->customer_name }}">
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label>Paket</label>
                                            <select name="hotspot_profile_id" class="form-control">
                                                @foreach($hotspotProfiles as $hotspotProfile)
                                                    <option value="{{ $hotspotProfile->id }}" @selected($hotspotUser->hotspot_profile_id === $hotspotProfile->id)>{{ $hotspotProfile->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label>Profile Group</label>
                                            <select name="profile_group_id" class="form-control">
                                                <option value="">Ikuti Paket</option>
                                                @foreach($profileGroups as $profileGroup)
                                                    <option value="{{ $profileGroup->id }}" @selected($hotspotUser->profile_group_id === $profileGroup->id)>{{ $profileGroup->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-2">
                                            <label>Username</label>
                                            <input type="text" name="username" class="form-control" value="{{ $hotspotUser->username }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Password</label>
                                            <input type="text" name="hotspot_password" class="form-control" value="{{ $hotspotUser->hotspot_password }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Metode Login</label>
                                            <select name="metode_login" class="form-control">
                                                <option value="username_password" @selected($hotspotUser->metode_login === 'username_password')>Username & Password</option>
                                                <option value="username_equals_password" @selected($hotspotUser->metode_login === 'username_equals_password')>Username = Password</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Akun</label>
                                            <select name="status_akun" class="form-control">
                                                @foreach(['enable', 'disable', 'isolir'] as $statusAkun)
                                                    <option value="{{ $statusAkun }}" @selected($hotspotUser->status_akun === $statusAkun)>{{ ucfirst($statusAkun) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Bayar</label>
                                            <select name="status_bayar" class="form-control">
                                                <option value="belum_bayar" @selected($hotspotUser->status_bayar === 'belum_bayar')>Belum</option>
                                                <option value="sudah_bayar" @selected($hotspotUser->status_bayar === 'sudah_bayar')>Sudah</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Tipe</label>
                                            <select name="tipe_pembayaran" class="form-control">
                                                <option value="prepaid" @selected($hotspotUser->tipe_pembayaran === 'prepaid')>Prepaid</option>
                                                <option value="postpaid" @selected($hotspotUser->tipe_pembayaran === 'postpaid')>Postpaid</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-2">
                                            <label>Registrasi</label>
                                            <select name="status_registrasi" class="form-control">
                                                <option value="aktif" @selected($hotspotUser->status_registrasi === 'aktif')>Aktif</option>
                                                <option value="on_process" @selected($hotspotUser->status_registrasi === 'on_process')>On Process</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Aksi Jatuh Tempo</label>
                                            <select name="aksi_jatuh_tempo" class="form-control">
                                                <option value="isolir" @selected($hotspotUser->aksi_jatuh_tempo === 'isolir')>Isolir</option>
                                                <option value="tetap_terhubung" @selected($hotspotUser->aksi_jatuh_tempo === 'tetap_terhubung')>Tetap Terhubung</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Jatuh Tempo</label>
                                            <input type="date" name="jatuh_tempo" class="form-control" value="{{ $hotspotUser->jatuh_tempo?->format('Y-m-d') }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Biaya Instalasi</label>
                                            <input type="number" step="0.01" name="biaya_instalasi" class="form-control" value="{{ $hotspotUser->biaya_instalasi }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Nomor HP</label>
                                            <input type="text" name="nomor_hp" class="form-control" value="{{ $hotspotUser->nomor_hp }}">
                                        </div>
                                        <div class="form-group col-md-2 d-flex align-items-end">
                                            <div class="form-check">
                                                <input type="checkbox" name="tagihkan_ppn" value="1" class="form-check-input" id="tagihkan_ppn_{{ $hotspotUser->id }}" @checked($hotspotUser->tagihkan_ppn)>
                                                <label for="tagihkan_ppn_{{ $hotspotUser->id }}" class="form-check-label">Tagihkan PPN</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-3">
                                            <label>Email</label>
                                            <input type="email" name="email" class="form-control" value="{{ $hotspotUser->email }}">
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label>NIK</label>
                                            <input type="text" name="nik" class="form-control" value="{{ $hotspotUser->nik }}">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Alamat</label>
                                        <textarea name="alamat" class="form-control" rows="2">{{ $hotspotUser->alamat }}</textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Catatan</label>
                                        <textarea name="catatan" class="form-control" rows="2">{{ $hotspotUser->catatan }}</textarea>
                                    </div>
                                    <div class="d-flex align-items-center" style="gap: 0.5rem;">
                                        <button type="submit" class="btn btn-outline-primary btn-sm">Simpan</button>
                                </form>
                                <form action="{{ route('super-admin.settings.hotspot-users.destroy', $hotspotUser) }}" method="POST" class="mb-0" onsubmit="return confirm('Hapus pelanggan Hotspot ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                                </form>
                                        <span class="badge {{ $hotspotUser->status_akun === 'enable' ? 'badge-success' : ($hotspotUser->status_akun === 'isolir' ? 'badge-warning' : 'badge-secondary') }}">
                                            {{ strtoupper($hotspotUser->status_akun) }}
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
            $('[data-generate-customer-id]').on('click', function () {
                $.get(@json(route('super-admin.settings.hotspot-users.customer-id')))
                    .done(function (response) {
                        $('#customer_id').val(response.customer_id);
                    });
            });
        });
    </script>
@endpush
