<?php

namespace App\Http\Controllers\Dashboard\Counting;

use App\Events\QuickCountUpdated;
use App\Models\QuickCount;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use App\Http\Resources\public\WithoutDataResource;
use App\Http\Requests\Counting\StoreQuickCountRequest;
use App\Http\Requests\Counting\UpdateQuickCountRequest;

class QuickCountController extends Controller
{
    public function index(Request $request)
    {
        if (!Gate::allows('view quickCount')) {
            return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
        }

        $filters = $request->all();
        $quickCountQuery = QuickCount::query()->orderBy('created_at', 'desc');

        if (isset($filters['periode'])) {
            $periode = $filters['periode'];
            $quickCountQuery->where('periode', $periode);
        }

        $qc = $quickCountQuery->get();
        if ($qc->isEmpty()) {
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'message' => "Data perhitungan pasangan calon 'Periode $periode' tidak ditemukan.",
                'data' => []
            ], Response::HTTP_OK);
        }

        $formattedData = $qc->map(function ($qc) {
            return [
                'id' => $qc->id,
                'nama' => $qc->paslon ? $qc->paslon->nama : null,
                'partai' => $qc->paslon && $qc->paslon->partai ? [
                    'id' => $qc->paslon->partai->id,
                    'nama' => $qc->paslon->partai->nama,
                    'color' => $qc->paslon->partai->color,
                    'created_at' => $qc->paslon->partai->created_at,
                    'updated_at' => $qc->paslon->partai->updated_at
                ] : null,
                'periode' => $qc->periode,
                'jumlah_suara' => $qc->jumlah_suara,
                'kategori_suara' => $qc->suara_kategori,
                'created_at' => $qc->created_at,
                'updated_at' => $qc->updated_at
            ];
        });

        return response()->json([
            'status' => Response::HTTP_OK,
            'message' => "Data perhitungan Pasangan Calon 'Periode $periode' berhasil ditampilkan.",
            'data' => $formattedData
        ], Response::HTTP_OK);
    }

    public function store(StoreQuickCountRequest $request)
    {
        if (!Gate::allows('create quickCount')) {
            return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
        }

        $data_QC = $request->validated();

        try {
            $quickCount = QuickCount::create([
                'pasangan_calon_id' => $data_QC['pasangan_calon_id'],
                'periode' => $data_QC['periode'],
                // 'jumlah_suara' => $data_QC['jumlah_suara'],
                'kategori_suara_id' => $data_QC['kategori_suara_id'],
            ]);

            $paslon = $quickCount->paslon;
            $paslonName = $paslon ? $paslon->nama : 'N/A';
            $partaiName = $paslon && $paslon->partai ? $paslon->partai->nama : 'N/A';
            $periode = $quickCount->periode;
            return response()->json([
                'status' => Response::HTTP_CREATED,
                'message' => "Data perhitungan 'Periode $periode' untuk Pasangan Calon '$paslonName' dari Partai '$partaiName' berhasil ditambahkan."
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('| QuickCount | - Error function store: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan saat menambahkan data quick count. Silakan coba lagi nanti.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(UpdateQuickCountRequest $request, $id)
    {
        if (!Gate::allows('edit quickCount')) {
            return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
        }

        try {
            $quickCount = QuickCount::find($id);
            if (!$quickCount) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => 'Data quick count tidak ditemukan.',
                    'data' => []
                ], Response::HTTP_NOT_FOUND);
            }

            $validatedData = $request->validated();
            $quickCount->update([
                'jumlah_suara' => $validatedData['jumlah_suara']
            ]);

            $formattedData = [
                'id' => $quickCount->id,
                'nama' => $quickCount->paslon->nama,
                'partai' => $quickCount->paslon->partai ? [
                    'id' => $quickCount->paslon->partai->id,
                    'nama' => $quickCount->paslon->partai->nama,
                    'color' => $quickCount->paslon->partai->color,
                    'created_at' => $quickCount->paslon->partai->created_at,
                    'updated_at' => $quickCount->paslon->partai->updated_at
                ] : null,
                'periode' => $quickCount->periode,
                'jumlah_suara' => $quickCount->jumlah_suara,
                'kategori_suara' => $quickCount->suara_kategori,
                'created_at' => $quickCount->created_at,
                'updated_at' => $quickCount->updated_at
            ];

            event(new QuickCountUpdated($formattedData));
            // event(new QuickCountUpdated('test'));

            $paslonName = $quickCount->paslon->nama;
            $partaiName = $quickCount->paslon->partai ? $quickCount->paslon->partai->nama : 'N/A';
            $periode = $quickCount->periode;
            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => "Jumlah suara untuk Pasangan Calon '$paslonName' dari Partai '$partaiName' untuk 'Periode $periode' berhasil diperbarui.",
                'data' => $formattedData
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('| QuickCount | - Error function edit: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan saat memperbarui jumlah suara. Silakan coba lagi nanti.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
