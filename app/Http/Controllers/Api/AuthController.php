<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (! $user || ! Auth::attempt(['email' => $user->email, 'password' => $request->password])) {
            throw ValidationException::withMessages([
                'phone' => ['The provided credentials do not match our records.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Your account has been deactivated.'], 403);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        $user->update(['last_login_at' => now()]);

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first(),
                'areas' => $user->areas()->select('areas.id', 'areas.code', 'areas.name')->get(),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'role' => $user->getRoleNames()->first(),
            'areas' => $user->areas()->select('areas.id', 'areas.code', 'areas.name')->get(),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => ['required', 'string', 'max:20', \Illuminate\Validation\Rule::unique('users', 'phone')->ignore($user->id)],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => __('Profile updated successfully.'),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first(),
                'areas' => $user->areas()->select('areas.id', 'areas.code', 'areas.name')->get(),
            ],
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if (! \Illuminate\Support\Facades\Hash::check($request->input('current_password'), $user->password)) {
            return response()->json([
                'message' => __('The provided current password does not match our records.'),
                'errors' => ['current_password' => [__('The provided current password does not match our records.')]],
            ], 422);
        }

        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->input('new_password')),
        ]);

        return response()->json([
            'message' => __('Password updated successfully.'),
        ]);
    }
}
