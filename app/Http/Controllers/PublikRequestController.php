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
use App\Models\UpcomingTps;

class PublikRequestController extends Controller
{
    public function getAllDataRole()
    {
        if (!Gate::allows('view publikRequest')) {
            return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
        }

        $loggedInUser = Auth::user();
        $roles = Role::orderBy('created_at', 'desc')->get();
        if ($roles->isEmpty()) {
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'message' => 'Data role tidak ditemukan.',
                'data' => []
            ], Response::HTTP_OK);
        }

        if ($loggedInUser->nama !== 'Super Admin') {
            $roles = $roles->filter(function ($role) {
                return $role->name !== 'Super Admin';
            });
        }

        return response()->json([
            'status' => Response::HTTP_OK,
            'message' => 'Retrieving all roles',
            'data' => $roles->values()
        ], Response::HTTP_OK);
    }

    public function getAllDataUser()
    {
        if (!Gate::allows('view publikRequest')) {
            return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
        }

        $loggedInUser = auth()->user();

        if ($loggedInUser->nama !== 'Super Admin') {
            $users = User::orderBy('created_at', 'desc')
                ->where('nama', '!=', 'Super Admin')
                ->where('id', '!=', 1)
                ->where('status_aktif', 2)
                ->get();
        } else {
            $users = User::orderBy('created_at', 'desc')->where('status_aktif', 2)->get();
        }

        if ($users->isEmpty()) {
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'message' => 'Data pengguna tidak ditemukan.',
                'data' => []
            ], Response::HTTP_OK);
        }

        $formattedData = $users->map(function ($user) {
            $role = $user->roles->first();

            $kelurahanIds = $user->kelurahan_id ?? null;
            $kelurahans = null;
            if (!empty($kelurahanIds)) {
                $kelurahans = Kelurahan::whereIn('id', $kelurahanIds)->get()->map(function ($kelurahan) {
                    return [
                        'id' => $kelurahan->id,
                        'nama_kelurahan' => $kelurahan->nama_kelurahan,
                        'kode_kelurahan' => $kelurahan->kode_kelurahan,
                        'max_rw' => $kelurahan->max_rw,
                        'provinsi_id' => $kelurahan->provinsis,
                        'kabupaten_id' => $kelurahan->kabupaten_kotas,
                        'kecamatan_id' => $kelurahan->kecamatans,
                        'created_at' => $kelurahan->created_at,
                        'updated_at' => $kelurahan->updated_at
                    ];
                });
            }

            $pjPelaksana = $user->pj_pelaksana ? User::find($user->pj_pelaksana) : null;
            $pjPelaksanaData = $pjPelaksana ? [
                'id' => $pjPelaksana->id,
                'nama' => $pjPelaksana->nama,
                'username' => $pjPelaksana->username,
                'jenis_kelamin' => $pjPelaksana->jenis_kelamin,
                'foto_profil' => $pjPelaksana->foto_profil ? env('STORAGE_SERVER_DOMAIN') . $pjPelaksana->foto_profil : null,
                'nik_ktp' => $pjPelaksana->nik_ktp,
                'no_hp' => $pjPelaksana->no_hp,
                'tgl_diangkat' => $pjPelaksana->tgl_diangkat,
                'role' => $pjPelaksana->roles->first() ? [
                    'id' => $pjPelaksana->roles->first()->id,
                    'name' => $pjPelaksana->roles->first()->name,
                    'deskripsi' => $pjPelaksana->roles->first()->deskripsi,
                    'created_at' => $pjPelaksana->roles->first()->created_at,
                    'updated_at' => $pjPelaksana->roles->first()->updated_at,
                ] : null,
                'kelurahan' => $pjPelaksana->kelurahan_id ? Kelurahan::whereIn('id', $pjPelaksana->kelurahan_id)->get()->map(function ($kelurahan) {
                    return [
                        'id' => $kelurahan->id,
                        'nama_kelurahan' => $kelurahan->nama_kelurahan,
                        'kode_kelurahan' => $kelurahan->kode_kelurahan,
                        'max_rw' => $kelurahan->max_rw,
                        'provinsi' => $kelurahan->provinsis,
                        'kabupaten' => $kelurahan->kabupaten_kotas,
                        'kecamatan' => $kelurahan->kecamatans,
                        'created_at' => $kelurahan->created_at,
                        'updated_at' => $kelurahan->updated_at
                    ];
                }) : null,
                'rw_pelaksana' => $pjPelaksana->rw_pelaksana ?? null,
                'status_aktif' => $pjPelaksana->status_users ? [
                    'id' => $pjPelaksana->status_users->id,
                    'label' => $pjPelaksana->status_users->label,
                    'created_at' => $pjPelaksana->status_users->created_at,
                    'updated_at' => $pjPelaksana->status_users->updated_at
                ] : null,
                'created_at' => $pjPelaksana->created_at,
                'updated_at' => $pjPelaksana->updated_at
            ] : null;

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
                'kelurahan' => $kelurahans,
                'rw_pelaksana' => $user->rw_pelaksana ?? null,
                'pj_pelaksana' => $pjPelaksanaData,
                'status_aktif' => $user->status_users ? [
                    'id' => $user->status_users->id,
                    'label' => $user->status_users->label,
                    'created_at' => $user->status_users->created_at,
                    'updated_at' => $user->status_users->updated_at
                ] : null,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];
        });

        return response()->json([
            'status' => Response::HTTP_OK,
            'message' => 'Retrieving all users',
            'data' => $formattedData
        ], Response::HTTP_OK);
    }

    public function getAllUserbyPenggerak()
    {
        $loggedInUser = Auth::user();
        if (!in_array($loggedInUser->role_id, [1, 2])) {
            return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
        }

        $users = User::where('role_id', 3)->orderBy('created_at', 'desc')->get();
        if ($users->isEmpty()) {
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'message' => 'Pengguna dengan role Penggerak tidak ditemukan.',
                'data' => []
            ], Response::HTTP_OK);
        }

        if ($users->isEmpty()) {
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'message' => 'Data pengguna tidak ditemukan.',
                'data' => []
            ], Response::HTTP_OK);
        }

        $formattedData = $users->map(function ($user) {
            $role = $user->roles->first();

            $kelurahanIds = $user->kelurahan_id ?? null;
            $kelurahans = null;
            if (!empty($kelurahanIds)) {
                $kelurahans = Kelurahan::whereIn('id', $kelurahanIds)->get()->map(function ($kelurahan) {
                    return [
                        'id' => $kelurahan->id,
                        'nama_kelurahan' => $kelurahan->nama_kelurahan,
                        'kode_kelurahan' => $kelurahan->kode_kelurahan,
                        'max_rw' => $kelurahan->max_rw,
                        'provinsi' => $kelurahan->provinsis,
                        'kabupaten' => $kelurahan->kabupaten_kotas,
                        'kecamatan' => $kelurahan->kecamatans,
                        'created_at' => $kelurahan->created_at,
                        'updated_at' => $kelurahan->updated_at
                    ];
                });
            }

            $pjPelaksana = $user->pj_pelaksana ? User::find($user->pj_pelaksana) : null;
            $pjPelaksanaData = $pjPelaksana ? [
                'id' => $pjPelaksana->id,
                'nama' => $pjPelaksana->nama,
                'username' => $pjPelaksana->username,
                'jenis_kelamin' => $pjPelaksana->jenis_kelamin,
                'foto_profil' => $pjPelaksana->foto_profil ? env('STORAGE_SERVER_DOMAIN') . $pjPelaksana->foto_profil : null,
                'nik_ktp' => $pjPelaksana->nik_ktp,
                'no_hp' => $pjPelaksana->no_hp,
                'tgl_diangkat' => $pjPelaksana->tgl_diangkat,
                'role' => $pjPelaksana->roles->first() ? [
                    'id' => $pjPelaksana->roles->first()->id,
                    'name' => $pjPelaksana->roles->first()->name,
                    'deskripsi' => $pjPelaksana->roles->first()->deskripsi,
                    'created_at' => $pjPelaksana->roles->first()->created_at,
                    'updated_at' => $pjPelaksana->roles->first()->updated_at,
                ] : null,
                'kelurahan' => $pjPelaksana->kelurahan_id ? Kelurahan::whereIn('id', $pjPelaksana->kelurahan_id)->get()->map(function ($kelurahan) {
                    return [
                        'id' => $kelurahan->id,
                        'nama_kelurahan' => $kelurahan->nama_kelurahan,
                        'kode_kelurahan' => $kelurahan->kode_kelurahan,
                        'max_rw' => $kelurahan->max_rw,
                        'provinsi' => $kelurahan->provinsis,
                        'kabupaten' => $kelurahan->kabupaten_kotas,
                        'kecamatan' => $kelurahan->kecamatans,
                        'created_at' => $kelurahan->created_at,
                        'updated_at' => $kelurahan->updated_at
                    ];
                }) : null,
                'rw_pelaksana' => $pjPelaksana->rw_pelaksana ?? null,
                'status_aktif' => $pjPelaksana->status_users ? [
                    'id' => $pjPelaksana->status_users->id,
                    'label' => $pjPelaksana->status_users->label,
                    'created_at' => $pjPelaksana->status_users->created_at,
                    'updated_at' => $pjPelaksana->status_users->updated_at
                ] : null,
                'created_at' => $pjPelaksana->created_at,
                'updated_at' => $pjPelaksana->updated_at
            ] : null;

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
                'kelurahan' => $kelurahans,
                'rw_pelaksana' => $user->rw_pelaksana ?? null,
                'pj_pelaksana' => $pjPelaksanaData,
                'status_aktif' => $user->status_users ? [
                    'id' => $user->status_users->id,
                    'label' => $user->status_users->label,
                    'created_at' => $user->status_users->created_at,
                    'updated_at' => $user->status_users->updated_at
                ] : null,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];
        });

        return response()->json([
            'status' => Response::HTTP_OK,
            'message' => 'Retrieving all users',
            'data' => $formattedData
        ], Response::HTTP_OK);
    }

    public function getAllDataKecamatan()
    {
        if (!Gate::allows('view publikRequest')) {
            return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
        }

        $kecamatan = Kecamatan::all();
        if ($kecamatan->isEmpty()) {
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'message' => 'Data kecamatan tidak ditemukan.',
                'data' => []
            ], Response::HTTP_OK);
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
            'message' => 'Retrieving all kecamatans',
            'data' => $formattedData
        ]);
    }

    public function getAllDataKelurahan()
    {
        if (!Gate::allows('view publikRequest')) {
            return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
        }

        $kelurahan = Kelurahan::all();
        if ($kelurahan->isEmpty()) {
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'message' => 'Data kelurahan tidak ditemukan.',
                'data' => []
            ], Response::HTTP_OK);
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
            'message' => 'Retrieving all kelurahans',
            'data' => $formattedData
        ]);
    }

    public function getKelurahanUserId($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'message' => 'Pengguna tidak ditemukan.',
                'data' => []
            ], Response::HTTP_OK);
        }

        if (empty($user->kelurahan_id)) {
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'message' => 'Tidak ada kelurahan yang terkait dengan pengguna ini.',
                'data' => []
            ], Response::HTTP_OK);
        }

        $kelurahans = Kelurahan::whereIn('id', $user->kelurahan_id)->get();
        if ($kelurahans->isEmpty()) {
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'message' => 'Kelurahan tidak ditemukan.',
                'data' => []
            ], Response::HTTP_OK);
        }

        $formattedKelurahans = $kelurahans->map(function ($kelurahan) {
            return [
                'id' => $kelurahan->id,
                'nama_kelurahan' => $kelurahan->nama_kelurahan,
                'kode_kelurahan' => $kelurahan->kode_kelurahan,
                'max_rw' => $kelurahan->max_rw,
                'provinsi_id' => $kelurahan->provinsis,
                'kabupaten_id' => $kelurahan->kabupaten_kotas,
                'kecamatan_id' => $kelurahan->kecamatans,
                'created_at' => $kelurahan->created_at,
                'updated_at' => $kelurahan->updated_at
            ];
        });

        return response()->json([
            'status' => Response::HTTP_OK,
            'message' => "Data kelurahan dari pengguna '{$user->nama}' berhasil diambil.",
            'data' => $formattedKelurahans
        ], Response::HTTP_OK);
    }

    public function getAllDataAktivitas()
    {
        if (!Gate::allows('view publikRequest')) {
            return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
        }

        $aktivitas = AktivitasPelaksana::orderBy('created_at', 'desc')->get();
        if ($aktivitas->isEmpty()) {
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'message' => 'Data aktivitas tidak ditemukan.',
                'data' => []
            ], Response::HTTP_OK);
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
            'message' => 'Retrieving all aktivitas',
            'data' => $formattedData
        ]);
    }

    public function getAllDataSuaraKPU()
    {
        if (!Gate::allows('view publikRequest')) {
            return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
        }

        $suara_kpu = SuaraKPU::orderBy('created_at', 'desc')->get();
        if ($suara_kpu->isEmpty()) {
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'message' => 'Data suara kpu tidak ditemukan.',
                'data' => []
            ], Response::HTTP_OK);
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
            'message' => 'Retrieving all suara kpu',
            'data' => $formattedData
        ]);
    }

    public function getAllDataUpcomingTPS()
    {
        if (!Gate::allows('view upcomingTPS')) {
            return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
        }

        $prakiraan_tps = UpcomingTps::orderBy('created_at', 'desc')->get();
        if ($prakiraan_tps->isEmpty()) {
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'message' => 'Data prakiraan tps tidak ditemukan.',
                'data' => []
            ], Response::HTTP_OK);
        }

        $formattedData = $prakiraan_tps->map(function ($prakiraan_tps) {
            return [
                'id' => $prakiraan_tps->id,
                'kelurahan' => $prakiraan_tps->kelurahans ? [
                    'id' => $prakiraan_tps->kelurahans->id,
                    'nama_kelurahan' => $prakiraan_tps->kelurahans->nama_kelurahan,
                    'kode_kelurahan' => $prakiraan_tps->kelurahans->kode_kelurahan,
                    'max_rw' => $prakiraan_tps->kelurahans->max_rw,
                    'kecamatan' => $prakiraan_tps->kelurahans->kecamatans,
                    'kabupaten' => $prakiraan_tps->kelurahans->kabupaten_kotas,
                    'provinsi' => $prakiraan_tps->kelurahans->provinsis,
                    'created_at' => $prakiraan_tps->kelurahans->created_at,
                    'updated_at' => $prakiraan_tps->kelurahans->updated_at
                ] : null,
                'tahun' => $prakiraan_tps->tahun,
                'jumlah_tps' => $prakiraan_tps->jumlah_tps,
                'created_at' => $prakiraan_tps->created_at,
                'updated_at' => $prakiraan_tps->updated_at,
            ];
        });

        return response()->json([
            'status' => Response::HTTP_OK,
            'message' => 'Retrieving all data prakiraan tps',
            'data' => $formattedData
        ]);
    }
}
