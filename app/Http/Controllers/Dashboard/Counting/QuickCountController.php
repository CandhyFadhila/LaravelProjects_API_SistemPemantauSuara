<?php

namespace App\Http\Controllers\Dashboard\Counting;

use App\Models\Kelurahan;
use App\Models\QuickCount;
use App\Models\SaveWinner;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Events\QuickCountUpdated;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use App\Http\Resources\public\WithoutDataResource;
use App\Http\Requests\Counting\StoreQuickCountRequest;
use App\Http\Requests\Counting\UpdateQuickCountRequest;

class QuickCountController extends Controller
{
    // public function index(Request $request)
    // {
    //     if (!Gate::allows('view quickCount')) {
    //         return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
    //     }

    //     $filters = $request->all();
    //     $quickCountQuery = QuickCount::query()->orderBy('created_at', 'desc');

    //     if (isset($filters['periode'])) {
    //         $periode = $filters['periode'];
    //         $quickCountQuery->where('periode', $periode);
    //     }

    //     $qc = $quickCountQuery->get();
    //     if ($qc->isEmpty()) {
    //         return response()->json([
    //             'status' => Response::HTTP_NOT_FOUND,
    //             'message' => "Data perhitungan pasangan calon 'Periode $periode' tidak ditemukan.",
    //             'data' => []
    //         ], Response::HTTP_OK);
    //     }

    //     $formattedData = $qc->map(function ($qc) {
    //         return [
    //             'id' => $qc->id,
    //             'nama' => $qc->paslon ? $qc->paslon->nama : null,
    //             'partai' => $qc->paslon && $qc->paslon->partai ? [
    //                 'id' => $qc->paslon->partai->id,
    //                 'nama' => $qc->paslon->partai->nama,
    //                 'color' => $qc->paslon->partai->color,
    //                 'created_at' => $qc->paslon->partai->created_at,
    //                 'updated_at' => $qc->paslon->partai->updated_at
    //             ] : null,
    //             'periode' => $qc->periode,
    //             'jumlah_suara' => $qc->jumlah_suara,
    //             'kelurahan' => $qc->kelurahans ? [
    //                 'id' => $qc->kelurahans->id,
    //                 'nama_kelurahan' => $qc->kelurahans->nama_kelurahan,
    //                 'kode_kelurahan' => $qc->kelurahans->kode_kelurahan,
    //                 'max_rw' => $qc->kelurahans->max_rw,
    //                 'provinsi' => $qc->kelurahans->provinsis,
    //                 'kabupaten' => $qc->kelurahans->kabupaten_kotas,
    //                 'kecamatan' => $qc->kelurahans->kecamatans,
    //                 'created_at' => $qc->kelurahans->created_at,
    //                 'updated_at' => $qc->kelurahans->updated_at
    //             ] : null,
    //             'tps' => $qc->tps,
    //             'kategori_suara' => $qc->suara_kategori,
    //             'created_at' => $qc->created_at,
    //             'updated_at' => $qc->updated_at
    //         ];
    //     });

    //     return response()->json([
    //         'status' => Response::HTTP_OK,
    //         'message' => "Data perhitungan Pasangan Calon 'Periode $periode' berhasil ditampilkan.",
    //         'data' => $formattedData
    //     ], Response::HTTP_OK);
    // }

    public function index(Request $request)
    {
        if (!Gate::allows('view quickCount')) {
            return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
        }

        // Filter periode jika ada
        $filters = $request->all();
        $quickCountQuery = QuickCount::query()->with(['paslon', 'suara_kategori', 'save_winners.kelurahans'])->orderBy('created_at', 'desc');

        if (isset($filters['periode'])) {
            $quickCountQuery->where('periode', $filters['periode']);
        }

        // Ambil semua data quick_counts dan save_winners terkait
        $quickCounts = $quickCountQuery->get();
        $formattedData = $quickCounts->map(function ($quickCount) {
            return [
                'id' => $quickCount->id,
                'nama' => $quickCount->paslon->nama ?? null,
                'partai' => $quickCount->paslon->partai ? [
                    'id' => $quickCount->paslon->partai->id,
                    'nama' => $quickCount->paslon->partai->nama,
                    'color' => $quickCount->paslon->partai->color,
                    'created_at' => $quickCount->paslon->partai->created_at,
                    'updated_at' => $quickCount->paslon->partai->updated_at
                ] : null,
                'periode' => $quickCount->periode,
                'kategori_suara' => $quickCount->suara_kategori ? [
                    'id' => $quickCount->suara_kategori->id,
                    'label' => $quickCount->suara_kategori->label,
                    'created_at' => $quickCount->suara_kategori->created_at,
                    'updated_at' => $quickCount->suara_kategori->updated_at
                ] : null,
                'save_counters' => $quickCount->save_winners->map(function ($saveCounters) {
                    return [
                        'id' => $saveCounters->id,
                        'kelurahan' => $saveCounters->kelurahans ? [
                            'id' => $saveCounters->kelurahans->id,
                            'nama_kelurahan' => $saveCounters->kelurahans->nama_kelurahan,
                            'kode_kelurahan' => $saveCounters->kelurahans->kode_kelurahan,
                            'max_rw' => $saveCounters->kelurahans->max_rw,
                            'provinsi' => $saveCounters->kelurahans->provinsis,
                            'kabupaten' => $saveCounters->kelurahans->kabupaten_kotas,
                            'kecamatan' => $saveCounters->kelurahans->kecamatans,
                            'created_at' => $saveCounters->kelurahans->created_at,
                            'updated_at' => $saveCounters->kelurahans->updated_at
                        ] : null,
                        'tps' => $saveCounters->tps,
                        'jumlah_suara' => $saveCounters->jumlah_suara,
                        'created_at' => $saveCounters->created_at,
                        'updated_at' => $saveCounters->updated_at
                    ];
                }),
                'created_at' => $quickCount->created_at,
                'updated_at' => $quickCount->updated_at
            ];
        });

        return response()->json([
            'status' => Response::HTTP_OK,
            'message' => 'Data Quick Count berhasil ditampilkan',
            'data' => $formattedData
        ], Response::HTTP_OK);
    }

    // public function store(StoreQuickCountRequest $request)
    // {
    //     if (!Gate::allows('create quickCount')) {
    //         return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
    //     }

    //     $data_QC = $request->validated();

    //     try {
    //         $quickCount = QuickCount::create([
    //             'pasangan_calon_id' => $data_QC['pasangan_calon_id'],
    //             'periode' => $data_QC['periode'],
    //             'kelurahan_id' => $data_QC['kelurahan_id'],
    //             'tps' => $data_QC['tps'],
    //             // 'jumlah_suara' => $data_QC['jumlah_suara'],
    //             'kategori_suara_id' => $data_QC['kategori_suara_id'],
    //         ]);

    //         $paslon = $quickCount->paslon;
    //         $paslonName = $paslon ? $paslon->nama : 'N/A';
    //         $partaiName = $paslon && $paslon->partai ? $paslon->partai->nama : 'N/A';
    //         $periode = $quickCount->periode;
    //         return response()->json([
    //             'status' => Response::HTTP_CREATED,
    //             'message' => "Data perhitungan 'Periode $periode' untuk Pasangan Calon '$paslonName' dari Partai '$partaiName' berhasil ditambahkan."
    //         ], Response::HTTP_CREATED);
    //     } catch (\Exception $e) {
    //         Log::error('| QuickCount | - Error function store: ' . $e->getMessage());
    //         return response()->json([
    //             'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
    //             'message' => 'Terjadi kesalahan saat menambahkan data quick count. Silakan coba lagi nanti.',
    //             'error' => $e->getMessage()
    //         ], Response::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }

    public function store(StoreQuickCountRequest $request)
    {
        if (!Gate::allows('create quickCount')) {
            return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
        }

        $data = $request->validated();

        try {
            // Langkah 1: Temukan entri QuickCount berdasarkan pasangan_calon_id, periode, dan kategori_suara_id
            $quickCount = QuickCount::where([
                'pasangan_calon_id' => $request->input('pasangan_calon_id'),
                'periode' => $request->input('periode'),
                'kategori_suara_id' => $request->input('kategori_suara_id')
            ])->first();
            if (!$quickCount) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => "Data pasangan calon pada periode {$data['periode']} dan kategori suara yang dipilih tidak ditemukan."
                ], Response::HTTP_OK);
            }

            $kelurahan = Kelurahan::find($data['kelurahan_id']);
            if (!$kelurahan) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => "Kelurahan dengan ID {$data['kelurahan_id']} tidak ditemukan."
                ], Response::HTTP_OK);
            }

            // Langkah 2: Cek jika ada entri SaveWinner yang sama
            $existingSaveWinner = SaveWinner::where([
                'quick_count_id' => $quickCount->id,
                'kelurahan_id' => $data['kelurahan_id'],
                'tps' => $data['tps']
            ])->first();
            if ($existingSaveWinner) {
                $existingSaveWinner->update(['jumlah_suara' => $data['jumlah_suara']]);
                $message = "Data jumlah suara untuk TPS {$data['tps']} di Kelurahan '{$kelurahan->nama_kelurahan}' berhasil diperbarui.";
            } else {
                SaveWinner::create([
                    'quick_count_id' => $quickCount->id,
                    'kelurahan_id' => $data['kelurahan_id'],
                    'tps' => $data['tps'],
                    'jumlah_suara' => $data['jumlah_suara']
                ]);
                $message = "Data Save Winner untuk TPS {$data['tps']} di Kelurahan '{$kelurahan->nama_kelurahan}' berhasil ditambahkan.";
            }

            // $allQuickCounts = QuickCount::with(['save_winners', 'paslon', 'suara_kategori'])->get();
            // $formattedData = $allQuickCounts->map(function ($quickCount) {
            //     $saveCountersFormatted = $quickCount->save_winners->map(function ($counter) {
            //         return [
            //             'id' => $counter->id,
            //             'kelurahan' => $counter->kelurahans ? [
            //                 'id' => $counter->kelurahans->id,
            //                 'nama_kelurahan' => $counter->kelurahans->nama_kelurahan,
            //                 'kode_kelurahan' => $counter->kelurahans->kode_kelurahan,
            //                 'max_rw' => $counter->kelurahans->max_rw,
            //                 'provinsi' => $counter->kelurahans->provinsis,
            //                 'kabupaten' => $counter->kelurahans->kabupaten_kotas,
            //                 'kecamatan' => $counter->kelurahans->kecamatans,
            //                 'created_at' => $counter->kelurahans->created_at,
            //                 'updated_at' => $counter->kelurahans->updated_at
            //             ] : null,
            //             'tps' => $counter->tps,
            //             'jumlah_suara' => $counter->jumlah_suara,
            //             'created_at' => $counter->created_at,
            //             'updated_at' => $counter->updated_at
            //         ];
            //     });

            //     return [
            //         'id' => $quickCount->id,
            //         'pasangan_calon' => $quickCount->paslon,
            //         'periode' => $quickCount->periode,
            //         'kategori_suara' => $quickCount->suara_kategori,
            //         'save_counters' => $saveCountersFormatted,
            //         'created_at' => $quickCount->created_at,
            //         'updated_at' => $quickCount->updated_at
            //     ];
            // });

            $saveWinners = SaveWinner::all();
            $totalVotes = $saveWinners->groupBy('quick_count_id')->map(function ($group) {
                return $group->sum('jumlah_suara');
            });
            $sortedQuickCounts = $totalVotes->sortDesc()->keys();
            $totalFormatted = $sortedQuickCounts->map(function ($quickCountId) use ($totalVotes) {
                $quickCount = QuickCount::with(['paslon', 'suara_kategori'])->find($quickCountId);
                return [
                    'id' => $quickCount->id,
                    'pasangan_calon' => $quickCount->paslon ? [
                        'id' => $quickCount->paslon->id,
                        'nama' => $quickCount->paslon->nama,
                        'partai' => $quickCount->paslon->partai ? [
                            'id' => $quickCount->paslon->partai->id,
                            'nama' => $quickCount->paslon->partai->nama,
                            'color' => $quickCount->paslon->partai->color,
                            'created_at' => $quickCount->paslon->partai->created_at,
                            'updated_at' => $quickCount->paslon->partai->updated_at
                        ] : null,
                        'created_at' => $quickCount->paslon->created_at,
                        'updated_at' => $quickCount->paslon->updated_at
                    ] : null,
                    'periode' => $quickCount->periode,
                    'kategori_suara' => $quickCount->suara_kategori,
                    'total_jumlah_suara' => $totalVotes[$quickCountId],
                    'created_at' => $quickCount->created_at,
                    'updated_at' => $quickCount->updated_at
                ];
            });

            $kelurahanResults = Kelurahan::with('kecamatans', 'kabupaten_kotas', 'provinsis')
                ->whereHas('save_winners', function ($query) {
                    $query->whereIn('quick_count_id', [1, 2]);
                })
                ->get()
                ->map(function ($kelurahan) {
                    // Temukan pemenang paslon berdasarkan suara tertinggi
                    $winnersByQuickCount = $kelurahan->save_winners
                        ->groupBy('quick_count_id')
                        ->map(function ($group) {
                            return $group->sum('jumlah_suara');
                        });

                    $winningQuickCountId = $winnersByQuickCount->sortDesc()->keys()->first();
                    $winningQuickCount = QuickCount::with('paslon.partai')->find($winningQuickCountId);

                    return [
                        'kelurahan' => [
                            'id' => $kelurahan->id,
                            'nama_kelurahan' => $kelurahan->nama_kelurahan,
                            'kode_kelurahan' => $kelurahan->kode_kelurahan,
                            'max_rw' => $kelurahan->max_rw,
                            'kecamatan' => $kelurahan->kecamatans,
                            'kabupaten' => $kelurahan->kabupaten_kotas,
                            'provinsi' => $kelurahan->provinsis,
                            'created_at' => $kelurahan->created_at,
                            'updated_at' => $kelurahan->updated_at
                        ],
                        'pasangan_calon' => [
                            'id' => $winningQuickCount->paslon->id,
                            'nama' => $winningQuickCount->paslon->nama,
                            'partai' => $winningQuickCount->paslon->partai ? [
                                'id' => $winningQuickCount->paslon->partai->id,
                                'nama' => $winningQuickCount->paslon->partai->nama,
                                'color' => $winningQuickCount->paslon->partai->color,
                                'created_at' => $winningQuickCount->paslon->partai->created_at,
                                'updated_at' => $winningQuickCount->paslon->partai->updated_at
                            ] : null,
                            'created_at' => $winningQuickCount->paslon->created_at,
                            'updated_at' => $winningQuickCount->paslon->updated_at
                        ]
                    ];
                });

            $formattedData = [
                'total' => $totalFormatted,
                'villages' => $kelurahanResults->values()
            ];
            event(new QuickCountUpdated($formattedData));

            return response()->json([
                'status' => Response::HTTP_CREATED,
                'message' => $message,
                'data' => $formattedData
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('| SaveWinner | - Error function store: ' . $e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Terjadi kesalahan saat menambahkan atau memperbarui data Save Winner. Silakan coba lagi nanti.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // public function update(UpdateQuickCountRequest $request, $id)
    // {
    //     if (!Gate::allows('edit quickCount')) {
    //         return response()->json(new WithoutDataResource(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melakukan proses ini.'), Response::HTTP_FORBIDDEN);
    //     }

    //     // req keluarahan, tps, id quick count

    //     try {
    //         $quickCount = QuickCount::find($id);
    //         if (!$quickCount) {
    //             return response()->json([
    //                 'status' => Response::HTTP_NOT_FOUND,
    //                 'message' => 'Data quick count tidak ditemukan.',
    //                 'data' => []
    //             ], Response::HTTP_NOT_FOUND);
    //         }

    //         $validatedData = $request->validated();
    //         $quickCount->update([
    //             'jumlah_suara' => $validatedData['jumlah_suara']
    //         ]);

    //         $allQuickCounts = QuickCount::all()->map(function ($qc) {
    //             return [
    //                 'id' => $qc->id,
    //                 'nama' => $qc->paslon->nama,
    //                 'partai' => $qc->paslon->partai ? [
    //                     'id' => $qc->paslon->partai->id,
    //                     'nama' => $qc->paslon->partai->nama,
    //                     'color' => $qc->paslon->partai->color,
    //                     'created_at' => $qc->paslon->partai->created_at,
    //                     'updated_at' => $qc->paslon->partai->updated_at
    //                 ] : null,
    //                 'kelurahan' => $qc->kelurahans ? [
    //                     'id' => $qc->kelurahans->id,
    //                     'nama_kelurahan' => $qc->kelurahans->nama_kelurahan,
    //                     'kode_kelurahan' => $qc->kelurahans->kode_kelurahan,
    //                     'max_rw' => $qc->kelurahans->max_rw,
    //                     'provinsi' => $qc->kelurahans->provinsis,
    //                     'kabupaten' => $qc->kelurahans->kabupaten_kotas,
    //                     'kecamatan' => $qc->kelurahans->kecamatans,
    //                     'created_at' => $qc->kelurahans->created_at,
    //                     'updated_at' => $qc->kelurahans->updated_at
    //                 ] : null,
    //                 'tps' => $qc->tps,
    //                 'periode' => $qc->periode,
    //                 'jumlah_suara' => $qc->jumlah_suara,
    //                 'kategori_suara' => $qc->suara_kategori,
    //                 'created_at' => $qc->created_at,
    //                 'updated_at' => $qc->updated_at
    //             ];
    //         });

    //         $formattedData = [
    //             'quick_count' => $allQuickCounts
    //         ];

    //         event(new QuickCountUpdated($formattedData));
    //         // event(new QuickCountUpdated('test'));

    //         $paslonName = $quickCount->paslon->nama;
    //         $partaiName = $quickCount->paslon->partai ? $quickCount->paslon->partai->nama : 'N/A';
    //         $periode = $quickCount->periode;
    //         return response()->json([
    //             'status' => Response::HTTP_OK,
    //             'message' => "Jumlah suara untuk Pasangan Calon '$paslonName' dari Partai '$partaiName' untuk 'Periode $periode' berhasil diperbarui.",
    //             'data' => $formattedData
    //         ], Response::HTTP_OK);
    //     } catch (\Exception $e) {
    //         Log::error('| QuickCount | - Error function edit: ' . $e->getMessage());
    //         return response()->json([
    //             'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
    //             'message' => 'Terjadi kesalahan saat memperbarui jumlah suara. Silakan coba lagi nanti.',
    //             'error' => $e->getMessage()
    //         ], Response::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }

    private function calculatedWinner() {}
}
