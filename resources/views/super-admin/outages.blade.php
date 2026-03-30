@extends('layouts.admin')

@section('title', 'Gangguan Jaringan')

@section('content')
    <div class="container">
        <div class="mb-3">
            <h1 class="h3 mb-1">Gangguan Jaringan</h1>
            <p class="text-muted mb-0">Kelola insiden jaringan dan bagikan status publik ke pelanggan.</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0">Laporkan Gangguan</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('super-admin.outages.store') }}" method="POST">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="title">Judul</label>
                            <input type="text" id="title" name="title" class="form-control" value="{{ old('title') }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="severity">Severity</label>
                            <select id="severity" name="severity" class="form-control">
                                @foreach(['critical', 'high', 'medium', 'low'] as $severity)
                                    <option value="{{ $severity }}" @selected(old('severity', 'high') === $severity)>{{ strtoupper($severity) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="started_at">Mulai</label>
                            <input type="datetime-local" id="started_at" name="started_at" class="form-control" value="{{ old('started_at', now()->format('Y-m-d\TH:i')) }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="estimated_resolved_at">Estimasi Selesai</label>
                            <input type="datetime-local" id="estimated_resolved_at" name="estimated_resolved_at" class="form-control" value="{{ old('estimated_resolved_at') }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="description">Deskripsi</label>
                        <textarea id="description" name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="area_labels">Area Terdampak</label>
                        <textarea id="area_labels" name="area_labels" class="form-control" rows="2" placeholder="Satu area per baris atau pisahkan dengan koma">{{ old('area_labels') }}</textarea>
                        <small class="text-muted">Form ini menerima area bebas seperti nama desa, cluster, atau wilayah gangguan.</small>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" id="include_status_link" name="include_status_link" value="1" class="form-check-input" @checked(old('include_status_link', true))>
                        <label for="include_status_link" class="form-check-label">Tampilkan link status publik</label>
                    </div>
                    <button type="submit" class="btn btn-danger">Simpan Gangguan</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Daftar Gangguan</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Judul</th>
                                <th>Severity</th>
                                <th>Status</th>
                                <th>Mulai</th>
                                <th>Area</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($outages as $outage)
                                <tr>
                                    <td>
                                        <div class="font-weight-bold">{{ $outage->title }}</div>
                                        <div class="text-muted small">{{ $outage->description }}</div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $outage->severity === 'critical' ? 'badge-danger' : ($outage->severity === 'high' ? 'badge-warning' : ($outage->severity === 'medium' ? 'badge-info' : 'badge-secondary')) }}">
                                            {{ strtoupper($outage->severity) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $outage->status === 'resolved' ? 'badge-success' : ($outage->status === 'in_progress' ? 'badge-warning' : 'badge-danger') }}">
                                            {{ strtoupper(str_replace('_', ' ', $outage->status)) }}
                                        </span>
                                    </td>
                                    <td>{{ $outage->started_at?->format('d-m-Y H:i') ?: '-' }}</td>
                                    <td>{{ $outage->affectedAreas->count() }} area</td>
                                    <td class="text-right">
                                        <a href="{{ route('super-admin.outages.show', $outage) }}" class="btn btn-outline-info btn-sm">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">Belum ada gangguan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
