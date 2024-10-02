<?php

namespace App\Http\Controllers\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Helpers\FileUploadHelper;
use App\Models\AktivitasPelaksana;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use App\Helpers\Filters\AktivitasFilterHelper;
use App\Http\Resources\public\WithoutDataResource;
use App\Http\Requests\Aktivitas\StoreAktivitasPelaksanaRequest;
use App\Http\Requests\Aktivitas\UpdateAktivitasPelaksanaRequest;

class AktivitasController extends Controller
{
    public function index(Request $request)
    {
        try {
            if (!Gate::allows('view aktivitas')) {
                return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
            }

            $limit = $request->input('limit', 10);
            $aktivitas_pelaksana = AktivitasPelaksana::query()->orderBy('created_at', 'desc');

            $filters = $request->all();
            $aktivitas = AktivitasFilterHelper::applyFiltersAktivitas($aktivitas_pelaksana, $filters);

            if ($limit == 0) {
                $data_aktivitas = $aktivitas->get();
                $paginationData = null;
            } else {
                $limit = is_numeric($limit) ? (int)$limit : 10;
                $data_aktivitas = $aktivitas->paginate($limit);

                $paginationData = [
                    'links' => [
                        'first' => $data_aktivitas->url(1),
                        'last' => $data_aktivitas->url($data_aktivitas->lastPage()),
                        'prev' => $data_aktivitas->previousPageUrl(),
                        'next' => $data_aktivitas->nextPageUrl(),
                    ],
                    'meta' => [
                        'current_page' => $data_aktivitas->currentPage(),
                        'last_page' => $data_aktivitas->lastPage(),
                        'per_page' => $data_aktivitas->perPage(),
                        'total' => $data_aktivitas->total(),
                    ]
                ];
            }

            if ($data_aktivitas->isEmpty()) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => 'Data aktivitas tidak ditemukan.',
                ], Response::HTTP_OK);
            }

            $formattedData = $data_aktivitas->map(function ($aktivitas) {
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
                    'nama_aktivitas' => $aktivitas->nama_aktivitas,
                    'status_aktivitas' => $aktivitas->status_aktivitas,
                    'deskripsi' => $aktivitas->deskripsi,
                    'tgl_mulai' => $aktivitas->tgl_mulai,
                    'tgl_selesai' => $aktivitas->tgl_selesai,
                    'tempat_aktivitas' => $aktivitas->tempat_aktivitas,
                    'foto_aktivitas' => $aktivitas->foto_aktivitas,
                    'kelurahan' => $aktivitas->kelurahans,
                    'created_at' => $aktivitas->created_at,
                    'updated_at' => $aktivitas->updated_at,
                ];
            });

            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => 'Data aktivitas berhasil ditampilkan.',
                'data' => $formattedData,
                'pagination' => $paginationData
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('| Aktivitas | - Error function index: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(StoreAktivitasPelaksanaRequest $request)
    {
        try {
            if (!Gate::allows('create aktivitas')) {
                return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
            }

            $validatedData = $request->validated();

            // Handle file upload untuk foto aktivitas
            $fotoAktivitasPath = null;
            if ($request->hasFile('foto_aktivitas')) {
                $fotoAktivitasPath = FileUploadHelper::storePhoto($request->file('foto_aktivitas'), 'aktivitas');
            }

            // Simpan data aktivitas
            $aktivitas = AktivitasPelaksana::create([
                'pelaksana' => $validatedData['pelaksana_id'],
                'nama_aktivitas' => $validatedData['nama_aktivitas'],
                'status_aktivitas' => 1,
                'deskripsi' => $validatedData['deskripsi'] ?? null,
                'tgl_mulai' => $validatedData['tgl_mulai'],
                'tgl_selesai' => $validatedData['tgl_selesai'],
                'tempat_aktivitas' => $validatedData['tempat_aktivitas'],
                'foto_aktivitas' => $fotoAktivitasPath,
                'rw' => $validatedData['rw'],
                'kelurahan' => $validatedData['kelurahan_id'],
            ]);

            return response()->json([
                'status' => Response::HTTP_CREATED,
                'message' => "Aktivitas '{$aktivitas->nama_aktivitas}' berhasil dibuat dan dilaksanakan pada '{$aktivitas->tgl_mulai}'.",
                'data' => $aktivitas
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('| Aktivitas | - Error function store: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id)
    {
        try {
            // Cek hak akses
            if (!Gate::allows('view aktivitas')) {
                return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
            }

            // Temukan aktivitas berdasarkan ID
            $aktivitas = AktivitasPelaksana::with(['pelaksana_users', 'status_aktivitas', 'kelurahans'])->find($id);

            if (!$aktivitas) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => 'Aktivitas tidak ditemukan.',
                ], Response::HTTP_NOT_FOUND);
            }

            // Format data aktivitas
            $formattedData = [
                'id' => $aktivitas->id,
                'nama_aktivitas' => $aktivitas->nama_aktivitas,
                'deskripsi' => $aktivitas->deskripsi,
                'tgl_mulai' => $aktivitas->tgl_mulai,
                'tgl_selesai' => $aktivitas->tgl_selesai,
                'tempat_aktivitas' => $aktivitas->tempat_aktivitas,
                'foto_aktivitas' => $aktivitas->foto_aktivitas ? env('STORAGE_SERVER_DOMAIN') . $aktivitas->foto_aktivitas : null,
                'pelaksana' => $aktivitas->pelaksana_users ? [
                    'id' => $aktivitas->pelaksana_users->id,
                    'nama' => $aktivitas->pelaksana_users->nama,
                    'username' => $aktivitas->pelaksana_users->username,
                    'nik_ktp' => $aktivitas->pelaksana_users->nik_ktp,
                    'foto_profil' =>  $aktivitas->pelaksana_users->foto_profil ? env('STORAGE_SERVER_DOMAIN') . $aktivitas->pelaksana_users->foto_profil : null,
                    'tgl_diangkat' => $aktivitas->pelaksana_users->tgl_diangkat,
                    'jenis_kelamin' => $aktivitas->pelaksana_users->jenis_kelamin,
                    'role' => $aktivitas->pelaksana_users->roles->first() ? $aktivitas->pelaksana_users->roles->first()->name : null,
                    'status_aktif' => $aktivitas->pelaksana_users->status_aktif,
                    'created_at' => $aktivitas->pelaksana_users->created_at,
                    'updated_at' => $aktivitas->pelaksana_users->updated_at
                ] : null,
                'status_aktivitas' => $aktivitas->status_aktivitas ? [
                    'id' => $aktivitas->status_aktivitas->id,
                    'label' => $aktivitas->status_aktivitas->label,
                    'created_at' => $aktivitas->status_aktivitas->created_at,
                    'updated_at' => $aktivitas->status_aktivitas->updated_at
                ] : null,
                'rw' => $aktivitas->rw,
                'kelurahan' => $aktivitas->kelurahans ? [
                    'id' => $aktivitas->kelurahans->id,
                    'nama_kelurahan' => $aktivitas->kelurahans->nama_kelurahan,
                    'kode_kelurahan' => $aktivitas->kelurahans->kode_kelurahan,
                    'max_rw' => $aktivitas->kelurahans->max_rw,
                    'provinsi_id' => $aktivitas->kelurahans->provinsis,
                    'kabupaten_id' => $aktivitas->kelurahans->kabupatens,
                    'kecamatan_id' => $aktivitas->kelurahans->kecamatans,
                    'created_at' => $aktivitas->kelurahans->created_at,
                    'updated_at' => $aktivitas->kelurahans->updated_at
                ] : null,
                'created_at' => $aktivitas->created_at,
                'updated_at' => $aktivitas->updated_at,
            ];

            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => "Detail aktivitas '{$aktivitas->nama_aktivitas}' berhasil ditampilkan.",
                'data' => $formattedData,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('| Aktivitas | - Error function show: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(UpdateAktivitasPelaksanaRequest $request, $id)
    {
        try {
            // Cek hak akses
            if (!Gate::allows('edit aktivitas')) {
                return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
            }

            $aktivitas = AktivitasPelaksana::find($id);
            if (!$aktivitas) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => 'Aktivitas tidak ditemukan.',
                ], Response::HTTP_NOT_FOUND);
            }

            // Validasi data yang diterima
            $validatedData = $request->validated();

            // Update data aktivitas
            $aktivitas->deskripsi = $validatedData['deskripsi'] ?? $aktivitas->deskripsi;
            $aktivitas->tgl_mulai = $validatedData['tgl_mulai'] ?? $aktivitas->tgl_mulai;
            $aktivitas->tgl_selesai = $validatedData['tgl_selesai'] ?? $aktivitas->tgl_selesai;
            $aktivitas->tempat_aktivitas = $validatedData['tempat_aktivitas'] ?? $aktivitas->tempat_aktivitas;

            // Jika ada file foto aktivitas baru, simpan dan hapus yang lama
            if ($request->hasFile('foto_aktivitas')) {
                // Hapus foto lama jika ada
                if ($aktivitas->foto_aktivitas) {
                    FileUploadHelper::deletePhoto($aktivitas->foto_aktivitas);
                }
                $aktivitas->foto_aktivitas = FileUploadHelper::storePhoto($request->file('foto_aktivitas'), 'aktivitas');
            }

            $aktivitas->save();

            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => "Aktivitas '{$aktivitas->nama_aktivitas}' berhasil diperbarui.",
                'data' => $aktivitas
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('| Aktivitas | - Error function update: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            if (!Gate::allows('delete aktivitas')) {
                return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
            }

            $aktivitas = AktivitasPelaksana::find($id);
            if (!$aktivitas) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => 'Aktivitas tidak ditemukan.',
                ], Response::HTTP_NOT_FOUND);
            }

            $aktivitas->delete();

            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => "Aktivitas '{$aktivitas->nama_aktivitas}' berhasil dihapus.",
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('| Aktivitas | - Error function destroy: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
