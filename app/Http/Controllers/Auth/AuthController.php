<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $loginInput = trim((string) ($request->input('login') ?? $request->input('email') ?? $request->input('phone') ?? ''));
        $request->merge(['login' => $loginInput]);

        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => __('The mobile number or email field is required.'),
        ]);

        $isEmail = (bool) filter_var($loginInput, FILTER_VALIDATE_EMAIL);
        $password = (string) $request->input('password');

        if ($isEmail) {
            $normalizedLogin = Str::lower($loginInput);
            $throttleKey = 'login:' . $normalizedLogin . '|' . $request->ip();
        } else {
            // Clean non-digit characters except leading +
            $cleaned = preg_replace('/[^\d+]/', '', $loginInput);
            $normalized = $cleaned;
            if (str_starts_with($normalized, '+880')) {
                $normalized = substr($normalized, 3);
            } elseif (str_starts_with($normalized, '880')) {
                $normalized = substr($normalized, 2);
            }
            $normalizedLogin = $normalized;
            $throttleKey = 'login:' . $normalizedLogin . '|' . $request->ip();
        }

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $message = __('Too many login attempts. Please try again in :seconds seconds.', ['seconds' => $seconds]);
            throw ValidationException::withMessages([
                'login' => $message,
                'email' => $message,
            ]);
        }

        $remember = $request->boolean('remember');
        $authenticated = false;

        if ($isEmail) {
            $authenticated = Auth::attempt(['email' => $normalizedLogin, 'password' => $password], $remember);
        } else {
            // Try matching normalized phone, cleaned phone, raw phone, or email
            $authenticated = Auth::attempt(['phone' => $normalizedLogin, 'password' => $password], $remember);

            if (! $authenticated && ! empty($cleaned) && $cleaned !== $normalizedLogin) {
                $authenticated = Auth::attempt(['phone' => $cleaned, 'password' => $password], $remember);
            }

            if (! $authenticated && $loginInput !== $normalizedLogin) {
                $authenticated = Auth::attempt(['phone' => $loginInput, 'password' => $password], $remember);
            }

            if (! $authenticated) {
                $authenticated = Auth::attempt(['email' => $loginInput, 'password' => $password], $remember);
            }
        }

        if (! $authenticated) {
            RateLimiter::hit($throttleKey, 60);
            $failedMessage = __('These credentials do not match our records.');
            throw ValidationException::withMessages([
                'login' => $failedMessage,
                'email' => $failedMessage,
            ]);
        }

        RateLimiter::clear($throttleKey);

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            $inactiveMessage = __('Your account is inactive. Contact an administrator.');
            throw ValidationException::withMessages([
                'login' => $inactiveMessage,
                'email' => $inactiveMessage,
            ]);
        }

        $user->update(['last_login_at' => now()]);

        AuditLog::record('login', $user);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}