@extends('layouts.admin')

@section('title', 'Edit Pengguna')

@section('content')
    <div class="container py-3">
        <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
            <div>
                <h1 class="h3 mb-1">Edit Pengguna</h1>
                <p class="text-muted mb-0">{{ $user->name }}</p>
            </div>
            <a href="{{ route('super-admin.users.index') }}" class="btn btn-outline-secondary btn-sm mt-2 mt-md-0">Kembali</a>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 pl-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('super-admin.users.update', $user) }}">
                    @csrf
                    @method('PUT')
                    @include('super-admin.users.partials.form', ['user' => $user, 'roles' => $roles])
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('super-admin.users.index') }}" class="btn btn-outline-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
