@extends('layouts.admin')

@section('title', 'Detail Gangguan')

@section('content')
    <div class="container">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 mb-1">{{ $outage->title }}</h1>
                <p class="text-muted mb-0">
                    <span class="badge {{ $outage->status === 'resolved' ? 'badge-success' : ($outage->status === 'in_progress' ? 'badge-warning' : 'badge-danger') }}">
                        {{ strtoupper(str_replace('_', ' ', $outage->status)) }}
                    </span>
                </p>
            </div>
            <div class="d-flex" style="gap: .5rem;">
                <a href="{{ route('super-admin.outages.index') }}" class="btn btn-outline-secondary">Kembali</a>
                <a href="{{ route('outage.public-status', $outage->public_token) }}" target="_blank" class="btn btn-outline-info">Halaman Publik</a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Update Gangguan</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('super-admin.outages.updates.store', $outage) }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="body">Pesan Update</label>
                                <textarea id="body" name="body" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="change_status">Ubah Status</label>
                                    <select id="change_status" name="change_status" class="form-control">
                                        <option value="">Tanpa perubahan</option>
                                        <option value="open">OPEN</option>
                                        <option value="in_progress">IN PROGRESS</option>
                                        <option value="resolved">RESOLVED</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="meta">Meta</label>
                                    <input type="text" id="meta" name="meta" class="form-control">
                                </div>
                                <div class="form-group col-md-4 d-flex align-items-end">
                                    <div class="form-check">
                                        <input type="checkbox" id="is_public" name="is_public" value="1" class="form-check-input" checked>
                                        <label for="is_public" class="form-check-label">Tampilkan ke pelanggan</label>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Kirim Update</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Riwayat</h3>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-column" style="gap: 1rem;">
                            @forelse($outage->updates->sortByDesc('created_at') as $update)
                                <div class="border rounded p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <strong>{{ strtoupper($update->type) }}</strong>
                                            @if(!$update->is_public)
                                                <span class="badge badge-secondary ml-1">Internal</span>
                                            @endif
                                        </div>
                                        <small class="text-muted">{{ $update->created_at->format('d-m-Y H:i') }}</small>
                                    </div>
                                    @if($update->meta)
                                        <div class="text-muted small mb-1">{{ $update->meta }}</div>
                                    @endif
                                    <div>{{ $update->body ?: '-' }}</div>
                                </div>
                            @empty
                                <div class="text-muted">Belum ada update.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Informasi Gangguan</h3>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tr>
                                <td class="text-muted">Severity</td>
                                <td class="text-right">{{ strtoupper($outage->severity) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Mulai</td>
                                <td class="text-right">{{ $outage->started_at?->format('d-m-Y H:i') ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Estimasi</td>
                                <td class="text-right">{{ $outage->estimated_resolved_at?->format('d-m-Y H:i') ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Selesai</td>
                                <td class="text-right">{{ $outage->resolved_at?->format('d-m-Y H:i') ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Status Link</td>
                                <td class="text-right">{{ $outage->include_status_link ? 'Aktif' : 'Nonaktif' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Area Terdampak</h3>
                    </div>
                    <div class="card-body">
                        @forelse($outage->affectedAreas as $area)
                            <span class="badge badge-info mb-1">{{ $area->display_label }}</span>
                        @empty
                            <div class="text-muted">Belum ada area terdaftar.</div>
                        @endforelse
                    </div>
                </div>

                @if(!$outage->isResolved())
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('super-admin.outages.resolve', $outage) }}" method="POST" onsubmit="return confirm('Tandai gangguan ini selesai?');">
                                @csrf
                                <button type="submit" class="btn btn-success btn-block">Tandai Selesai</button>
                            </form>
                            <form action="{{ route('super-admin.outages.destroy', $outage) }}" method="POST" class="mt-2" onsubmit="return confirm('Hapus gangguan ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-block">Hapus Gangguan</button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
