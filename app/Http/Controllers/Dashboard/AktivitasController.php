<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\User;
use App\Models\Kelurahan;
use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Helpers\FileUploadHelper;
use App\Models\AktivitasPelaksana;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\Aktivitas\AktivitasExport;
use App\Imports\Aktivitas\AktivitasImport;
use App\Helpers\Filters\AktivitasFilterHelper;
use App\Http\Resources\public\WithoutDataResource;
use App\Http\Requests\Aktivitas\ImportAktivitasRequest;
use App\Http\Requests\Aktivitas\StoreAktivitasPelaksanaRequest;
use App\Http\Requests\Aktivitas\UpdateAktivitasPelaksanaRequest;

class AktivitasController extends Controller
{
    public function index(Request $request)
    {
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
                'data' => []
            ], Response::HTTP_OK);
        }

        $formattedData = $data_aktivitas->map(function ($aktivitas) {
            $role = $aktivitas->pelaksana_users->roles->first();
            $pelaksana = $aktivitas->pelaksana_users;
            $kelurahanIds = $pelaksana ? $pelaksana->kelurahan_id ?? null : null;
            $kelurahanData = null;

            if (!empty($kelurahanIds)) {
                $kelurahanData = Kelurahan::whereIn('id', $kelurahanIds)->get()->map(function ($kelurahan) {
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

            $pjPelaksana = $pelaksana && $pelaksana->pj_pelaksana ? User::find($pelaksana->pj_pelaksana) : null;
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
                'id' => $aktivitas->id,
                'pelaksana' => $aktivitas->pelaksana_users ? [
                    'id' => $aktivitas->pelaksana_users->id,
                    'nama' => $aktivitas->pelaksana_users->nama,
                    'username' => $aktivitas->pelaksana_users->username,
                    'nik_ktp' => $aktivitas->pelaksana_users->nik_ktp,
                    'foto_profil' =>  $aktivitas->pelaksana_users->foto_profil ? env('STORAGE_SERVER_DOMAIN') . $aktivitas->pelaksana_users->foto_profil : null,
                    'tgl_diangkat' => $aktivitas->pelaksana_users->tgl_diangkat,
                    'jenis_kelamin' => $aktivitas->pelaksana_users->jenis_kelamin,
                    'role' => $role ? [
                        'id' => $role->id,
                        'name' => $role->name,
                        'deskripsi' => $role->deskripsi,
                        'created_at' => $role->created_at,
                        'updated_at' => $role->updated_at,
                    ] : null,
                    'status_aktif' => $pelaksana->status_aktif,
                    'kelurahan' => $kelurahanData,
                    'rw_pelaksana' => $pelaksana->rw_pelaksana ?? null,
                    'pj_pelaksana' => $pjPelaksanaData,
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
                'potensi_suara' => $aktivitas->potensi_suara,
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
    }

    public function store(StoreAktivitasPelaksanaRequest $request)
    {
        if (!Gate::allows('create aktivitas')) {
            return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
        }

        $validatedData = $request->validated();

        // Handle file upload untuk foto aktivitas
        $fotoAktivitasPath = null;
        if ($request->hasFile('foto_aktivitas')) {
            $fotoAktivitasPath = FileUploadHelper::storePhoto($request->file('foto_aktivitas'), 'aktivitas');
        }

        $kelurahan = Kelurahan::where('kode_kelurahan', $validatedData['kelurahan_id'])->first();
        if (!$kelurahan) {
            return response()->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => "Kelurahan dengan kode '{$validatedData['kelurahan_id']}' tidak ditemukan."
            ], Response::HTTP_BAD_REQUEST);
        }

        // Simpan data aktivitas
        $aktivitas = AktivitasPelaksana::create([
            'pelaksana' => $validatedData['pelaksana_id'],
            'status_aktivitas' => 1,
            'deskripsi' => $validatedData['deskripsi'] ?? null,
            'tgl_mulai' => $validatedData['tgl_mulai'],
            'tgl_selesai' => $validatedData['tgl_selesai'],
            'tempat_aktivitas' => $validatedData['tempat_aktivitas'],
            'foto_aktivitas' => $fotoAktivitasPath,
            'rw' => $validatedData['rw'],
            'potensi_suara' => $validatedData['potensi_suara'],
            'kelurahan' => $kelurahan->id,
        ]);

        $tanggal_aktivitas = DateHelper::convertToDMY($aktivitas->tgl_mulai);
        return response()->json([
            'status' => Response::HTTP_CREATED,
            'message' => "Aktivitas pada RW {$aktivitas->rw} Kelurahan '{$aktivitas->kelurahans->nama_kelurahan}' tanggal {$tanggal_aktivitas} berhasil ditambahkan.",
        ], Response::HTTP_CREATED);
    }

    public function show($id)
    {
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

        $role = $aktivitas->pelaksana_users->roles->first();
        $pelaksana = $aktivitas->pelaksana_users;
        $kelurahanIds = $pelaksana ? $pelaksana->kelurahan_id ?? null : null;
        $kelurahanData = null;

        if (!empty($kelurahanIds)) {
            $kelurahanData = Kelurahan::whereIn('id', $kelurahanIds)->get()->map(function ($kelurahan) {
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

        $pjPelaksana = $pelaksana && $pelaksana->pj_pelaksana ? User::find($pelaksana->pj_pelaksana) : null;
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

        $formattedData = [
            'id' => $aktivitas->id,
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
                'role' => $role ? [
                    'id' => $role->id,
                    'name' => $role->name,
                    'deskripsi' => $role->deskripsi,
                    'created_at' => $role->created_at,
                    'updated_at' => $role->updated_at,
                ] : null,
                'status_aktif' => $pelaksana->status_aktif,
                'kelurahan' => $kelurahanData,
                'rw_pelaksana' => $pelaksana->rw_pelaksana ?? null,
                'pj_pelaksana' => $pjPelaksanaData,
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
            'potensi_suara' => $aktivitas->potensi_suara,
            'created_at' => $aktivitas->created_at,
            'updated_at' => $aktivitas->updated_at,
        ];
        $tanggal_aktivitas = DateHelper::convertToDMY($aktivitas->tgl_mulai);
        return response()->json([
            'status' => Response::HTTP_OK,
            'message' => "Aktivitas pada RW {$aktivitas->rw} Kelurahan '{$aktivitas->kelurahans->nama_kelurahan}' tanggal '{$tanggal_aktivitas}' berhasil ditampilkan.",
            'data' => $formattedData,
        ], Response::HTTP_OK);
    }

    public function update(UpdateAktivitasPelaksanaRequest $request, $id)
    {
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
        $aktivitas->potensi_suara = $validatedData['potensi_suara'] ?? $aktivitas->potensi_suara;

        $currentDate = now('Asia/Jakarta');
        if ($currentDate->between($aktivitas->tgl_mulai, $aktivitas->tgl_selesai)) {
            $aktivitas->status_aktivitas = 2;
        } elseif ($currentDate->lt($aktivitas->tgl_mulai)) {
            $aktivitas->status_aktivitas = 1;
        } elseif ($currentDate->gt($aktivitas->tgl_selesai)) {
            $aktivitas->status_aktivitas = 3;
        }

        // Jika ada file foto aktivitas baru, simpan dan hapus yang lama
        if ($request->hasFile('foto_aktivitas')) {
            if ($aktivitas->foto_aktivitas) {
                FileUploadHelper::deletePhoto($aktivitas->foto_aktivitas);
            }
            $aktivitas->foto_aktivitas = FileUploadHelper::storePhoto($request->file('foto_aktivitas'), 'aktivitas');
        } else if (is_string($request->input('foto_aktivitas'))) {
            unset($data['foto_aktivitas']);
        }

        $aktivitas->save();
        $tanggal_aktivitas = DateHelper::convertToDMY($aktivitas->tgl_mulai);
        return response()->json([
            'status' => Response::HTTP_OK,
            'message' => "Aktivitas pada RW {$aktivitas->rw} Kelurahan '{$aktivitas->kelurahans->nama_kelurahan}' tanggal {$tanggal_aktivitas} berhasil diperbarui."
        ], Response::HTTP_OK);
    }

    public function destroy($id)
    {
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
            'message' => "Aktivitas pada RW {$aktivitas->rw} Kelurahan '{$aktivitas->kelurahans->nama_kelurahan}' tanggal '{$tanggal_aktivitas}' berhasil dihapus.",
        ], Response::HTTP_OK);
    }

    public function exportAktivitas(Request $request)
    {
        if (!Gate::allows('export aktivitas')) {
            return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
        }

        $data_aktivitas = AktivitasPelaksana::all();
        if ($data_aktivitas->isEmpty()) {
            return response()->json(new WithoutDataResource(Response::HTTP_NOT_FOUND, 'Tidak ada data aktivitas yang tersedia untuk diekspor.'), Response::HTTP_NOT_FOUND);
        }

        try {
            return Excel::download(new AktivitasExport($request->all()), 'data-aktivitas.xls');
        } catch (\Throwable $e) {
            return response()->json(new WithoutDataResource(Response::HTTP_INTERNAL_SERVER_ERROR, 'Maaf sepertinya terjadi kesalahan.'), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json(new WithoutDataResource(Response::HTTP_OK, 'Data pengguna berhasil di download.'), Response::HTTP_OK);
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
