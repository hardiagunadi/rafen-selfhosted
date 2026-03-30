@extends('layouts.admin')

@section('title', 'Bandwidth')

@section('content')
    <div class="container">
        <div class="mb-3">
            <h1 class="h3 mb-1">Profil Bandwidth</h1>
            <p class="text-muted mb-0">Kelola profil bandwidth global untuk instalasi self-hosted single-tenant.</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Tambah Profil Bandwidth</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('super-admin.settings.bandwidth-profiles.store') }}" method="POST">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="name">Nama</label>
                            <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group col-md-2">
                            <label for="upload_min_mbps">Upload Min</label>
                            <input type="number" id="upload_min_mbps" name="upload_min_mbps" class="form-control @error('upload_min_mbps') is-invalid @enderror" value="{{ old('upload_min_mbps', 0) }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="upload_max_mbps">Upload Max</label>
                            <input type="number" id="upload_max_mbps" name="upload_max_mbps" class="form-control @error('upload_max_mbps') is-invalid @enderror" value="{{ old('upload_max_mbps', 10) }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="download_min_mbps">Download Min</label>
                            <input type="number" id="download_min_mbps" name="download_min_mbps" class="form-control @error('download_min_mbps') is-invalid @enderror" value="{{ old('download_min_mbps', 0) }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="download_max_mbps">Download Max</label>
                            <input type="number" id="download_max_mbps" name="download_max_mbps" class="form-control @error('download_max_mbps') is-invalid @enderror" value="{{ old('download_max_mbps', 20) }}">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title mb-0">Daftar Profil</h3>
            </div>
            <div class="card-body">
                @if($bandwidthProfiles->isEmpty())
                    <div class="text-muted">Belum ada profil bandwidth.</div>
                @else
                    <div class="d-flex flex-column" style="gap: 1rem;">
                        @foreach($bandwidthProfiles as $bandwidthProfile)
                            <div class="border rounded p-3">
                                <form action="{{ route('super-admin.settings.bandwidth-profiles.update', $bandwidthProfile) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-row">
                                        <div class="form-group col-md-4">
                                            <label>Nama</label>
                                            <input type="text" name="name" class="form-control" value="{{ $bandwidthProfile->name }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Upload Min</label>
                                            <input type="number" name="upload_min_mbps" class="form-control" value="{{ $bandwidthProfile->upload_min_mbps }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Upload Max</label>
                                            <input type="number" name="upload_max_mbps" class="form-control" value="{{ $bandwidthProfile->upload_max_mbps }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Download Min</label>
                                            <input type="number" name="download_min_mbps" class="form-control" value="{{ $bandwidthProfile->download_min_mbps }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Download Max</label>
                                            <input type="number" name="download_max_mbps" class="form-control" value="{{ $bandwidthProfile->download_max_mbps }}">
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center" style="gap: 0.5rem;">
                                        <button type="submit" class="btn btn-outline-primary btn-sm">Simpan</button>
                                </form>
                                <form action="{{ route('super-admin.settings.bandwidth-profiles.destroy', $bandwidthProfile) }}" method="POST" class="mb-0" onsubmit="return confirm('Hapus profil bandwidth ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                                </form>
                                    </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
