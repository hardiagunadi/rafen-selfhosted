@extends('layouts.admin')

@section('title', 'Jadwal Shift')

@section('content')
    <div class="container py-3">
        <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
            <div>
                <h1 class="h3 mb-1">Jadwal Shift</h1>
                <p class="text-muted mb-0">Kelola definisi shift, penjadwalan, dan permintaan tukar operator.</p>
            </div>
            <a href="{{ route('shifts.my') }}" class="btn btn-outline-primary btn-sm mt-2 mt-md-0">Jadwal Saya</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row">
            <div class="col-lg-4 mb-3">
                <div class="card mb-3">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Tambah Definisi Shift</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('shifts.definitions.store') }}">
                            @csrf
                            <div class="form-group">
                                <label>Nama Shift</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group col">
                                    <label>Mulai</label>
                                    <input type="time" name="start_time" class="form-control" required>
                                </div>
                                <div class="form-group col">
                                    <label>Selesai</label>
                                    <input type="time" name="end_time" class="form-control" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Role</label>
                                <input type="text" name="role" class="form-control" placeholder="Opsional">
                            </div>
                            <div class="form-group">
                                <label>Warna</label>
                                <input type="color" name="color" value="#3b82f6" class="form-control">
                            </div>
                            <div class="form-group form-check">
                                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="shift-active" checked>
                                <label class="form-check-label" for="shift-active">Aktif</label>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Simpan Definisi</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Assign Jadwal</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('shifts.schedule.store') }}">
                            @csrf
                            <div class="form-group">
                                <label>Petugas</label>
                                <select name="user_id" class="form-control" required>
                                    <option value="">Pilih Petugas</option>
                                    @foreach($staffUsers as $staffUser)
                                        <option value="{{ $staffUser->id }}">{{ $staffUser->name }} ({{ $staffUser->roleLabel() }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Shift</label>
                                <select name="shift_definition_id" class="form-control" required>
                                    <option value="">Pilih Shift</option>
                                    @foreach($definitions as $definition)
                                        <option value="{{ $definition->id }}">{{ $definition->name }} ({{ $definition->start_time }} - {{ $definition->end_time }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Tanggal</label>
                                <input type="date" name="schedule_date" value="{{ now()->toDateString() }}" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Catatan</label>
                                <input type="text" name="notes" class="form-control" placeholder="Opsional">
                            </div>
                            <button type="submit" class="btn btn-success btn-block">Simpan Jadwal</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 mb-3">
                <div class="card mb-3">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Definisi Shift</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Jam</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($definitions as $definition)
                                    <tr>
                                        <td>
                                            <span class="badge mr-1" style="background: {{ $definition->color }}; color: #fff;">&nbsp;</span>
                                            {{ $definition->name }}
                                        </td>
                                        <td>{{ $definition->start_time }} - {{ $definition->end_time }}</td>
                                        <td>{{ $definition->role ?: '-' }}</td>
                                        <td>{{ $definition->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                                        <td class="text-right">
                                            <form method="POST" action="{{ route('shifts.definitions.destroy', $definition) }}" class="d-inline" onsubmit="return confirm('Hapus definisi shift ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Belum ada definisi shift.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Jadwal 14 Hari</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Petugas</th>
                                    <th>Shift</th>
                                    <th>Status</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($schedules as $schedule)
                                    <tr>
                                        <td>{{ $schedule->schedule_date?->format('Y-m-d') }}</td>
                                        <td>{{ $schedule->user?->name ?? '-' }}</td>
                                        <td>{{ $schedule->shiftDefinition?->name ?? '-' }}</td>
                                        <td>{{ ucfirst($schedule->status) }}</td>
                                        <td class="text-right">
                                            <form method="POST" action="{{ route('shifts.schedule.destroy', $schedule) }}" class="d-inline" onsubmit="return confirm('Hapus jadwal ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Belum ada jadwal shift.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Permintaan Tukar Shift</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Pemohon</th>
                                    <th>Shift Asal</th>
                                    <th>Target</th>
                                    <th>Status</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($swapRequests as $swapRequest)
                                    <tr>
                                        <td>{{ $swapRequest->requester?->name ?? '-' }}</td>
                                        <td>
                                            {{ $swapRequest->fromSchedule?->schedule_date?->format('Y-m-d') ?: '-' }}
                                            <div class="small text-muted">{{ $swapRequest->fromSchedule?->shiftDefinition?->name ?? '-' }}</div>
                                        </td>
                                        <td>{{ $swapRequest->target?->name ?? '-' }}</td>
                                        <td>{{ ucfirst($swapRequest->status) }}</td>
                                        <td class="text-right">
                                            @if($swapRequest->status === 'pending')
                                                <form method="POST" action="{{ route('shifts.swap-requests.review', $swapRequest) }}" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-success btn-sm">Setujui</button>
                                                </form>
                                                <form method="POST" action="{{ route('shifts.swap-requests.review', $swapRequest) }}" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">Tolak</button>
                                                </form>
                                            @else
                                                <span class="text-muted small">{{ $swapRequest->reviewedBy?->name ?: '-' }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Belum ada permintaan tukar shift.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
