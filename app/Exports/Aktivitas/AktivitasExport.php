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

    public function collection()
    {
        return AktivitasPelaksana::with(['pelaksana_users', 'status', 'kelurahans'])->get();
    }

    public function headings(): array
    {
        return [
            'no',
            'nama_aktivitas',
            'deskripsi',
            'tanggal_mulai',
            'tanggal_selesai',
            'tempat_aktivitas',
            'rw',
            'kelurahan',
            'kecamatan',
            'pelaksana',
            'status_aktivitas',
            'terakhir_dibuat',
            'terakhir_diperbarui'
        ];
    }

    public function map($aktivitas): array
    {
        static $no = 1;

        return [
            $no++,
            $aktivitas->nama_aktivitas,
            $aktivitas->deskripsi ?? 'N/A',
            $aktivitas->tgl_mulai,
            $aktivitas->tgl_selesai,
            $aktivitas->tempat_aktivitas,
            $aktivitas->rw,
            $aktivitas->kelurahans ? $aktivitas->kelurahans->nama_kelurahan : 'N/A',
            $aktivitas->kelurahans->kecamatans ? $aktivitas->kelurahans->kecamatans->nama_kecamatan : 'N/A',
            $aktivitas->pelaksana_users ? $aktivitas->pelaksana_users->nama : 'N/A',
            $aktivitas->status ? $aktivitas->status->label : 'N/A',
            $aktivitas->created_at,
            $aktivitas->updated_at
        ];
    }
}
