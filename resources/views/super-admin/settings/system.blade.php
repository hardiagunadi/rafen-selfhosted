@extends('layouts.admin')

@section('title', 'Pengaturan Sistem')

@section('content')
    <div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="row">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Profil Bisnis</h3>
                    </div>
                    <form action="{{ route('super-admin.settings.system.update-business') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="form-group">
                                <label for="business_name">Nama Bisnis</label>
                                <input id="business_name" type="text" name="business_name" class="form-control" value="{{ old('business_name', $settings->business_name) }}">
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="business_phone">Nomor Telepon</label>
                                    <input id="business_phone" type="text" name="business_phone" class="form-control" value="{{ old('business_phone', $settings->business_phone) }}">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="business_email">Email Bisnis</label>
                                    <input id="business_email" type="email" name="business_email" class="form-control" value="{{ old('business_email', $settings->business_email) }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="website">Website</label>
                                <input id="website" type="url" name="website" class="form-control" value="{{ old('website', $settings->website) }}">
                            </div>
                            <div class="form-group">
                                <label for="business_address">Alamat Bisnis</label>
                                <textarea id="business_address" name="business_address" rows="3" class="form-control">{{ old('business_address', $settings->business_address) }}</textarea>
                            </div>
                            <div class="form-group">
                                <label for="portal_title">Judul Portal</label>
                                <input id="portal_title" type="text" name="portal_title" class="form-control" value="{{ old('portal_title', $settings->portal_title) }}">
                            </div>
                            <div class="form-group mb-0">
                                <label for="portal_description">Deskripsi Portal</label>
                                <textarea id="portal_description" name="portal_description" rows="3" class="form-control">{{ old('portal_description', $settings->portal_description) }}</textarea>
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <button type="submit" class="btn btn-primary">Simpan Profil</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Logo & PWA</h3>
                    </div>
                    <div class="card-body">
                        @if($settings->business_logo)
                            <div class="mb-3">
                                <img src="{{ asset('storage/'.$settings->business_logo) }}" alt="Logo bisnis" style="max-height: 72px; max-width: 220px;">
                            </div>
                        @endif

                        <form action="{{ route('super-admin.settings.system.upload-logo') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label for="business_logo">Unggah Logo Bisnis</label>
                                <input id="business_logo" type="file" name="business_logo" class="form-control-file" required>
                                <small class="form-text text-muted">Logo ini akan dipakai untuk ikon PWA admin dan portal.</small>
                            </div>
                            <button type="submit" class="btn btn-outline-primary">Unggah Logo</button>
                        </form>

                        <hr>

                        <div class="small text-muted">
                            <div>Manifest Admin: <a href="{{ route('manifest.admin') }}" target="_blank">{{ route('manifest.admin') }}</a></div>
                            <div>Manifest Portal: <a href="{{ route('portal.manifest') }}" target="_blank">{{ route('portal.manifest') }}</a></div>
                            <div>VAPID Public Key: {{ config('push.vapid.public_key') !== '' ? 'Terkonfigurasi' : 'Belum diatur' }}</div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">Halaman Isolir</h3>
                        <a href="{{ route('super-admin.settings.system.isolir-preview') }}" class="btn btn-outline-secondary btn-sm" target="_blank">Preview</a>
                    </div>
                    <form action="{{ route('super-admin.settings.system.update-isolir') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="form-group">
                                <label for="isolir_page_title">Judul</label>
                                <input id="isolir_page_title" type="text" name="isolir_page_title" class="form-control" value="{{ old('isolir_page_title', $settings->isolir_page_title) }}">
                            </div>
                            <div class="form-group">
                                <label for="isolir_page_body">Pesan</label>
                                <textarea id="isolir_page_body" name="isolir_page_body" rows="4" class="form-control">{{ old('isolir_page_body', $settings->isolir_page_body) }}</textarea>
                            </div>
                            <div class="form-group">
                                <label for="isolir_page_contact">Kontak</label>
                                <input id="isolir_page_contact" type="text" name="isolir_page_contact" class="form-control" value="{{ old('isolir_page_contact', $settings->isolir_page_contact) }}">
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="isolir_page_bg_color">Warna Background</label>
                                    <input id="isolir_page_bg_color" type="text" name="isolir_page_bg_color" class="form-control" value="{{ old('isolir_page_bg_color', $settings->isolir_page_bg_color ?: '#1a1a2e') }}">
                                </div>
                                <div class="form-group col-md-6 mb-0">
                                    <label for="isolir_page_accent_color">Warna Aksen</label>
                                    <input id="isolir_page_accent_color" type="text" name="isolir_page_accent_color" class="form-control" value="{{ old('isolir_page_accent_color', $settings->isolir_page_accent_color ?: '#e94560') }}">
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <button type="submit" class="btn btn-primary">Simpan Halaman Isolir</button>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Update Manual</h3>
                    </div>
                    <form action="{{ route('super-admin.settings.system.update-notice') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="alert alert-light border">
                                Fitur ini hanya menampilkan notifikasi update untuk pengguna self-hosted. Proses update tetap dilakukan manual saat maintenance window agar layanan tidak terganggu.
                            </div>
                            <div class="form-group">
                                <label>Versi Terpasang</label>
                                <input type="text" class="form-control" value="{{ $settings->installedVersion() }}" readonly>
                            </div>
                            <div class="custom-control custom-switch mb-3">
                                <input id="update_is_active" type="checkbox" name="update_is_active" value="1" class="custom-control-input" @checked(old('update_is_active', $settings->update_is_active))>
                                <label class="custom-control-label" for="update_is_active">Aktifkan notifikasi update manual</label>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="update_available_version">Versi Tersedia</label>
                                    <input id="update_available_version" type="text" name="update_available_version" class="form-control" value="{{ old('update_available_version', $settings->update_available_version) }}" placeholder="Contoh: 2026.03.30-sh.5">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="update_severity">Tingkat Urgensi</label>
                                    <select id="update_severity" name="update_severity" class="form-control">
                                        <option value="">Warning</option>
                                        @foreach(['info' => 'Info', 'warning' => 'Warning', 'danger' => 'Penting'] as $severityKey => $severityLabel)
                                            <option value="{{ $severityKey }}" @selected(old('update_severity', $settings->update_severity) === $severityKey)>{{ $severityLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="update_available_at">Tersedia Sejak</label>
                                    <input id="update_available_at" type="datetime-local" name="update_available_at" class="form-control" value="{{ old('update_available_at', $settings->update_available_at?->format('Y-m-d\\TH:i')) }}">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="update_release_notes_url">Tautan Catatan Rilis</label>
                                    <input id="update_release_notes_url" type="url" name="update_release_notes_url" class="form-control" value="{{ old('update_release_notes_url', $settings->update_release_notes_url) }}" placeholder="https://...">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="update_headline">Judul Notifikasi</label>
                                <input id="update_headline" type="text" name="update_headline" class="form-control" value="{{ old('update_headline', $settings->update_headline) }}" placeholder="Contoh: Update stabilitas tersedia">
                            </div>
                            <div class="form-group">
                                <label for="update_summary">Ringkasan</label>
                                <textarea id="update_summary" name="update_summary" rows="3" class="form-control">{{ old('update_summary', $settings->update_summary) }}</textarea>
                            </div>
                            <div class="form-group mb-0">
                                <label for="update_instructions">Instruksi Manual</label>
                                <textarea id="update_instructions" name="update_instructions" rows="3" class="form-control">{{ old('update_instructions', $settings->update_instructions) }}</textarea>
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <button type="submit" class="btn btn-primary">Simpan Notifikasi Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
