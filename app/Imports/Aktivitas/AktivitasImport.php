<?php

namespace App\Imports\Aktivitas;

use App\Models\User;
use App\Models\Kelurahan;
use App\Helpers\RandomHelper;
use App\Models\StatusAktivitas;
use App\Models\StatusAktivitasRw;
use App\Models\AktivitasPelaksana;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
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
        $this->Kelurahan = Kelurahan::select('id', 'nama_kelurahan', 'kode_kelurahan')->get();
        $this->StatusAktivitas = StatusAktivitas::select('id', 'label')->get();
    }

    public function rules(): array
    {
        return [
            'deskripsi' => 'required',
            'kelurahan' => 'required',
            'rw' => 'required|integer',
            'pelaksana_nik' => 'required',
            'nama' => 'required',
            'tanggal_mulai' => 'required',
            'tanggal_selesai' => 'required',
            'potensi_suara' => 'required|integer',
            'status_aktivitas' => 'required',
            'tempat_aktivitas' => 'required',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'deskripsi.required' => 'Deskripsi aktivitas tidak boleh kosong.',
            'nama.required' => 'Nama Penanggung Jawab baru aktivitas tidak boleh kosong.',
            'kelurahan.required' => 'Silahkan masukkan nama kelurahan aktivitas terlebih dahulu.',
            'rw.required' => 'Lokasi RW di kelurahan aktivitas tidak boleh kosong.',
            'rw.integer' => 'Lokasi RW di kelurahan aktivitas tidak diperbolehkan selain angka.',
            'pelaksana_nik.required' => 'Silahkan masukkan NIK KTP pelaksana aktivitas terlebih dahulu.',
            'tanggal_mulai.required' => 'Silahkan masukkan tanggal mulai aktivitas terlebih dahulu.',
            'tanggal_selesai.required' => 'Silahkan masukkan tanggal selesai aktivitas terlebih dahulu.',
            'potensi_suara.required' => 'Potensi suara yang ada di kelurahan aktivitas tidak boleh kosong.',
            'potensi_suara.integer' => 'Potensi suara yang dimasukkan tidak diperbolehkan selain angka.',
            'status_aktivitas.required' => 'Silahkan masukkan status aktivitas terlebih dahulu.',
            'tempat_aktivitas.required' => 'Silahkan masukkan tempat aktivitas terlebih dahulu.',
        ];
    }

    public function model(array $row)
    {
        $pelaksana = User::where('nik_ktp', $row['pelaksana_nik'])->first();
        $defaultHP = '0812345678';
        $kelurahanIds = Kelurahan::pluck('id')->toArray();
        if (!$pelaksana) {
            $username = RandomHelper::generateUsername($row['nama']);
            $password = RandomHelper::generatePasswordBasic();

            $pelaksana = User::create([
                'nama' => $row['nama'],
                'username' => $username,
                'nik_ktp' => $row['pelaksana_nik'],
                'no_hp' => $row['no_hp'] ?? $defaultHP,
                'jenis_kelamin' => $row['jenis_kelamin'],
                'password' => Hash::make($password),
                'kelurahan_id' => $kelurahanIds,
                // 'rw_pelaksana' => [$row['rw']],
                'role_id' => 2,
            ]);

            $role = Role::find(2);
            if ($role) {
                $pelaksana->syncRoles([$role->name]);
            }
        }

        // $kelurahan = $this->Kelurahan->where('nama_kelurahan', $row['kelurahan'])->first();
        $kelurahan = $this->Kelurahan->where('kode_kelurahan', $row['kelurahan'])->first();
        if (!$kelurahan) {
            throw new \Exception("Kelurahan '" . $row['kelurahan'] . "' tidak ditemukan.");
        }

        $status_aktivitas = $this->StatusAktivitas->where('label', $row['status_aktivitas'])->first();
        if (!$status_aktivitas) {
            throw new \Exception("Aktivitas '" . $row['status_aktivitas'] . "' tidak valid.");
        }

        $aktivitas = AktivitasPelaksana::create([
            'deskripsi' => $row['deskripsi'],
            'kelurahan' => $kelurahan->id,
            'rw' => $row['rw'],
            'pelaksana' => $pelaksana->id,
            'nama' => $row['nama'],
            'tgl_mulai' => $row['tanggal_mulai'],
            'tgl_selesai' => $row['tanggal_selesai'],
            'potensi_suara' => $row['potensi_suara'],
            'status_aktivitas' => $status_aktivitas->id,
            'tempat_aktivitas' => $row['tempat_aktivitas']
        ]);

        // Periksa atau buat entri StatusAktivitasRw
        $statusAktivitasRw = StatusAktivitasRw::where('kelurahan_id', $kelurahan->id)
            ->where('rw', $row['rw'])
            ->first();

        if ($statusAktivitasRw) {
            // Update jika sudah ada
            $statusAktivitasRw->update(['status_aktivitas' => $status_aktivitas->id]);
        } else {
            // Buat baru jika belum ada
            $statusAktivitasRw = StatusAktivitasRw::create([
                'kelurahan_id' => $kelurahan->id,
                'rw' => $row['rw'],
                'status_aktivitas' => $status_aktivitas->id
            ]);
        }

        // Update AktivitasPelaksana dengan ID StatusAktivitasRw
        $aktivitas->update(['status_aktivitas_rw' => $statusAktivitasRw->id]);

        return $aktivitas;
    }
}
