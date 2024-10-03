<?php

namespace App\Http\Controllers\Publik\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;
use App\Http\Requests\Login\LoginRequest;
use App\Http\Resources\public\WithoutDataResource;

class LoginController extends Controller
{
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        $user = User::where('username', $credentials['username'])->first();
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            Log::error("Login failed for username: {$credentials['username']} - Invalid credentials.");
            return response()->json(new WithoutDataResource(Response::HTTP_BAD_REQUEST, 'Username atau password Anda tidak valid, silakan periksa kembali dan lakukan login ulang.'), Response::HTTP_BAD_REQUEST);
        }

        // Cek status_aktif
        if ($user->status_aktif == 0) {
            auth()->logout();
            Log::info("| Auth | - Login failed for user ID: {$user->id} - Account inactive since {$user->updated_at}.");
            return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, "Kami mendeteksi bahwa akun anda tidak aktif sejak {$user->updated_at}."), Response::HTTP_FORBIDDEN);
        }

        Log::info("Login successful for user ID: {$user->id}, Name: {$user->nama}.");

        $isAdmin = $user->hasRole(['Super Admin', 'Penanggung Jawab']) || in_array($user->role_id, [1, 2]);

        // Generate token
        $token = $user->createToken('create_token_' . Str::uuid())->plainTextToken;

        $role = $user->roles->first();
        return response()->json([
            'status' => Response::HTTP_OK,
            'message' => "Login berhasil! Selamat datang '{$user->nama}'.",
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'nama' => $user->nama,
                    'username' => $user->username,
                    'jenis_kelamin' => $user->jenis_kelamin,
                    'foto_profil' => $user->foto_profil ? env('STORAGE_SERVER_DOMAIN') . $user->foto_profil : null,
                    'nik_ktp' => $user->nik_ktp,
                    'no_hp' => $user->no_hp ?? null,
                    'tgl_diangkat' => $user->tgl_diangkat,
                    'role' => $role ? [
                        'id' => $role->id,
                        'name' => $role->name,
                        'deskripsi' => $role->deskripsi,
                        'created_at' => $role->created_at,
                        'updated_at' => $role->updated_at,
                    ] : null,
                    'is_admin' => $isAdmin,
                    'status_aktif' => $user->status_aktif,
                    'permission' => $role ? $role->permissions->pluck('id') : [],
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
                'token' => $token,
            ]
        ], Response::HTTP_OK);
    }

    public function getInfoUserLogin()
    {
        $user = auth()->user();
        $role = $user->roles->first();
        return response()->json([
            'status' => Response::HTTP_OK,
            'message' => "Berhasil menampilkan detail akun '{$user->nama}'.",
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'nama' => $user->nama,
                    'username' => $user->username,
                    'jenis_kelamin' => $user->jenis_kelamin,
                    'foto_profil' => $user->foto_profil ? env('STORAGE_SERVER_DOMAIN') . $user->foto_profil : null,
                    'nik_ktp' => $user->nik_ktp,
                    'no_hp' => $user->no_hp ?? null,
                    'tgl_diangkat' => $user->tgl_diangkat,
                    'role' => $role ? [
                        'id' => $role->id,
                        'name' => $role->name,
                        'deskripsi' => $role->deskripsi,
                        'created_at' => $role->created_at,
                        'updated_at' => $role->updated_at,
                    ] : null,
                    'status_aktif' => $user->status_aktif,
                    'permission' => $role ? $role->permissions->pluck('id') : [],
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
            ]
        ], Response::HTTP_OK);
    }

    public function logout()
    {
        $user = auth()->user();

        if (method_exists($user->currentAccessToken(), 'delete')) {
            $user->currentAccessToken()->delete();
        }

        auth()->guard('web')->logout();
        $deleteCookie = Cookie::forget('authToken');

        $userId = auth()->user();
        Log::info("Logout successful for user ID: {$userId->id}, Name: {$userId->nama}.");

        return response()->json(new WithoutDataResource(Response::HTTP_OK, 'Anda berhasil melakukan logout.'), Response::HTTP_OK)->withCookie($deleteCookie);
    }
}
