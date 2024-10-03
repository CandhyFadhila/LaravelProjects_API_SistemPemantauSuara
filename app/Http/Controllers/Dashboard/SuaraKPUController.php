<?php

namespace App\Http\Controllers\Dashboard;

use App\Exports\KPU\SuaraKPUExport;
use App\Models\SuaraKPU;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Imports\KPU\SuaraKPUImport;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\KPU\ImportSuaraKPURequest;
use App\Http\Resources\public\WithoutDataResource;

class SuaraKPUController extends Controller
{
    public function exportKPU(Request $request)
    {
        try {
            if (!Gate::allows('export aktivitas')) {
                return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
            }

            $data_suara_kpu = SuaraKPU::all();
            if ($data_suara_kpu->isEmpty()) {
                return response()->json(new WithoutDataResource(Response::HTTP_NOT_FOUND, 'Tidak ada data suara yang tersedia untuk diekspor.'), Response::HTTP_NOT_FOUND);
            }

            try {
                return Excel::download(new SuaraKPUExport($request->all()), 'data-suaraKPU.xls');
            } catch (\Throwable $e) {
                return response()->json(new WithoutDataResource(Response::HTTP_INTERNAL_SERVER_ERROR, 'Maaf sepertinya terjadi kesalahan.'), Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return response()->json(new WithoutDataResource(Response::HTTP_OK, 'Data suara berhasil di download.'), Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('| Suara KPU | - Error function exportKPU: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function importKPU(ImportSuaraKPURequest $request)
    {
        try {
            if (!Gate::allows('import suaraKPU')) {
                return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
            }

            $file = $request->validated();

            try {
                Excel::import(new SuaraKPUImport, $file['kpu_file']);
            } catch (\Exception $e) {
                return response()->json(new WithoutDataResource(Response::HTTP_NOT_ACCEPTABLE, 'Maaf sepertinya terjadi kesalahan.' . $e->getMessage()), Response::HTTP_NOT_ACCEPTABLE);
            }

            return response()->json(new WithoutDataResource(Response::HTTP_OK, 'Data suara berhasil di import kedalam database.'), Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('| Suara KPU | - Error function importAktivitas: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
