<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\SuaraKPU;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use App\Http\Resources\public\WithoutDataResource;

class SuaraKPUController extends Controller
{
    public function index(Request $request)
    {
        try {
            if (!Gate::allows('view suaraKPU')) {
                return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
            }

            $limit = $request->input('limit', 10);
            $query = SuaraKPU::with(['partais', 'kelurahans']);

            // tahun, tps, rw, jenis data (suara kpu)
            if ($request->has('tps')) {
                $query->where('tps', $request->input('tps'));
            }

            // Paginasi atau ambil semua data
            if ($limit == 0) {
                $data_kpu = $query->get();
                $paginationData = null;
            } else {
                $data_kpu = $query->paginate($limit);

                $paginationData = [
                    'links' => [
                        'first' => $data_kpu->url(1),
                        'last' => $data_kpu->url($data_kpu->lastPage()),
                        'prev' => $data_kpu->previousPageUrl(),
                        'next' => $data_kpu->nextPageUrl(),
                    ],
                    'meta' => [
                        'current_page' => $data_kpu->currentPage(),
                        'last_page' => $data_kpu->lastPage(),
                        'per_page' => $data_kpu->perPage(),
                        'total' => $data_kpu->total(),
                    ]
                ];
            }
            if ($data_kpu->isEmpty()) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => 'Data suara KPU tidak ditemukan.',
                ], Response::HTTP_OK);
            }

            $formattedData = $data_kpu->map(function ($suara) {
                return [
                    'id' => $suara->id,
                    'partai' => $suara->partais ? $suara->partais->nama : null,
                    'kelurahan' => $suara->partais->kelurahans ? [
                        'id' => $suara->partais->kelurahans->kelurahans->id,
                        'nama_kelurahan' => $suara->partais->kelurahans->kelurahans->nama_kelurahan,
                        'kode_kelurahan' => $suara->partais->kelurahans->kelurahans->kode_kelurahan,
                        'max_rw' => $suara->partais->kelurahans->kelurahans->max_rw,
                        'provinsi_id' => $suara->partais->kelurahans->kelurahans->provinsis,
                        'kabupaten_id' => $suara->partais->kelurahans->kelurahans->kabupaten_kotas,
                        'kecamatan_id' => $suara->partais->kelurahans->kelurahans->kecamatans,
                        'created_at' => $suara->partais->kelurahans->kelurahans->created_at,
                        'updated_at' => $suara->partais->kelurahans->kelurahans->updated_at
                    ] : null,
                    'tps' => $suara->tps,
                    'jumlah_suara' => $suara->jumlah_suara,
                    'jumlah_dpt' => $suara->jumlah_dpt,
                    'suara_caleg' => $suara->suara_caleg ?? 'N/A',
                    'suara_partai' => $suara->suara_partai ?? 'N/A',
                    'created_at' => $suara->created_at,
                    'updated_at' => $suara->updated_at,
                ];
            });

            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => 'Data suara KPU berhasil ditampilkan.',
                'data' => $formattedData,
                'pagination' => $paginationData
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('| KPU | - Error function index: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function importKPU(Request $request)
    {
        
    }
}
