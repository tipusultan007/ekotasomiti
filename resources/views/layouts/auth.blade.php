<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('Login')) - {{ \App\Models\Setting::get('org_name', config('app.name')) }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    <style>
        .auth-body {
            min-height: 100vh;
            background: linear-gradient(140deg, #0f172a 0%, #1e3a8a 55%, #2563eb 100%);
            position: relative;
            overflow: hidden;
        }
        .auth-body::before, .auth-body::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,.05);
        }
        .auth-body::before { width: 420px; height: 420px; top: -120px; left: -120px; }
        .auth-body::after { width: 320px; height: 320px; bottom: -100px; right: -80px; }
        .auth-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 30px 80px rgba(2,6,23,.45);
            position: relative;
            z-index: 1;
        }
        .auth-brand-icon {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border-radius: 18px;
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem;
            box-shadow: 0 8px 20px rgba(37,99,235,.4);
        }
    </style>
</head>
<body>
    <div class="auth-body">
        <div class="position-absolute top-0 end-0 p-3" style="z-index:2;">
            <div class="dropdown">
                <button class="btn btn-light btn-sm rounded-pill px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-translate me-1"></i>{{ app()->getLocale() === 'bn' ? 'বাংলা' : 'English' }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('language.switch', 'en') }}">{{ app()->getLocale() === 'en' ? '✓ ' : '' }}English</a></li>
                    <li><a class="dropdown-item" href="{{ route('language.switch', 'bn') }}">{{ app()->getLocale() === 'bn' ? '✓ ' : '' }}বাংলা</a></li>
                </ul>
            </div>
        </div>
        @yield('content')
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>