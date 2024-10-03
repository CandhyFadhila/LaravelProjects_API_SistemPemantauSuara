<?php

namespace App\Imports\Aktivitas;

use App\Models\User;
use App\Models\Kelurahan;
use App\Models\StatusAktivitas;
use App\Models\AktivitasPelaksana;
use Spatie\Permission\Models\Role;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class AktivitasImport implements ToModel, WithHeadingRow, WithValidation
{
    use Importable;

    private $User;
    private $Kelurahan;
    private $StatusAktivitas;
    public function __construct()
    {
        $this->User = User::select('id', 'nama')->get();
        $this->Kelurahan = Kelurahan::select('id', 'nama_kelurahan')->get();
        $this->StatusAktivitas = StatusAktivitas::select('id', 'label')->get();
    }

    public function rules(): array
    {
        return [
            'deskripsi' => 'required',
            'kelurahan' => 'required|exists:kelurahans,nama_kelurahan',
            'rw' => 'required|integer',
            'pelaksana' => 'required|exists:users,nama',
            'tanggal_mulai' => 'required',
            'tanggal_selesai' => 'required',
            'status_aktivitas' => 'required|exists:status,label',
            'tempat_aktivitas' => 'required',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'deskripsi.required' => 'Deskripsi aktivitas tidak boleh kosong.',
            'kelurahan.required' => 'Silahkan masukkan nama kelurahan aktivitas terlebih dahulu.',
            'kelurahan.exists' => 'Kelurahan yang dimasukkan tidak ada dalam database.',
            'rw.required' => 'Lokasi RW di kelurahan aktivitas tidak boleh kosong.',
            'rw.integer' => 'Kelurahan yang dimasukkan tidak ada dalam database.',
            'pelaksana.required' => 'Silahkan masukkan nama pelaksana aktivitas terlebih dahulu.',
            'pelaksana.exists' => 'Pelaksana aktivitas yang dimasukkan tidak ada dalam database.',
            'tanggal_mulai.required' => 'Silahkan masukkan tanggal mulai aktivitas terlebih dahulu.',
            'tanggal_selesai.required' => 'Silahkan masukkan tanggal selesai aktivitas terlebih dahulu.',
            'status_aktivitas.required' => 'Silahkan masukkan status aktivitas terlebih dahulu.',
            'status_aktivitas.exists' => 'Status aktivitas yang dimasukkan tidak ada dalam database.',
            'tempat_aktivitas.required' => 'Silahkan masukkan tempat aktivitas terlebih dahulu.',
        ];
    }

    public function model(array $row)
    {
        $pelaksana = $this->User->where('nama', $row['pelaksana'])->first();
        if (!$pelaksana) {
            throw new \Exception("Pelaksana '" . $row['pelaksana'] . "' tidak ditemukan.");
        }

        $kelurahan = $this->Kelurahan->where('nama_kelurahan', $row['kelurahan'])->first();
        if (!$kelurahan) {
            throw new \Exception("Kelurahan '" . $row['kelurahan'] . "' tidak ditemukan.");
        }

        $status_aktivitas = $this->StatusAktivitas->where('label', $row['status_aktivitas'])->first();
        if (!$status_aktivitas) {
            throw new \Exception("Aktivitas '" . $row['status_aktivitas'] . "' tidak valid.");
        }

        return new AktivitasPelaksana([
            'deskripsi' => $row['deskripsi'],
            'kelurahan' => $kelurahan->id,
            'rw' => $row['rw'],
            'pelaksana' => $pelaksana->id,
            'tgl_mulai' => $row['tanggal_mulai'],
            'tgl_selesai' => $row['tanggal_selesai'],
            'status_aktivitas' => $status_aktivitas->id,
            'tempat_aktivitas' => $row['tempat_aktivitas']
        ]);
    }
}
