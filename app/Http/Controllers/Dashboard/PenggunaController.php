<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Helpers\FileUploadHelper;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Exports\Pengguna\PenggunaExport;
use App\Imports\Pengguna\PenggunaImport;
use App\Helpers\Filters\UserFilterHelper;
use App\Http\Requests\UpdateUserPasswordRequest;
use App\Http\Resources\public\WithoutDataResource;
use App\Http\Requests\Pengguna\StorePenggunaRequest;
use App\Http\Requests\Pengguna\ImportPenggunaRequest;
use App\Http\Requests\Pengguna\UpdatePenggunaRequest;

class PenggunaController extends Controller
{
    public function index(Request $request)
    {
        try {
            if (!Gate::allows('view pengguna')) {
                return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
            }

            $loggedInUser = Auth::user();
            $limit = $request->input('limit', 10);
            if ($loggedInUser->hasAnyRole(['Super Admin', 'Penanggung Jawab'])) {
                $query = User::query()->where('id', '!=', 1)->orderBy('created_at', 'desc');
            } else {
                $query = User::query()->where('status_aktif', 2)->where('id', '!=', 1)->orderBy('created_at', 'desc');
            }

            $filters = $request->all();
            $query = UserFilterHelper::applyFiltersUser($query, $filters);

            if ($limit == 0) {
                $users = $query->get();
                $paginationData = null;
            } else {
                $limit = is_numeric($limit) ? (int)$limit : 10;
                $users = $query->paginate($limit);

                $paginationData = [
                    'links' => [
                        'first' => $users->url(1),
                        'last' => $users->url($users->lastPage()),
                        'prev' => $users->previousPageUrl(),
                        'next' => $users->nextPageUrl(),
                    ],
                    'meta' => [
                        'current_page' => $users->currentPage(),
                        'last_page' => $users->lastPage(),
                        'per_page' => $users->perPage(),
                        'total' => $users->total(),
                    ]
                ];
            }

            if ($users->isEmpty()) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => 'Data pengguna tidak ditemukan.',
                ], Response::HTTP_OK);
            }

            $formattedData = $users->map(function ($user) {
                $role = $user->roles->first();
                return [
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
                    'kelurahan' => $user->kelurahans ? [
                        'id' => $user->kelurahans->id,
                        'nama_kelurahan' => $user->kelurahans->nama_kelurahan,
                        'kode_kelurahan' => $user->kelurahans->kode_kelurahan,
                        'max_rw' => $user->kelurahans->max_rw,
                        'provinsi_id' => $user->kelurahans->provinsis,
                        'kabupaten_id' => $user->kelurahans->kabupaten_kotas,
                        'kecamatan_id' => $user->kelurahans->kecamatans,
                        'created_at' => $user->kelurahans->created_at,
                        'updated_at' => $user->kelurahans->updated_at
                    ] : null,
                    'status_aktif' => $user->status_aktif,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ];
            });

            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => 'Data pengguna berhasil ditampilkan.',
                'data' => $formattedData,
                'pagination' => $paginationData
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('| Pengguna | - Error function index: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(StorePenggunaRequest $request)
    {
        try {
            if (!Gate::allows('create pengguna')) {
                return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
            }

            $data = $request->validated();
            $password = $data['password'] ?? '12345';

            $fotoProfilPath = null;
            if ($request->hasFile('foto_profil')) {
                $fotoProfilPath = FileUploadHelper::storePhoto($request->file('foto_profil'), 'profiles');
            }

            $user = [
                'nama' => $data['nama'],
                'username' => $data['username'],
                'jenis_kelamin' => $data['jenis_kelamin'],
                'nik_ktp' => $data['nik_ktp'],
                'foto_profil' => $fotoProfilPath,
                'no_hp' => $data['no_hp'] ?? null,
                'role_id' => $data['role_id'],
                'kelurahan_id' => $data['kelurahan_id'],
                'password' => Hash::make($password),
            ];
            $createUser = User::create($user);
            $createUser->roles()->attach($data['role_id']);

            return response()->json([
                'status' => Response::HTTP_CREATED,
                'message' => "Pengguna baru '{$createUser->nama}' berhasil ditambahkan.",
                'data' => $createUser
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('| Pengguna | - Error function store: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id)
    {
        try {
            if (!Gate::allows('view pengguna')) {
                return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
            }

            $user = User::where('id', '!=', 1)->find($id);
            if (!$user) {
                return response()->json([
                    'status' => Response::HTTP_OK,
                    'message' => 'Pengguna tidak ditemukan.'
                ], Response::HTTP_NOT_FOUND);
            }

            $role = $user->roles->first();
            $formattedData = [
                'id' => $user->id,
                'nama' => $user->nama,
                'username' => $user->username,
                'jenis_kelamin' => $user->jenis_kelamin,
                'foto_profil' => $user->foto_profil ? env('STORAGE_SERVER_DOMAIN') . $user->foto_profil : null,
                'nik_ktp' => $user->nik_ktp,
                'no_hp' => $user->no_hp ?? null,
                'tgl_diangkat' => $user->tgl_diangkat,
                'status_aktif' => $user->status_aktif,
                'role' => $role ? [
                    'id' => $role->id,
                    'name' => $role->name,
                    'deskripsi' => $role->deskripsi,
                    'created_at' => $role->created_at,
                    'updated_at' => $role->updated_at,
                ] : null,
                'kelurahan' => $user->kelurahans ? [
                    'id' => $user->kelurahans->id,
                    'nama_kelurahan' => $user->kelurahans->nama_kelurahan,
                    'kode_kelurahan' => $user->kelurahans->kode_kelurahan,
                    'max_rw' => $user->kelurahans->max_rw,
                    'provinsi_id' => $user->kelurahans->provinsis,
                    'kabupaten_id' => $user->kelurahans->kabupaten_kotas,
                    'kecamatan_id' => $user->kelurahans->kecamatans,
                    'created_at' => $user->kelurahans->created_at,
                    'updated_at' => $user->kelurahans->updated_at
                ] : null,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];

            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => "Detail pengguna '{$user->nama}' berhasil ditampilkan.",
                'data' => $formattedData
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('| Pengguna | - Error function show: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(UpdatePenggunaRequest $request, $id)
    {
        try {
            if (!Gate::allows('edit pengguna')) {
                return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
            }

            $validatedData = $request->validated();
            $user = User::findOrFail($id);

            if ($request->hasFile('foto_profil')) {
                // Hapus foto lama jika ada
                if ($user->foto_profil && Storage::disk('public')->exists($user->foto_profil)) {
                    FileUploadHelper::deletePhoto($user->foto_profil);
                }

                // Upload foto baru
                $fotoProfilPath = FileUploadHelper::storePhoto($request->file('foto_profil'), 'profiles');
                $user->foto_profil = $fotoProfilPath;
            }

            $user->nama = $validatedData['nama'] ?? $user->nama;
            $user->jenis_kelamin = $validatedData['jenis_kelamin'] ?? $user->jenis_kelamin;
            $user->nik_ktp = $validatedData['nik_ktp'] ?? $user->nik_ktp;
            $user->no_hp = $validatedData['no_hp'] ?? $user->no_hp;
            $user->role_id = $validatedData['role_id'] ?? $user->role_id;

            if (isset($validatedData['role_id'])) {
                $user->roles()->sync([$validatedData['role_id']]);
            }
            $user->save();

            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => "Data pengguna '{$user->nama}' berhasil diperbarui.",
                'data' => $user
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('| Pengguna | - Error function update: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function toggleStatusUser($id)
    {
        try {
            if (!Gate::allows('aktifkan pengguna')) {
                return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
            }

            $user = User::where('id', '!=', 1)->find($id);
            if (!$user) {
                return response()->json(new WithoutDataResource(Response::HTTP_NOT_FOUND, 'Pengguna tidak ditemukan.'), Response::HTTP_NOT_FOUND);
            }

            // Logika toggle status pengguna
            if ($user->status_aktif === 1) {
                $user->status_aktif = 2; // Aktifkan user
                $message = "Pengguna '{$user->nama}' berhasil diaktifkan.";
            } elseif ($user->status_aktif === 2) {
                $user->status_aktif = 3; // Nonaktifkan user
                $message = "Pengguna '{$user->nama}' berhasil dinonaktifkan.";
            } elseif ($user->status_aktif === 3) {
                $user->status_aktif = 2; // Aktifkan kembali
                $message = "Pengguna '{$user->nama}' berhasil diaktifkan kembali.";
            }

            $user->save();
            return response()->json(new WithoutDataResource(Response::HTTP_OK, $message), Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('| Pengguna | - Error function toggleStatusUser: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Reset by admin
    public function resetPasswordPengguna(Request $request, $id)
    {
        try {
            if (!Gate::allows('reset password')) {
                return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
            }

            // 1. Get user_id dari request
            $user = User::find($id);
            if (!$user) {
                return response()->json(new WithoutDataResource(Response::HTTP_NOT_FOUND, 'Pengguna akun tidak ditemukan.'), Response::HTTP_NOT_FOUND);
            }

            // 2. Pengecualian 'Super Admin'
            if ($user->id == 1 || $user->nama === 'Super Admin') {
                return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Tidak diperbolehkan mereset password untuk akun Super Admin.'), Response::HTTP_FORBIDDEN);
            }

            // 3. Reset password
            $newPassword = $request->input('password');
            $hashedPassword = Hash::make($newPassword);
            $user->password = $hashedPassword;
            $user->save();

            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => "Berhasil melakukan reset password untuk pengguna '{$user->nama}'.",
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('| Pengguna | - Error function resetPasswordPengguna: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Reset by user
    public function updatePasswordPengguna(UpdateUserPasswordRequest $request)
    {
        try {
            if (!Gate::allows('update password')) {
                return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
            }

            $user = Auth::user();
            $data = $request->validated();
            if (isset($data['password'])) {
                $currentPassword = $request->input('current_password');
                if (!Hash::check($currentPassword, $user->password)) {
                    return response()->json(new WithoutDataResource(Response::HTTP_BAD_REQUEST, 'Kata sandi yang anda masukkan tidak valid.'), Response::HTTP_BAD_REQUEST);
                }

                $data['password'] = Hash::make($data['password']);
            }
            /** @var \App\Models\User $user **/
            $user->fill($data)->save();
            return response()->json(new WithoutDataResource(Response::HTTP_OK, 'Berhasil memperbarui kata sandi anda.'), Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('| Pengguna | - Error function updatePasswordPengguna: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function importPengguna(ImportPenggunaRequest $request)
    {
        try {
            if (!Gate::allows('import pengguna')) {
                return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
            }

            $file = $request->validated();

            try {
                Excel::import(new PenggunaImport, $file['pengguna_file']);
            } catch (\Exception $e) {
                return response()->json(new WithoutDataResource(Response::HTTP_NOT_ACCEPTABLE, 'Maaf sepertinya terjadi kesalahan.' . $e->getMessage()), Response::HTTP_NOT_ACCEPTABLE);
            }

            return response()->json(new WithoutDataResource(Response::HTTP_OK, 'Data pengguna berhasil di import kedalam database.'), Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('| Pengguna | - Error function importPengguna: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function exportPengguna(Request $request)
    {
        try {
            if (!Gate::allows('export pengguna')) {
                return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
            }

            $data_pengguna = User::all();
            if ($data_pengguna->isEmpty()) {
                return response()->json(new WithoutDataResource(Response::HTTP_NOT_FOUND, 'Tidak ada data pengguna yang tersedia untuk diekspor.'), Response::HTTP_NOT_FOUND);
            }

            try {
                return Excel::download(new PenggunaExport($request->all()), 'data-pengguna.xls');
            } catch (\Throwable $e) {
                return response()->json(new WithoutDataResource(Response::HTTP_INTERNAL_SERVER_ERROR, 'Maaf sepertinya terjadi kesalahan.'), Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return response()->json(new WithoutDataResource(Response::HTTP_OK, 'Data pengguna berhasil di download.'), Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('| Pengguna | - Error function exportPengguna: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
