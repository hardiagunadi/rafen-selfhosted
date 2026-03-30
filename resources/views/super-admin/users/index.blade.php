@extends('layouts.admin')

@section('title', 'Pengguna Internal')

@section('content')
    <div class="container py-3">
        <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
            <div>
                <h1 class="h3 mb-1">Pengguna Internal</h1>
                <p class="text-muted mb-0">Kelola akun operator untuk instalasi self-hosted single-tenant.</p>
            </div>
            <a href="{{ route('super-admin.users.create') }}" class="btn btn-primary btn-sm mt-2 mt-md-0">Tambah Pengguna</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row mb-3">
            <div class="col-md-4 mb-2">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted small">Total Pengguna</div>
                        <div class="h3 mb-0">{{ $stats['total'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-2">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted small">Super Admin</div>
                        <div class="h3 mb-0">{{ $stats['super_admins'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-2">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted small">Operator</div>
                        <div class="h3 mb-0">{{ $stats['operators'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <form method="GET" action="{{ route('super-admin.users.index') }}" class="form-inline gap-2">
                    <input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm mr-2" placeholder="Cari nama, email, phone">
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Cari</button>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Phone</th>
                            <th>Login Terakhir</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>
                                    <div class="font-weight-semibold">{{ $user->name }}</div>
                                    <div class="small text-muted">
                                        {{ $user->nickname ?: '-' }}
                                        @if($user->isSuperAdmin())
                                            <span class="badge badge-dark ml-1">Super Admin</span>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->roleLabel() }}</td>
                                <td>{{ $user->phone ?: '-' }}</td>
                                <td>{{ $user->last_login_at?->format('Y-m-d H:i') ?: '-' }}</td>
                                <td class="text-right">
                                    <a href="{{ route('super-admin.users.edit', $user) }}" class="btn btn-warning btn-sm">Edit</a>
                                    <form action="{{ route('super-admin.users.destroy', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus pengguna ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Belum ada pengguna yang cocok.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
