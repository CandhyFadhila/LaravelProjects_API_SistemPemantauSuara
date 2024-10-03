<?php

namespace App\Imports\KPU;

use App\Models\Partai;
use App\Models\SuaraKPU;
use App\Models\Kelurahan;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class SuaraKPUImport implements ToModel, WithHeadingRow, WithValidation
{
    use Importable;

    private $Partai;
    private $Kelurahan;

    public function __construct()
    {
        $this->Partai = Partai::select('id', 'nama')->get();
        $this->Kelurahan = Kelurahan::select('id', 'nama_kelurahan')->get();
    }

    public function rules(): array
    {
        return [
            'partai' => 'required',
            'kelurahan' => 'required',
            'tahun' => 'required|integer',
            'tps' => 'required|integer',
            'alamat' => 'required',
            'jumlah_suara' => 'required|integer',
            'jumlah_dpt' => 'required|integer',
            'suara_caleg' => 'nullable|integer',
            'suara_partai' => 'nullable|integer',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'partai.required' => 'Nama partai tidak boleh kosong.',
            'kelurahan.required' => 'Nama kelurahan tidak boleh kosong.',
            'tahun.required' => 'Tahun tidak boleh kosong.',
            'tahun.integer' => 'Tahun harus berupa angka.',
            'tps.required' => 'TPS tidak boleh kosong.',
            'tps.integer' => 'TPS harus berupa angka.',
            'alamat.required' => 'Alamat tidak boleh kosong.',
            'jumlah_suara.required' => 'Jumlah suara tidak boleh kosong.',
            'jumlah_suara.integer' => 'Jumlah suara harus berupa angka.',
            'jumlah_dpt.required' => 'Jumlah DPT tidak boleh kosong.',
            'jumlah_dpt.integer' => 'Jumlah DPT harus berupa angka.',
            'suara_caleg.integer' => 'Suara caleg harus berupa angka.',
            'suara_partai.integer' => 'Suara partai harus berupa angka.',
        ];
    }

    public function model(array $row)
    {
        $partai = $this->Partai->where('nama', $row['partai'])->first();
        if (!$partai) {
            throw new \Exception("Partai '" . $row['partai'] . "' tidak ditemukan.");
        }

        $kelurahan = $this->Kelurahan->where('nama_kelurahan', $row['kelurahan'])->first();
        if (!$kelurahan) {
            throw new \Exception("Kelurahan '" . $row['kelurahan'] . "' tidak ditemukan.");
        }

        return new SuaraKPU([
            'partai_id' => $partai->id,
            'kelurahan_id' => $kelurahan->id,
            'tahun' => $row['tahun'],
            'tps' => $row['tps'],
            'alamat' => $row['alamat'],
            'jumlah_suara' => $row['jumlah_suara'],
            'jumlah_dpt' => $row['jumlah_dpt'],
            'suara_caleg' => $row['suara_caleg'] ?? null,
            'suara_partai' => $row['suara_partai'] ?? null,
        ]);
    }
}
