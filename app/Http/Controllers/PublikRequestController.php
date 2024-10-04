<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use Illuminate\Http\Response;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Http\Resources\public\WithoutDataResource;
use App\Models\AktivitasPelaksana;
use App\Models\SuaraKPU;

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

    public function getAllDataAktivitas()
    {
        try {
            if (!Gate::allows('view publikRequest')) {
                return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
            }

            $aktivitas = AktivitasPelaksana::all();
            if ($aktivitas->isEmpty()) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => 'Data aktivitas tidak ditemukan.'
                ], Response::HTTP_NOT_FOUND);
            }

            $formattedData = $aktivitas->map(function ($aktivitas) {
                return [
                    'id' => $aktivitas->id,
                    'pelaksana' => $aktivitas->pelaksana_users ? [
                        'id' => $aktivitas->pelaksana_users->id,
                        'nama' => $aktivitas->pelaksana_users->nama,
                        'username' => $aktivitas->pelaksana_users->username,
                        'nik_ktp' => $aktivitas->pelaksana_users->nik_ktp,
                        'foto_profil' =>  $aktivitas->pelaksana_users->foto_profil ? env('STORAGE_SERVER_DOMAIN') . $aktivitas->pelaksana_users->foto_profil : null,
                        'tgl_diangkat' => $aktivitas->pelaksana_users->tgl_diangkat,
                        'jenis_kelamin' => $aktivitas->pelaksana_users->jenis_kelamin,
                        'role_id' => $aktivitas->pelaksana_users->role_id,
                        'status_aktif' => $aktivitas->pelaksana_users->status_aktif,
                        'created_at' => $aktivitas->pelaksana_users->created_at,
                        'updated_at' => $aktivitas->pelaksana_users->updated_at
                    ] : null,
                    'status_aktivitas' => $aktivitas->status_aktivitas,
                    'deskripsi' => $aktivitas->deskripsi,
                    'tgl_mulai' => $aktivitas->tgl_mulai,
                    'tgl_selesai' => $aktivitas->tgl_selesai,
                    'tempat_aktivitas' => $aktivitas->tempat_aktivitas,
                    'foto_aktivitas' => $aktivitas->foto_aktivitas ? env('STORAGE_SERVER_DOMAIN') . $aktivitas->foto_aktivitas : null,
                    'kelurahan' => $aktivitas->kelurahans ? [
                        'id' => $aktivitas->kelurahans->id,
                        'nama_kelurahan' => $aktivitas->kelurahans->nama_kelurahan,
                        'kode_kelurahan' => $aktivitas->kelurahans->kode_kelurahan,
                        'max_rw' => $aktivitas->kelurahans->max_rw,
                        'kecamatan' => $aktivitas->kelurahans->kecamatans,
                        'kabupaten' => $aktivitas->kelurahans->kabupaten_kotas,
                        'provinsi' => $aktivitas->kelurahans->provinsis,
                        'created_at' => $aktivitas->kelurahans->created_at,
                        'updated_at' => $aktivitas->kelurahans->updated_at
                    ] : null,
                    'created_at' => $aktivitas->created_at,
                    'updated_at' => $aktivitas->updated_at,
                ];
            });

            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => 'Retrieving all aktivitas for dropdown',
                'data' => $formattedData
            ]);
        } catch (\Exception $e) {
            Log::error('| Publik Request | - Error function getAllDataAktivitas: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getAllDataSuaraKPU()
    {
        try {
            if (!Gate::allows('view publikRequest')) {
                return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
            }

            $suara_kpu = SuaraKPU::all();
            if ($suara_kpu->isEmpty()) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => 'Data suara kpu tidak ditemukan.'
                ], Response::HTTP_NOT_FOUND);
            }

            $formattedData = $suara_kpu->map(function ($suara_kpu) {
                return [
                    'id' => $suara_kpu->id,
                    'partai' => $suara_kpu->partais ? [
                        'id' => $suara_kpu->partais->id,
                        'nama' => $suara_kpu->partais->nama,
                        'color' => $suara_kpu->partais->color ?? null
                    ] : null,
                    'kelurahan' => $suara_kpu->kelurahans ? [
                        'id' => $suara_kpu->kelurahans->id,
                        'nama_kelurahan' => $suara_kpu->kelurahans->nama_kelurahan,
                        'kode_kelurahan' => $suara_kpu->kelurahans->kode_kelurahan,
                        'max_rw' => $suara_kpu->kelurahans->max_rw,
                        'kecamatan' => $suara_kpu->kelurahans->kecamatans,
                        'kabupaten' => $suara_kpu->kelurahans->kabupaten_kotas,
                        'provinsi' => $suara_kpu->kelurahans->provinsis,
                        'created_at' => $suara_kpu->kelurahans->created_at,
                        'updated_at' => $suara_kpu->kelurahans->updated_at
                    ] : null,
                    'tahun' => $suara_kpu->tahun,
                    'cakupan_wilayah' => $suara_kpu->cakupan_wilayah,
                    'kategori_suara' => $suara_kpu->kategori_suaras,
                    'tps' => $suara_kpu->tps,
                    'jumlah_suara' => $suara_kpu->jumlah_suara,
                    'dpt_laki' => $suara_kpu->dpt_laki,
                    'dpt_perempuan' => $suara_kpu->dpt_perempuan,
                    'jumlah_dpt' => $suara_kpu->jumlah_dpt,
                    'suara_caleg' => $suara_kpu->suara_caleg ?? 'N/A',
                    'suara_partai' => $suara_kpu->suara_partai ?? 'N/A',
                    'created_at' => $suara_kpu->created_at,
                    'updated_at' => $suara_kpu->updated_at,
                ];
            });

            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => 'Retrieving all suara kpu for dropdown',
                'data' => $formattedData
            ]);
        } catch (\Exception $e) {
            Log::error('| Publik Request | - Error function getAllDataSuaraKPU: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
