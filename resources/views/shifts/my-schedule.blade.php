@extends('layouts.admin')

@section('title', 'Jadwal Shift Saya')

@section('content')
    <div class="container py-3">
        <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
            <div>
                <h1 class="h3 mb-1">Jadwal Shift Saya</h1>
                <p class="text-muted mb-0">Lihat jadwal 14 hari ke depan dan ajukan tukar shift bila diperlukan.</p>
            </div>
            @if(auth()->user()->isSuperAdmin() || auth()->user()->role === \App\Models\User::ROLE_ADMINISTRATOR)
                <a href="{{ route('shifts.index') }}" class="btn btn-outline-primary btn-sm mt-2 mt-md-0">Kelola Shift</a>
            @endif
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row">
            <div class="col-lg-4 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Ajukan Tukar Shift</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('shifts.swap-requests.store') }}">
                            @csrf
                            <div class="form-group">
                                <label>Shift Saya</label>
                                <select name="from_schedule_id" class="form-control" required>
                                    <option value="">Pilih Shift</option>
                                    @foreach($userSchedules as $schedule)
                                        <option value="{{ $schedule->id }}">
                                            {{ $schedule->schedule_date?->format('Y-m-d') }} - {{ $schedule->shiftDefinition?->name ?? '-' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Target Petugas</label>
                                <select name="target_id" class="form-control">
                                    <option value="">Tidak ditentukan</option>
                                    @foreach($staffUsers as $staffUser)
                                        <option value="{{ $staffUser->id }}">{{ $staffUser->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Jadwal Tujuan</label>
                                <select name="to_schedule_id" class="form-control">
                                    <option value="">Tidak ditentukan</option>
                                    @foreach($candidateSchedules as $candidateSchedule)
                                        <option value="{{ $candidateSchedule->id }}">
                                            {{ $candidateSchedule->schedule_date?->format('Y-m-d') }} - {{ $candidateSchedule->user?->name ?? '-' }} - {{ $candidateSchedule->shiftDefinition?->name ?? '-' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Alasan</label>
                                <textarea name="reason" rows="3" class="form-control" placeholder="Opsional"></textarea>
                            </div>
                            <button type="submit" class="btn btn-warning btn-block">Kirim Permintaan</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 mb-3">
                <div class="card mb-3">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Jadwal 14 Hari ke Depan</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Shift</th>
                                    <th>Jam</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($userSchedules as $schedule)
                                    <tr>
                                        <td>{{ $schedule->schedule_date?->translatedFormat('d M Y') }}</td>
                                        <td>{{ $schedule->shiftDefinition?->name ?? '-' }}</td>
                                        <td>{{ $schedule->shiftDefinition?->start_time ?? '-' }} - {{ $schedule->shiftDefinition?->end_time ?? '-' }}</td>
                                        <td>{{ ucfirst($schedule->status) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Belum ada jadwal shift.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Riwayat Permintaan Tukar</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Diajukan</th>
                                    <th>Shift Asal</th>
                                    <th>Status</th>
                                    <th>Reviewer</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($swapRequests as $swapRequest)
                                    <tr>
                                        <td>{{ $swapRequest->created_at?->format('Y-m-d H:i') }}</td>
                                        <td>
                                            {{ $swapRequest->fromSchedule?->schedule_date?->format('Y-m-d') ?: '-' }}
                                            <div class="small text-muted">{{ $swapRequest->fromSchedule?->shiftDefinition?->name ?? '-' }}</div>
                                        </td>
                                        <td>{{ ucfirst($swapRequest->status) }}</td>
                                        <td>{{ $swapRequest->reviewedBy?->name ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Belum ada permintaan tukar.</td>
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
