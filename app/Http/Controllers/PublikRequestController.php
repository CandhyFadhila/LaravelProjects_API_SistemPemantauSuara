<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Http\Resources\public\WithoutDataResource;

class PublikRequestController extends Controller
{
    public function getAllDataRole()
    {
        try {
            if (!Gate::allows('view publikRequest')) {
                return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
            }

            $loggedInUser = Auth::user();
            $roles = Role::all();
            if ($roles->isEmpty()) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => 'Data role tidak ditemukan.'
                ], Response::HTTP_NOT_FOUND);
            }

            if ($loggedInUser->nama !== 'Super Admin') {
                $roles = $roles->filter(function ($role) {
                    return $role->name !== 'Super Admin';
                });
            }

            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => 'Retrieving all roles for dropdown',
                'data' => $roles->values()
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('| Publik Request | - Error function getAllDataRole: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getAllDataUser()
    {
        try {
            if (!Gate::allows('view publikRequest')) {
                return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
            }
    
            $loggedInUser = auth()->user();
    
            if ($loggedInUser->nama !== 'Super Admin') {
                $users = User::where('nama', '!=', 'Super Admin')
                    ->where('id', '!=', 1)
                    ->where('status_aktif', 2)
                    ->get();
            } else {
                $users = User::where('status_aktif', 2)->get();
            }
    
            if ($users->isEmpty()) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => 'Data pengguna tidak ditemukan.',
                ], Response::HTTP_NOT_FOUND);
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
                    'status_aktif' => $user->status_aktif,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ];
            });
    
            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => 'Retrieving all users',
                'data' => $formattedData
            ], Response::HTTP_OK);
    
        } catch (\Exception $e) {
            Log::error('| Publik Request | - Error function getAllDataUser: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }    

    public function getAllDataKecamatan()
    {
        try {
            if (!Gate::allows('view publikRequest')) {
                return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
            }

            $kecamatan = Kecamatan::all();
            if ($kecamatan->isEmpty()) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => 'Data kecamatan tidak ditemukan.'
                ], Response::HTTP_NOT_FOUND);
            }

            $formattedData = $kecamatan->map(function ($kecamatan) {
                return [
                    'id' => $kecamatan->id,
                    'nama_kecamatan' => $kecamatan->nama_kecamatan,
                    'kode_kecamatan' => $kecamatan->kode_kecamatan,
                    'provinsi' => $kecamatan->provinsis,
                    'kabupaten' => $kecamatan->kabupaten_kotas,
                    'created_at' => $kecamatan->created_at,
                    'updated_at' => $kecamatan->updated_at
                ];
            });

            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => 'Retrieving all kecamatans for dropdown',
                'data' => $formattedData
            ]);
        } catch (\Exception $e) {
            Log::error('| Publik Request | - Error function getAllDataKecamatan: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getAllDataKelurahan()
    {
        try {
            if (!Gate::allows('view publikRequest')) {
                return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
            }

            $kelurahan = Kelurahan::all();
            if ($kelurahan->isEmpty()) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => 'Data kelurahan tidak ditemukan.'
                ], Response::HTTP_NOT_FOUND);
            }

            $formattedData = $kelurahan->map(function ($kelurahan) {
                return [
                    'id' => $kelurahan->id,
                    'nama_kelurahan' => $kelurahan->nama_kelurahan,
                    'kode_kelurahan' => $kelurahan->kode_kelurahan,
                    'max_rw' => $kelurahan->max_rw,
                    'kecamatan' => $kelurahan->kecamatans,
                    'kabupaten' => $kelurahan->kabupaten_kotas,
                    'provinsi' => $kelurahan->provinsis,
                    'created_at' => $kelurahan->created_at,
                    'updated_at' => $kelurahan->updated_at
                ];
            });

            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => 'Retrieving all kelurahans for dropdown',
                'data' => $formattedData
            ]);
        } catch (\Exception $e) {
            Log::error('| Publik Request | - Error function getAllDataKelurahan: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
