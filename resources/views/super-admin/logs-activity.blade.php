@extends('layouts.admin')

@section('title', 'Log Aktivitas')

@section('content')
    <div class="container">
        <div class="mb-3">
            <h1 class="h3 mb-1">Log Aktivitas</h1>
            <p class="text-muted mb-0">Riwayat tindakan admin pada instalasi self-hosted.</p>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Waktu</th>
                                <th>Pengguna</th>
                                <th>Aksi</th>
                                <th>Objek</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                <tr>
                                    <td>{{ $log->created_at?->format('Y-m-d H:i:s') ?: '-' }}</td>
                                    <td>{{ $log->user?->name ?: 'Sistem' }}</td>
                                    <td><span class="badge badge-info">{{ $log->action }}</span></td>
                                    <td>
                                        <div class="text-muted small">{{ $log->subject_type ?: '-' }}</div>
                                        <div>{{ $log->subject_label ?: '-' }}</div>
                                    </td>
                                    <td>{{ $log->ip_address ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">Belum ada log aktivitas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
