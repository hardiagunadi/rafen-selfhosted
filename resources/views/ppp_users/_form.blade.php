@php($editing = isset($pppUser))

<div class="form-row">
    <div class="form-group col-md-2">
        <label for="customer_id">ID Pelanggan</label>
        <div class="input-group">
            <input type="text" id="customer_id" name="customer_id" class="form-control" value="{{ old('customer_id', $pppUser->customer_id ?? '') }}">
            <div class="input-group-append">
                <button type="button" class="btn btn-outline-secondary" data-generate-customer-id>Auto</button>
            </div>
        </div>
    </div>
    <div class="form-group col-md-4">
        <label for="customer_name">Nama</label>
        <input type="text" id="customer_name" name="customer_name" class="form-control" value="{{ old('customer_name', $pppUser->customer_name ?? '') }}">
    </div>
    <div class="form-group col-md-3">
        <label for="ppp_profile_id">Paket</label>
        <select id="ppp_profile_id" name="ppp_profile_id" class="form-control">
            @foreach($pppProfiles as $pppProfile)
                <option value="{{ $pppProfile->id }}" @selected((string) old('ppp_profile_id', $pppUser->ppp_profile_id ?? '') === (string) $pppProfile->id)>{{ $pppProfile->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-3">
        <label for="profile_group_id">Profile Group</label>
        <select id="profile_group_id" name="profile_group_id" class="form-control">
            <option value="">Ikuti Paket</option>
            @foreach($profileGroups as $profileGroup)
                <option value="{{ $profileGroup->id }}" @selected((string) old('profile_group_id', $pppUser->profile_group_id ?? '') === (string) $profileGroup->id)>{{ $profileGroup->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-2">
        <label for="odp_id">ODP</label>
        <select id="odp_id" name="odp_id" class="form-control">
            <option value="">- Pilih ODP -</option>
            @foreach($odps as $odp)
                <option value="{{ $odp->id }}" @selected((string) old('odp_id', $pppUser->odp_id ?? '') === (string) $odp->id)>{{ $odp->code }} - {{ $odp->name }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="form-row">
    <div class="form-group col-md-2">
        <label for="username">Username PPP</label>
        <input type="text" id="username" name="username" class="form-control" value="{{ old('username', $pppUser->username ?? '') }}">
    </div>
    <div class="form-group col-md-2">
        <label for="ppp_password">Password PPP</label>
        <input type="text" id="ppp_password" name="ppp_password" class="form-control" value="{{ old('ppp_password', $pppUser->ppp_password ?? '') }}">
    </div>
    <div class="form-group col-md-2">
        <label for="password_clientarea">Password Portal</label>
        <input type="text" id="password_clientarea" name="password_clientarea" class="form-control" value="{{ old('password_clientarea', $pppUser->password_clientarea ?? '') }}">
    </div>
    <div class="form-group col-md-2">
        <label for="metode_login">Metode Login</label>
        <select id="metode_login" name="metode_login" class="form-control">
            <option value="username_password" @selected(old('metode_login', $pppUser->metode_login ?? 'username_password') === 'username_password')>Username & Password</option>
            <option value="username_equals_password" @selected(old('metode_login', $pppUser->metode_login ?? '') === 'username_equals_password')>Username = Password</option>
        </select>
    </div>
    <div class="form-group col-md-2">
        <label for="tipe_service">Tipe Service</label>
        <select id="tipe_service" name="tipe_service" class="form-control">
            @foreach(['pppoe', 'l2tp_pptp', 'openvpn_sstp'] as $tipeService)
                <option value="{{ $tipeService }}" @selected(old('tipe_service', $pppUser->tipe_service ?? 'pppoe') === $tipeService)>{{ strtoupper(str_replace('_', '/', $tipeService)) }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-2">
        <label for="tipe_ip">Tipe IP</label>
        <select id="tipe_ip" name="tipe_ip" class="form-control">
            <option value="dhcp" @selected(old('tipe_ip', $pppUser->tipe_ip ?? 'dhcp') === 'dhcp')>DHCP</option>
            <option value="static" @selected(old('tipe_ip', $pppUser->tipe_ip ?? '') === 'static')>Static</option>
        </select>
    </div>
</div>
<div class="form-row">
    <div class="form-group col-md-2">
        <label for="ip_static">IP Static</label>
        <input type="text" id="ip_static" name="ip_static" class="form-control" value="{{ old('ip_static', $pppUser->ip_static ?? '') }}">
    </div>
    <div class="form-group col-md-2">
        <label for="odp_pop">ODP / POP Manual</label>
        <input type="text" id="odp_pop" name="odp_pop" class="form-control" value="{{ old('odp_pop', $pppUser->odp_pop ?? '') }}">
    </div>
    <div class="form-group col-md-2">
        <label for="nomor_hp">Nomor HP</label>
        <input type="text" id="nomor_hp" name="nomor_hp" class="form-control" value="{{ old('nomor_hp', $pppUser->nomor_hp ?? '') }}">
    </div>
    <div class="form-group col-md-3">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $pppUser->email ?? '') }}">
    </div>
    <div class="form-group col-md-2">
        <label for="nik">NIK</label>
        <input type="text" id="nik" name="nik" class="form-control" value="{{ old('nik', $pppUser->nik ?? '') }}">
    </div>
    <div class="form-group col-md-1">
        <label for="status_akun">Akun</label>
        <select id="status_akun" name="status_akun" class="form-control">
            @foreach(['enable', 'disable', 'isolir'] as $statusAkun)
                <option value="{{ $statusAkun }}" @selected(old('status_akun', $pppUser->status_akun ?? 'enable') === $statusAkun)>{{ ucfirst($statusAkun) }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-1">
        <label for="status_bayar">Bayar</label>
        <select id="status_bayar" name="status_bayar" class="form-control">
            <option value="belum_bayar" @selected(old('status_bayar', $pppUser->status_bayar ?? 'belum_bayar') === 'belum_bayar')>Belum</option>
            <option value="sudah_bayar" @selected(old('status_bayar', $pppUser->status_bayar ?? '') === 'sudah_bayar')>Sudah</option>
        </select>
    </div>
    <div class="form-group col-md-1">
        <label for="tipe_pembayaran">Tipe</label>
        <select id="tipe_pembayaran" name="tipe_pembayaran" class="form-control">
            <option value="prepaid" @selected(old('tipe_pembayaran', $pppUser->tipe_pembayaran ?? 'prepaid') === 'prepaid')>Pre</option>
            <option value="postpaid" @selected(old('tipe_pembayaran', $pppUser->tipe_pembayaran ?? '') === 'postpaid')>Post</option>
        </select>
    </div>
</div>
<div class="form-row">
    <div class="form-group col-md-2">
        <label for="status_registrasi">Registrasi</label>
        <select id="status_registrasi" name="status_registrasi" class="form-control">
            <option value="aktif" @selected(old('status_registrasi', $pppUser->status_registrasi ?? 'aktif') === 'aktif')>Aktif</option>
            <option value="on_process" @selected(old('status_registrasi', $pppUser->status_registrasi ?? '') === 'on_process')>On Process</option>
        </select>
    </div>
    <div class="form-group col-md-2">
        <label for="aksi_jatuh_tempo">Aksi Jatuh Tempo</label>
        <select id="aksi_jatuh_tempo" name="aksi_jatuh_tempo" class="form-control">
            <option value="isolir" @selected(old('aksi_jatuh_tempo', $pppUser->aksi_jatuh_tempo ?? 'isolir') === 'isolir')>Isolir</option>
            <option value="tetap_terhubung" @selected(old('aksi_jatuh_tempo', $pppUser->aksi_jatuh_tempo ?? '') === 'tetap_terhubung')>Tetap Terhubung</option>
        </select>
    </div>
    <div class="form-group col-md-2">
        <label for="jatuh_tempo">Jatuh Tempo</label>
        <input type="date" id="jatuh_tempo" name="jatuh_tempo" class="form-control" value="{{ old('jatuh_tempo', isset($pppUser) && $pppUser->jatuh_tempo ? $pppUser->jatuh_tempo->format('Y-m-d') : '') }}">
    </div>
    <div class="form-group col-md-2">
        <label for="biaya_instalasi">Biaya Instalasi</label>
        <input type="number" step="0.01" id="biaya_instalasi" name="biaya_instalasi" class="form-control" value="{{ old('biaya_instalasi', $pppUser->biaya_instalasi ?? 0) }}">
    </div>
    <div class="form-group col-md-2">
        <label for="durasi_promo_bulan">Promo Bulan</label>
        <input type="number" id="durasi_promo_bulan" name="durasi_promo_bulan" class="form-control" value="{{ old('durasi_promo_bulan', $pppUser->durasi_promo_bulan ?? 0) }}">
    </div>
    <div class="form-group col-md-2 d-flex align-items-end" style="gap: 1rem;">
        <div class="form-check">
            <input type="checkbox" id="tagihkan_ppn" name="tagihkan_ppn" value="1" class="form-check-input" @checked(old('tagihkan_ppn', $editing ? $pppUser->tagihkan_ppn : true))>
            <label for="tagihkan_ppn" class="form-check-label">PPN</label>
        </div>
        <div class="form-check">
            <input type="checkbox" id="prorata_otomatis" name="prorata_otomatis" value="1" class="form-check-input" @checked(old('prorata_otomatis', $pppUser->prorata_otomatis ?? false))>
            <label for="prorata_otomatis" class="form-check-label">Prorata</label>
        </div>
        <div class="form-check">
            <input type="checkbox" id="promo_aktif" name="promo_aktif" value="1" class="form-check-input" @checked(old('promo_aktif', $pppUser->promo_aktif ?? false))>
            <label for="promo_aktif" class="form-check-label">Promo</label>
        </div>
    </div>
</div>
<div class="form-row">
    <div class="form-group col-md-2">
        <label for="latitude">Latitude</label>
        <input type="text" id="latitude" name="latitude" class="form-control" value="{{ old('latitude', $pppUser->latitude ?? '') }}">
    </div>
    <div class="form-group col-md-2">
        <label for="longitude">Longitude</label>
        <input type="text" id="longitude" name="longitude" class="form-control" value="{{ old('longitude', $pppUser->longitude ?? '') }}">
    </div>
    <div class="form-group col-md-2">
        <label for="location_accuracy_m">Akurasi (m)</label>
        <input type="number" step="0.01" id="location_accuracy_m" name="location_accuracy_m" class="form-control" value="{{ old('location_accuracy_m', $pppUser->location_accuracy_m ?? '') }}">
    </div>
    <div class="form-group col-md-3">
        <label for="location_capture_method">Metode Lokasi</label>
        <select id="location_capture_method" name="location_capture_method" class="form-control">
            <option value="">- Pilih -</option>
            <option value="gps" @selected(old('location_capture_method', $pppUser->location_capture_method ?? '') === 'gps')>GPS</option>
            <option value="map_picker" @selected(old('location_capture_method', $pppUser->location_capture_method ?? '') === 'map_picker')>Map Picker</option>
            <option value="manual" @selected(old('location_capture_method', $pppUser->location_capture_method ?? '') === 'manual')>Manual</option>
        </select>
    </div>
    <div class="form-group col-md-3">
        <label for="location_captured_at">Waktu Capture</label>
        <input type="datetime-local" id="location_captured_at" name="location_captured_at" class="form-control" value="{{ old('location_captured_at', isset($pppUser) && $pppUser->location_captured_at ? $pppUser->location_captured_at->format('Y-m-d\\TH:i') : '') }}">
    </div>
</div>
<div class="form-group">
    <label for="alamat">Alamat</label>
    <textarea id="alamat" name="alamat" class="form-control" rows="2">{{ old('alamat', $pppUser->alamat ?? '') }}</textarea>
</div>
<div class="form-group">
    <label for="catatan">Catatan</label>
    <textarea id="catatan" name="catatan" class="form-control" rows="2">{{ old('catatan', $pppUser->catatan ?? '') }}</textarea>
</div>
