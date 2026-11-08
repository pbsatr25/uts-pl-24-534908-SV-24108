<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Tukar email dan password dengan token Sanctum.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            // LOG WARNING - login ditolak. Email dan password TIDAK dicatat (data pribadi
            // dan rahasia); cukup id user bila emailnya terdaftar, plus sebab penolakan.
            Log::warning('Login ditolak', [
                'event' => 'auth.login_gagal',
                'user_id' => $user?->id,
                'aksi' => 'login',
                'sebab' => $user ? 'password salah' : 'email tidak terdaftar',
            ]);

            return response()->json(['message' => 'Email atau password salah.'], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        // Token yang baru dibuat tidak pernah dicatat.
        Log::info('Login berhasil', [
            'event' => 'auth.login_berhasil',
            'user_id' => $user->id,
            'aksi' => 'login',
        ]);

        return response()->json([
            'message' => 'Login berhasil.',
            'token_type' => 'Bearer',
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Data user pemilik token.
     */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * Cabut token yang sedang dipakai. Token di perangkat lain tetap berlaku.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        Log::info('Logout, token dicabut', [
            'event' => 'auth.logout',
            'user_id' => $request->user()->id,
            'aksi' => 'logout',
        ]);

        return response()->json(['message' => 'Logout berhasil, token sudah dicabut.']);
    }
}
