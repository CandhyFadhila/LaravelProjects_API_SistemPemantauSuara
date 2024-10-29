<?php

namespace App\Http\Controllers\Dashboard;

use Illuminate\Http\Request;
use App\Models\PasanganCalon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\Paslon\StorePaslonRequest;
use App\Http\Requests\Paslon\UpdatePaslonRequest;
use Illuminate\Support\Facades\Gate;
use App\Http\Resources\public\WithoutDataResource;

class PasanganCalonController extends Controller
{
    public function index(Request $request)
    {
        if (!Gate::allows('view paslon')) {
            return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
        }

        $filters = $request->all();
        $paslonQuery = PasanganCalon::query()->orderBy('created_at', 'desc');

        if (isset($filters['partai_id'])) {
            $partaiId = $filters['partai_id'];
            $paslonQuery->where('partai_id', $partaiId);
        }

        $paslon = $paslonQuery->get();
        if ($paslon->isEmpty()) {
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'message' => 'Data Pasangan Calon tidak ditemukan.',
                'data' => []
            ], Response::HTTP_OK);
        }

        $formattedData = $paslon->map(function ($paslon) {
            return [
                'id' => $paslon->id,
                'nama' => $paslon->nama,
                'partai' => $paslon->partai ? [
                    'id' => $paslon->partai->id,
                    'nama' => $paslon->partai->nama,
                    'color' => $paslon->partai->color ?? null,
                    'created_at' => $paslon->partai->created_at,
                    'updated_at' => $paslon->partai->updated_at
                ] : null,
                'created_at' => $paslon->created_at,
                'updated_at' => $paslon->updated_at
            ];
        });

        return response()->json([
            'status' => Response::HTTP_OK,
            'message' => "Data Pasangan Calon berhasil ditampilkan.",
            'data' => $formattedData
        ], Response::HTTP_OK);
    }

    public function store(StorePaslonRequest $request)
    {
        if (!Gate::allows('create paslon')) {
            return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
        }

        $data = $request->validated();

        $existingPaslon = PasanganCalon::where('partai_id', $data['partai_id'])->first();
        if ($existingPaslon) {
            $partaiName = $existingPaslon->partai ? $existingPaslon->partai->nama : 'Partai yang dipilih';
            return response()->json([
                'status' => Response::HTTP_CONFLICT,
                'message' => "Tidak dapat melanjutkan proses, Karena partai '{$partaiName}' sudah memiliki pasangan calon."
            ], Response::HTTP_OK);
        }

        try {
            $paslon = PasanganCalon::create([
                'nama' => $data['nama'],
                'partai_id' => $data['partai_id'],
            ]);

            $partaiName = $paslon->partai ? $paslon->partai->nama : 'N/A';
            $paslonName = $paslon->nama;
            return response()->json([
                'status' => Response::HTTP_CREATED,
                'message' => "Data Pasangan Calon '{$paslonName}' dari Partai '{$partaiName}' berhasil ditambahkan."
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('| PasanganCalon | - Error function store: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan saat menambahkan Data Pasangan Calon. Silakan coba lagi nanti.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id)
    {
        if (!Gate::allows('view paslon')) {
            return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
        }

        try {
            $paslon = PasanganCalon::find($id);
            if (!$paslon) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => "Data Pasangan Calon tidak ditemukan.",
                    'data' => null
                ], Response::HTTP_NOT_FOUND);
            }

            $formattedData = [
                'id' => $paslon->id,
                'nama' => $paslon->nama,
                'partai' => $paslon->partai ? [
                    'id' => $paslon->partai->id,
                    'nama' => $paslon->partai->nama,
                    'color' => $paslon->partai->color ?? null,
                    'created_at' => $paslon->partai->created_at,
                    'updated_at' => $paslon->partai->updated_at
                ] : null,
                'created_at' => $paslon->created_at,
                'updated_at' => $paslon->updated_at
            ];

            $partaiName = $paslon->partai ? $paslon->partai->nama : 'N/A';
            $paslonName = $paslon->nama;
            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => "Data Pasangan Calon '{$paslonName}' dari Partai '{$partaiName}' berhasil ditampilkan.",
                'data' => $formattedData
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('| PasanganCalon | - Error function show: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan saat mengambil Data Pasangan Calon. Silakan coba lagi nanti.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(UpdatePaslonRequest $request, $id)
    {
        if (!Gate::allows('edit paslon')) {
            return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
        }

        $validatedData = $request->validated();

        try {
            $paslon = PasanganCalon::find($id);
            if (!$paslon) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => "Data pasangan calon tidak ditemukan.",
                    'data' => null
                ], Response::HTTP_NOT_FOUND);
            }

            $existingPaslon = PasanganCalon::where('partai_id', $validatedData['partai_id'])
                ->where('id', '!=', $id)
                ->first();
            if ($existingPaslon) {
                $partaiName = $existingPaslon->partai ? $existingPaslon->partai->nama : 'Partai yang dipilih';
                return response()->json([
                    'status' => Response::HTTP_CONFLICT,
                    'message' => "Tidak dapat melanjutkan proses, Karena partai '{$partaiName}' sudah memiliki pasangan calon."
                ], Response::HTTP_OK);
            }

            $paslon->update([
                'nama' => $validatedData['nama'],
                'partai_id' => $validatedData['partai_id']
            ]);

            $paslonName = $paslon->nama;
            $partaiName = $paslon->partai ? $paslon->partai->nama : 'N/A';
            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => "Data Pasangan Calon '$paslonName' berhasil diperbarui.",
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('| PasanganCalon | - Error function update: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan saat memperbarui data pasangan calon. Silakan coba lagi nanti.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
