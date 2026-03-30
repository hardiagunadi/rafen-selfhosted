<!DOCTYPE html>
<html lang="id">
<head>
    @php($systemSettings = \App\Models\SystemSetting::instance())
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - {{ $systemSettings->appName('Rafen Self-Hosted') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/svg+xml" href="{{ asset('branding/rafen-favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('branding/favicon-32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('branding/favicon-180.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <style>
        body.login-page {
            background:
                radial-gradient(circle at 12% 12%, rgba(15, 107, 149, 0.16), transparent 30%),
                radial-gradient(circle at 88% 8%, rgba(12, 138, 143, 0.14), transparent 28%),
                linear-gradient(160deg, #f4f7fb 0%, #eef5fb 45%, #f8fbff 100%);
        }

        .auth-wordmark {
            width: min(280px, 88%);
            margin: 0 auto;
            filter: drop-shadow(0 8px 18px rgba(11, 42, 74, 0.2));
        }

        .login-box {
            width: min(420px, calc(100vw - 2rem));
        }

        .login-card-body {
            border: 1px solid #d7e1ee;
            border-radius: 18px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
            background: rgba(255, 255, 255, 0.96);
            padding: 1.6rem;
        }

        .login-box-msg {
            color: #39516c;
        }

        .login-card-body .form-control,
        .login-card-body .input-group-text {
            border-color: #d4deea;
        }

        .login-card-body .form-control:focus {
            border-color: #8fb5df;
            box-shadow: 0 0 0 0.2rem rgba(19, 103, 164, 0.15);
        }

        .login-card-body .btn-primary {
            background: linear-gradient(90deg, #0f6b95, #0c8a8f);
            border: none;
            box-shadow: 0 10px 18px rgba(15, 107, 149, 0.22);
        }
    </style>
</head>
<body class="hold-transition login-page">
    <div class="login-box">
        <div class="login-logo">
            <img src="{{ asset('branding/rafen-wordmark.svg') }}" alt="RAFEN" class="auth-wordmark">
        </div>
        <div class="card">
            <div class="card-body login-card-body">
                <p class="login-box-msg">Masuk ke {{ $systemSettings->appName('Rafen Self-Hosted') }}</p>

                @if ($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                <form action="{{ route('login.attempt') }}" method="POST">
                    @csrf
                    <div class="input-group mb-3">
                        <input type="email" name="email" class="form-control" placeholder="Email" required value="{{ old('email') }}">
                        <div class="input-group-append">
                            <div class="input-group-text"><span class="fas fa-envelope"></span></div>
                        </div>
                    </div>

                    <div class="input-group mb-3">
                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                        <div class="input-group-append">
                            <div class="input-group-text"><span class="fas fa-lock"></span></div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-8">
                            <div class="icheck-primary">
                                <input type="checkbox" id="remember" name="remember" @checked(old('remember'))>
                                <label for="remember">Remember Me</label>
                            </div>
                        </div>
                        <div class="col-4">
                            <button type="submit" class="btn btn-primary btn-block">Masuk</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>
