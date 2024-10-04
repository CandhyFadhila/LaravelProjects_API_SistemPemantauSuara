<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\SuaraKPU;
use App\Models\Kelurahan;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\AktivitasPelaksana;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use App\Http\Resources\public\WithoutDataResource;

class DetailMapController extends Controller
{
    public function indexPotensiSuara(Request $request)
    {
        try {
            if (!Gate::allows('view suaraKPU')) {
                return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
            }

            $kode_kelurahan = $request->input('kode_kelurahan', []);
            $tahun = $request->input('tahun', []);

            // Cari kelurahan berdasarkan array kode_kelurahan
            $kelurahanIds = Kelurahan::whereIn('kode_kelurahan', $kode_kelurahan)->pluck('id');
            if ($kelurahanIds->isEmpty()) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => 'Data kelurahan tidak ditemukan.',
                ], Response::HTTP_OK);
            }

            // Cari aktivitas berdasarkan id kelurahan dan filter tahun (tgl_mulai)
            $aktivitas = AktivitasPelaksana::whereIn('kelurahan', $kelurahanIds)
                ->where(function ($query) use ($tahun) {
                    foreach ($tahun as $year) {
                        $query->orWhereYear('tgl_mulai', $year);
                    }
                })
                ->get();
            if ($aktivitas->isEmpty()) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => 'Data aktivitas tidak ditemukan untuk kelurahan ini di tahun yang dipilih.',
                ], Response::HTTP_OK);
            }

            $format_aktivitas = $aktivitas->map(function ($item) {
                return [
                    'id' => $item->id,
                    'pelaksana' => $item->pelaksana_users ? [
                        'id' => $item->pelaksana_users->id,
                        'nama' => $item->pelaksana_users->nama,
                        'username' => $item->pelaksana_users->username,
                        'nik_ktp' => $item->pelaksana_users->nik_ktp,
                        'foto_profil' =>  $item->pelaksana_users->foto_profil ? env('STORAGE_SERVER_DOMAIN') . $item->pelaksana_users->foto_profil : null,
                        'tgl_diangkat' => $item->pelaksana_users->tgl_diangkat,
                        'jenis_kelamin' => $item->pelaksana_users->jenis_kelamin,
                        'role_id' => $item->pelaksana_users->role_id,
                        'status_aktif' => $item->pelaksana_users->status_aktif,
                        'created_at' => $item->pelaksana_users->created_at,
                        'updated_at' => $item->pelaksana_users->updated_at
                    ] : null,
                    'status_aktivitas' => $item->status_aktivitas,
                    'deskripsi' => $item->deskripsi,
                    'tgl_mulai' => $item->tgl_mulai,
                    'tgl_selesai' => $item->tgl_selesai,
                    'tempat_aktivitas' => $item->tempat_aktivitas,
                    'foto_aktivitas' => $item->foto_aktivitas ? env('STORAGE_SERVER_DOMAIN') . $item->foto_aktivitas : null,
                    'rw' => $item->rw,
                    'potensi_suara' => $item->potensi_suara,
                    'kelurahan' => $item->kelurahans ? [
                        'id' => $item->kelurahans->id,
                        'nama_kelurahan' => $item->kelurahans->nama_kelurahan,
                        'kode_kelurahan' => $item->kelurahans->kode_kelurahan,
                        'max_rw' => $item->kelurahans->max_rw,
                        'kecamatan' => $item->kelurahans->kecamatans,
                        'kabupaten' => $item->kelurahans->kabupaten_kotas,
                        'provinsi' => $item->kelurahans->provinsis,
                        'created_at' => $item->kelurahans->created_at,
                        'updated_at' => $item->kelurahans->updated_at
                    ] : null,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];
            });

            $format_chart = $aktivitas->map(function ($item) {
                return [
                    'rw' => $item->rw,
                    'potensi_suara' => $item->potensi_suara
                ];
            })->values();

            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => 'Data aktivitas dan chart berhasil ditampilkan.',
                'data' => [
                    'chart' => $format_chart,
                    'table' => $format_aktivitas,
                    'tahun' => $tahun
                ],
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('| Detail Map | - Error function indexPotensiSuara: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function indexSuaraKPU(Request $request)
    {
        try {
            if (!Gate::allows('view suaraKPU')) {
                return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
            }

            $kode_kelurahan = $request->input('kode_kelurahan', []);
            $tahun = $request->input('tahun', []);

            if (empty($kode_kelurahan) || empty($tahun)) {
                return response()->json([
                    'status' => Response::HTTP_BAD_REQUEST,
                    'message' => 'Kode kelurahan dan tahun diperlukan.',
                ], Response::HTTP_BAD_REQUEST);
            }

            // Step 1: Cari kelurahan berdasarkan array kode_kelurahan
            $kelurahanIds = Kelurahan::whereIn('kode_kelurahan', $kode_kelurahan)->pluck('id');
            if ($kelurahanIds->isEmpty()) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => 'Data kelurahan tidak ditemukan.',
                ], Response::HTTP_OK);
            }

            // Step 2: Cari data suara KPU berdasarkan id kelurahan dan filter tahun
            $suaraKPU = SuaraKPU::whereIn('kelurahan_id', $kelurahanIds)
                ->where(function ($query) use ($tahun) {
                    foreach ($tahun as $year) {
                        $query->orWhere('tahun', $year);
                    }
                })
                ->get();
            if ($suaraKPU->isEmpty()) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => 'Data suara KPU tidak ditemukan untuk kelurahan ini di tahun yang dipilih.',
                ], Response::HTTP_OK);
            }

            // Step 3: Format data suara KPU untuk tabel
            $format_suaraKPU = $suaraKPU->map(function ($item) {
                return [
                    'id' => $item->id,
                    'partai' => $item->partais ? [
                        'id' => $item->partais->id,
                        'nama' => $item->partais->nama,
                        'color' => $item->partais->color ?? null
                    ] : null,
                    'kelurahan' => $item->kelurahans ? [
                        'id' => $item->kelurahans->id,
                        'nama_kelurahan' => $item->kelurahans->nama_kelurahan,
                        'kode_kelurahan' => $item->kelurahans->kode_kelurahan,
                        'max_rw' => $item->kelurahans->max_rw,
                        'kecamatan' => $item->kelurahans->kecamatans,
                        'kabupaten' => $item->kelurahans->kabupaten_kotas,
                        'provinsi' => $item->kelurahans->provinsis,
                        'created_at' => $item->kelurahans->created_at,
                        'updated_at' => $item->kelurahans->updated_at
                    ] : null,
                    'tahun' => $item->tahun,
                    'cakupan_wilayah' => $item->cakupan_wilayah,
                    'kategori_suara' => $item->kategori_suaras,
                    'tps' => $item->tps,
                    'jumlah_suara' => $item->jumlah_suara,
                    'dpt_laki' => $item->dpt_laki,
                    'dpt_perempuan' => $item->dpt_perempuan,
                    'jumlah_dpt' => $item->jumlah_dpt,
                    'suara_caleg' => $item->suara_caleg ?? 'N/A',
                    'suara_partai' => $item->suara_partai ?? 'N/A',
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];
            })->values();

            $format_chart = $suaraKPU->map(function ($item) {
                return [
                    'partai' => $item->partais ? [
                        'id' => $item->partais->id,
                        'nama' => $item->partais->nama,
                        'color' => $item->partais->color ?? null
                    ] : null,
                    'jumlah_suara' => $item->jumlah_suara
                ];
            })->values();

            // Kembalikan data dalam bentuk response JSON
            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => 'Data suara KPU berhasil ditampilkan.',
                'data' => [
                    'chart' => $format_chart,
                    'table' => $format_suaraKPU,
                    'total' => $tahun
                ],
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('| Detail Map | - Error function indexSuaraKPU: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
