<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Aktivitas;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('username', $data['username'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['username' => 'Username atau kata sandi salah.']);
        }
        if (! $user->is_active) {
            throw ValidationException::withMessages(['username' => 'Akun Anda dinonaktifkan. Hubungi administrator.']);
        }

        $user->forceFill(['last_login_at' => now()])->save();
        auth()->setUser($user);
        Aktivitas::catat("{$user->name} masuk ke sistem", 'login');

        return response()->json([
            'token' => $user->createToken('sirta')->plainTextToken,
            'user' => $user,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Berhasil keluar.']);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], ['current_password.current_password' => 'Kata sandi saat ini tidak sesuai.']);

        $request->user()->update(['password' => $data['password']]);

        return response()->json(['message' => 'Kata sandi berhasil diubah.']);
    }
}
