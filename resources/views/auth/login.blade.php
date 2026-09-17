@extends('layouts.auth')

@section('title', __('Login'))

@section('content')
<div class="d-flex align-items-center justify-content-center min-vh-100 px-3 py-5">
    <div class="auth-card" style="max-width: 420px; width: 100%;">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="auth-brand-icon mx-auto mb-3">
                    <i class="bi bi-bank2"></i>
                </div>
                <h4 class="mb-1 fw-bold">{{ \App\Models\Setting::get('org_name', config('app.name')) }}</h4>
                <p class="text-muted mb-0 small">{{ __('Co-operative Somiti Management System') }}</p>
            </div>

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label for="login" class="form-label">{{ __('Mobile Number or Email') }}</label>
                    <input type="text" id="login" name="login" class="form-control @error('login') is-invalid @elseif($errors->has('email')) is-invalid @enderror"
                           value="{{ old('login', old('email')) }}" required autofocus autocomplete="username"
                           placeholder="{{ __('017XXXXXXXX / admin@ekota.com') }}">
                    @error('login')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @else
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">{{ __('Password') }}</label>
                    <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror"
                           required autocomplete="current-password" placeholder="••••••••">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label" for="remember">{{ __('Remember me') }}</label>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">{{ __('Sign In') }}</button>
            </form>

            <p class="text-center text-muted small mt-4 mb-0"><i class="bi bi-info-circle me-1"></i>{{ __('Default admin: 01700000000 / admin@ekota.com (password: password)') }}</p>
        </div>
    </div>
</div>
@endsection