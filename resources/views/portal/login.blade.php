@extends('portal.layout')

@section('title', 'Login Portal Pelanggan')

@push('css')
    <style>
        body {
            background: linear-gradient(135deg, #0a3e68 0%, #0f6b95 55%, #0c8a8f 100%);
        }

        .portal-main {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .portal-login-card {
            width: 100%;
            max-width: 400px;
            border-radius: 16px;
            box-shadow: 0 18px 45px rgba(8, 28, 54, .28);
        }
    </style>
@endpush

@section('content')
    <div class="card portal-login-card">
        <div class="card-header text-center bg-white border-0 pt-4">
            <div class="mb-2" style="font-size: 2.5rem; color: #0f6b95;">
                <i class="fas fa-user-shield"></i>
            </div>
            <h1 class="h4 mb-1">{{ \App\Models\SystemSetting::instance()->portalName() }}</h1>
            <p class="text-muted mb-0">{{ \App\Models\SystemSetting::instance()->portalDescription() }}</p>
        </div>
        <div class="card-body px-4 pb-4">
            @if($errors->any())
                <div class="alert alert-danger py-2 small">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('portal.login.post') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="login">ID Pelanggan / Username / Nomor HP</label>
                    <input
                        type="text"
                        id="login"
                        name="login"
                        class="form-control"
                        value="{{ old('login') }}"
                        placeholder="Contoh: 000000000001 atau budi-ppp"
                        required
                        autofocus
                    >
                </div>
                <div class="form-group">
                    <label for="password">Password Portal</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        required
                    >
                </div>
                <button type="submit" class="btn btn-primary btn-block">Masuk</button>
            </form>
        </div>
    </div>
@endsection
