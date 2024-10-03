<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Helpers\FileUploadHelper;
use App\Models\AktivitasPelaksana;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\Aktivitas\AktivitasExport;
use App\Helpers\Filters\AktivitasFilterHelper;
use App\Http\Requests\Aktivitas\ImportAktivitasRequest;
use App\Http\Resources\public\WithoutDataResource;
use App\Http\Requests\Aktivitas\StoreAktivitasPelaksanaRequest;
use App\Http\Requests\Aktivitas\UpdateAktivitasPelaksanaRequest;
use App\Imports\Aktivitas\AktivitasImport;

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

            $tanggal_aktivitas = DateHelper::convertToDMY($aktivitas->tgl_mulai);
            return response()->json([
                'status' => Response::HTTP_CREATED,
                'message' => "Aktivitas '{$aktivitas->nama_aktivitas}' pada RW {$aktivitas->rw} Kelurahan '{$aktivitas->kelurahans->nama_kelurahan}' tanggal {$tanggal_aktivitas} berhasil ditambahkan.",
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
            if (!Gate::allows('view aktivitas')) {
                return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
            }

            $aktivitas = AktivitasPelaksana::find($id);

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
                    'role' => $aktivitas->pelaksana_users->roles->first() ? $aktivitas->pelaksana_users->roles->first() : null,
                    'status_aktif' => $aktivitas->pelaksana_users->status_aktif,
                    'created_at' => $aktivitas->pelaksana_users->created_at,
                    'updated_at' => $aktivitas->pelaksana_users->updated_at
                ] : null,
                'status_aktivitas' => $aktivitas->status,
                'rw' => $aktivitas->rw,
                'kelurahan' => $aktivitas->kelurahans ? [
                    'id' => $aktivitas->kelurahans->id,
                    'nama_kelurahan' => $aktivitas->kelurahans->nama_kelurahan,
                    'kode_kelurahan' => $aktivitas->kelurahans->kode_kelurahan,
                    'max_rw' => $aktivitas->kelurahans->max_rw,
                    'provinsi_id' => $aktivitas->kelurahans->provinsis,
                    'kabupaten_id' => $aktivitas->kelurahans->kabupaten_kotas,
                    'kecamatan_id' => $aktivitas->kelurahans->kecamatans,
                    'created_at' => $aktivitas->kelurahans->created_at,
                    'updated_at' => $aktivitas->kelurahans->updated_at
                ] : null,
                'created_at' => $aktivitas->created_at,
                'updated_at' => $aktivitas->updated_at,
            ];
            $tanggal_aktivitas = DateHelper::convertToDMY($aktivitas->tgl_mulai);
            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => "Aktivitas '{$aktivitas->nama_aktivitas}' pada RW {$aktivitas->rw} Kelurahan '{$aktivitas->kelurahans->nama_kelurahan}' tanggal '{$tanggal_aktivitas}' berhasil ditampilkan.",
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
            $tanggal_aktivitas = DateHelper::convertToDMY($aktivitas->tgl_mulai);
            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => "Aktivitas '{$aktivitas->nama_aktivitas}' pada RW {$aktivitas->rw} Kelurahan '{$aktivitas->kelurahans->nama_kelurahan}' tanggal {$tanggal_aktivitas} berhasil diperbarui.",
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
            $tanggal_aktivitas = DateHelper::convertToDMY($aktivitas->tgl_mulai);
            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => "Aktivitas '{$aktivitas->nama_aktivitas}' pada RW {$aktivitas->rw} Kelurahan '{$aktivitas->kelurahans->nama_kelurahan}' tanggal '{$tanggal_aktivitas}' berhasil dihapus.",
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('| Aktivitas | - Error function destroy: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function exportAktivitas(Request $request)
    {
        try {
            if (!Gate::allows('export aktivitas')) {
                return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
            }

            $data_aktivitas = AktivitasPelaksana::all();
            if ($data_aktivitas->isEmpty()) {
                return response()->json(new WithoutDataResource(Response::HTTP_NOT_FOUND, 'Tidak ada data pengguna yang tersedia untuk diekspor.'), Response::HTTP_NOT_FOUND);
            }

            try {
                return Excel::download(new AktivitasExport($request->all()), 'data-aktivitas.xls');
            } catch (\Throwable $e) {
                return response()->json(new WithoutDataResource(Response::HTTP_INTERNAL_SERVER_ERROR, 'Maaf sepertinya terjadi kesalahan.'), Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return response()->json(new WithoutDataResource(Response::HTTP_OK, 'Data pengguna berhasil di download.'), Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('| Aktivitas | - Error function exportPengguna: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function importAktivitas(ImportAktivitasRequest $request)
    {
        try {
            if (!Gate::allows('import aktivitas')) {
                return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
            }

            $file = $request->validated();

            try {
                Excel::import(new AktivitasImport, $file['aktivitas_file']);
            } catch (\Exception $e) {
                return response()->json(new WithoutDataResource(Response::HTTP_NOT_ACCEPTABLE, 'Maaf sepertinya terjadi kesalahan.' . $e->getMessage()), Response::HTTP_NOT_ACCEPTABLE);
            }

            return response()->json(new WithoutDataResource(Response::HTTP_OK, 'Data aktivitas berhasil di import kedalam database.'), Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('| Aktivitas | - Error function importAktivitas: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
