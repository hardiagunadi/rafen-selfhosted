@php($editing = isset($hotspotUser))

<div class="form-row">
    <div class="form-group col-md-2">
        <label for="customer_id">ID Pelanggan</label>
        <div class="input-group">
            <input type="text" id="customer_id" name="customer_id" class="form-control" value="{{ old('customer_id', $hotspotUser->customer_id ?? '') }}">
            <div class="input-group-append">
                <button type="button" class="btn btn-outline-secondary" data-generate-customer-id>Auto</button>
            </div>
        </div>
    </div>
    <div class="form-group col-md-4">
        <label for="customer_name">Nama</label>
        <input type="text" id="customer_name" name="customer_name" class="form-control" value="{{ old('customer_name', $hotspotUser->customer_name ?? '') }}">
    </div>
    <div class="form-group col-md-3">
        <label for="hotspot_profile_id">Paket</label>
        <select id="hotspot_profile_id" name="hotspot_profile_id" class="form-control">
            @foreach($hotspotProfiles as $hotspotProfile)
                <option value="{{ $hotspotProfile->id }}" @selected((string) old('hotspot_profile_id', $hotspotUser->hotspot_profile_id ?? '') === (string) $hotspotProfile->id)>{{ $hotspotProfile->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-3">
        <label for="profile_group_id">Profile Group</label>
        <select id="profile_group_id" name="profile_group_id" class="form-control">
            <option value="">Ikuti Paket</option>
            @foreach($profileGroups as $profileGroup)
                <option value="{{ $profileGroup->id }}" @selected((string) old('profile_group_id', $hotspotUser->profile_group_id ?? '') === (string) $profileGroup->id)>{{ $profileGroup->name }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="form-row">
    <div class="form-group col-md-2">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" class="form-control" value="{{ old('username', $hotspotUser->username ?? '') }}">
    </div>
    <div class="form-group col-md-2">
        <label for="hotspot_password">Password</label>
        <input type="text" id="hotspot_password" name="hotspot_password" class="form-control" value="{{ old('hotspot_password', $hotspotUser->hotspot_password ?? '') }}">
    </div>
    <div class="form-group col-md-2">
        <label for="metode_login">Metode Login</label>
        <select id="metode_login" name="metode_login" class="form-control">
            <option value="username_password" @selected(old('metode_login', $hotspotUser->metode_login ?? 'username_password') === 'username_password')>Username & Password</option>
            <option value="username_equals_password" @selected(old('metode_login', $hotspotUser->metode_login ?? '') === 'username_equals_password')>Username = Password</option>
        </select>
    </div>
    <div class="form-group col-md-2">
        <label for="status_akun">Akun</label>
        <select id="status_akun" name="status_akun" class="form-control">
            @foreach(['enable', 'disable', 'isolir'] as $statusAkun)
                <option value="{{ $statusAkun }}" @selected(old('status_akun', $hotspotUser->status_akun ?? 'enable') === $statusAkun)>{{ ucfirst($statusAkun) }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-2">
        <label for="status_bayar">Bayar</label>
        <select id="status_bayar" name="status_bayar" class="form-control">
            <option value="belum_bayar" @selected(old('status_bayar', $hotspotUser->status_bayar ?? 'belum_bayar') === 'belum_bayar')>Belum</option>
            <option value="sudah_bayar" @selected(old('status_bayar', $hotspotUser->status_bayar ?? '') === 'sudah_bayar')>Sudah</option>
        </select>
    </div>
    <div class="form-group col-md-2">
        <label for="tipe_pembayaran">Tipe</label>
        <select id="tipe_pembayaran" name="tipe_pembayaran" class="form-control">
            <option value="prepaid" @selected(old('tipe_pembayaran', $hotspotUser->tipe_pembayaran ?? 'prepaid') === 'prepaid')>Prepaid</option>
            <option value="postpaid" @selected(old('tipe_pembayaran', $hotspotUser->tipe_pembayaran ?? '') === 'postpaid')>Postpaid</option>
        </select>
    </div>
</div>
<div class="form-row">
    <div class="form-group col-md-2">
        <label for="status_registrasi">Registrasi</label>
        <select id="status_registrasi" name="status_registrasi" class="form-control">
            <option value="aktif" @selected(old('status_registrasi', $hotspotUser->status_registrasi ?? 'aktif') === 'aktif')>Aktif</option>
            <option value="on_process" @selected(old('status_registrasi', $hotspotUser->status_registrasi ?? '') === 'on_process')>On Process</option>
        </select>
    </div>
    <div class="form-group col-md-2">
        <label for="aksi_jatuh_tempo">Aksi Jatuh Tempo</label>
        <select id="aksi_jatuh_tempo" name="aksi_jatuh_tempo" class="form-control">
            <option value="isolir" @selected(old('aksi_jatuh_tempo', $hotspotUser->aksi_jatuh_tempo ?? 'isolir') === 'isolir')>Isolir</option>
            <option value="tetap_terhubung" @selected(old('aksi_jatuh_tempo', $hotspotUser->aksi_jatuh_tempo ?? '') === 'tetap_terhubung')>Tetap Terhubung</option>
        </select>
    </div>
    <div class="form-group col-md-2">
        <label for="jatuh_tempo">Jatuh Tempo</label>
        <input type="date" id="jatuh_tempo" name="jatuh_tempo" class="form-control" value="{{ old('jatuh_tempo', isset($hotspotUser) && $hotspotUser->jatuh_tempo ? $hotspotUser->jatuh_tempo->format('Y-m-d') : '') }}">
    </div>
    <div class="form-group col-md-2">
        <label for="biaya_instalasi">Biaya Instalasi</label>
        <input type="number" step="0.01" id="biaya_instalasi" name="biaya_instalasi" class="form-control" value="{{ old('biaya_instalasi', $hotspotUser->biaya_instalasi ?? 0) }}">
    </div>
    <div class="form-group col-md-2">
        <label for="nomor_hp">Nomor HP</label>
        <input type="text" id="nomor_hp" name="nomor_hp" class="form-control" value="{{ old('nomor_hp', $hotspotUser->nomor_hp ?? '') }}">
    </div>
    <div class="form-group col-md-2 d-flex align-items-end">
        <div class="form-check">
            <input type="checkbox" id="tagihkan_ppn" name="tagihkan_ppn" value="1" class="form-check-input" @checked(old('tagihkan_ppn', $hotspotUser->tagihkan_ppn ?? false))>
            <label for="tagihkan_ppn" class="form-check-label">Tagihkan PPN</label>
        </div>
    </div>
</div>
<div class="form-row">
    <div class="form-group col-md-3">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $hotspotUser->email ?? '') }}">
    </div>
    <div class="form-group col-md-3">
        <label for="nik">NIK</label>
        <input type="text" id="nik" name="nik" class="form-control" value="{{ old('nik', $hotspotUser->nik ?? '') }}">
    </div>
</div>
<div class="form-group">
    <label for="alamat">Alamat</label>
    <textarea id="alamat" name="alamat" class="form-control" rows="2">{{ old('alamat', $hotspotUser->alamat ?? '') }}</textarea>
</div>
<div class="form-group">
    <label for="catatan">Catatan</label>
    <textarea id="catatan" name="catatan" class="form-control" rows="2">{{ old('catatan', $hotspotUser->catatan ?? '') }}</textarea>
</div>
