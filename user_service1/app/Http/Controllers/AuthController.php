<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // =====================
    // REGISTER
    // =====================
    public function register(Request $request)
    {
        try {
            $data = $request->validate([
                'name'     => 'required|string|max:255',
                'email'    => 'required|email|unique:users,email',
                'password' => 'required|min:8'
            ]);

            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status'  => true,
                'message' => 'Register berhasil',
                'token'   => $token,
                'user'    => $user
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Validasi gagal',
                'errors'  => $e->errors()
            ], 422);
        }
    }

    // =====================
    // LOGIN
    // =====================
    public function login(Request $request)
    {
        try {
            $data = $request->validate([
                'email'    => 'required|email',
                'password' => 'required'
            ]);

            $user = User::where('email', $data['email'])->first();

            if (!$user || !Hash::check($data['password'], $user->password)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Email atau password salah'
                ], 401);
            }

            // hapus token lama
            $user->tokens()->delete();

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status'  => true,
                'message' => 'Login berhasil',
                'token'   => $token,
                'user'    => $user
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Validasi gagal',
                'errors'  => $e->errors()
            ], 422);
        }
    }

    // =====================
    // PROFILE (Protected)
    // =====================
    public function apiProfile(Request $request)
    {
        return response()->json([
            'status' => true,
            'user'   => $request->user()
        ], 200);
    }

    // =====================
    // LOGOUT (Protected)
    // =====================
    public function apiLogout(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $user->currentAccessToken()->delete();

            Log::info('[Auth] User logged out', [
                'user_id' => $user->id
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Logout berhasil'
            ], 200);
        } catch (\Throwable $e) {
            Log::error('[Auth] Logout error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Gagal logout'
            ], 500);
        }
    }
}