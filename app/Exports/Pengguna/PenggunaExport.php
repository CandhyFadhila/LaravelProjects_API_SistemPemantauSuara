<?php

namespace App\Exports\Pengguna;

use App\Models\User;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class PenggunaExport implements FromCollection, WithHeadings, WithMapping
{
    use Exportable;

    public function collection()
    {
        return User::with(['roles', 'kelurahans', 'status_users'])
            // ->where('status_aktif', 2)
            ->where('id', '!=', 1)
            ->get();
    }

    public function headings(): array
    {
        return [
            'no',
            'nama',
            'username',
            'jenis_kelamin',
            'nik_ktp',
            'no_hp',
            'tanggal_diangkat',
            'status_aktif',
            'peran',
            'kelurahan',
            'terakhir_dibuat',
            'terakhir_diperbarui'
        ];
    }

    public function map($user): array
    {
        static $no = 1;
        $roles = $user->roles->pluck('name')->toArray();

        return [
            $no++,
            $user->nama,
            $user->username,
            $user->jenis_kelamin ? 'Laki-laki' : 'Perempuan',
            $user->nik_ktp ?? 'N/A',
            $user->no_hp ?? 'N/A',
            $user->tgl_diangkat ?? 'N/A',
            $user->status_users->label,
            implode(', ', $roles),
            $user->kelurahans->nama_kelurahan ?? 'N/A',
            $user->created_at,
            $user->updated_at
        ];
    }
}
