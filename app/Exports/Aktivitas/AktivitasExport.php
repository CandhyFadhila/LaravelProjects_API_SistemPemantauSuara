<?php

namespace App\Exports\Aktivitas;

use App\Models\AktivitasPelaksana;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class AktivitasExport implements FromCollection, WithHeadings, WithMapping
{
    use Exportable;

    protected $User;

    public function __construct($User)
    {
        $this->User = $User;
    }

    public function collection()
    {
        $query = AktivitasPelaksana::with(['pelaksana_users', 'status', 'kelurahans']);

        if ($this->User->role_id == 1) {
            return $query->get();
        }
        if ($this->User->role_id == 2) {
            return $query->whereHas('pelaksana_users', function ($q) {
                $q->where('role_id', 3)->where('pj_pelaksana', $this->User->id);
            })->get();
        }
        if ($this->User->role_id == 3) {
            return $query->where('pelaksana', $this->User->id)->get();
        }

        return collect();
    }

    public function headings(): array
    {
        return [
            'no',
            'pelaksana',
            'deskripsi',
            'tanggal_mulai',
            'tanggal_selesai',
            'tempat_aktivitas',
            'rw',
            'kelurahan',
            'kecamatan',
            'status_aktivitas',
            'potensi_suara',
            'terakhir_dibuat',
            'terakhir_diperbarui'
        ];
    }

    public function map($aktivitas): array
    {
        static $no = 1;

        return [
            $no++,
            $aktivitas->pelaksana_users ? $aktivitas->pelaksana_users->nama : 'N/A',
            $aktivitas->deskripsi ?? 'N/A',
            $aktivitas->tgl_mulai,
            $aktivitas->tgl_selesai,
            $aktivitas->tempat_aktivitas,
            $aktivitas->rw,
            $aktivitas->kelurahans ? $aktivitas->kelurahans->nama_kelurahan : 'N/A',
            $aktivitas->kelurahans->kecamatans ? $aktivitas->kelurahans->kecamatans->nama_kecamatan : 'N/A',
            $aktivitas->status ? $aktivitas->status->label : 'N/A',
            $aktivitas->potensi_suara ?? 'N/A',
            $aktivitas->created_at,
            $aktivitas->updated_at
        ];
    }
}
