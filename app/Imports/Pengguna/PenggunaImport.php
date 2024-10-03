<?php

namespace App\Imports\Pengguna;

use App\Models\User;
use App\Helpers\RandomHelper;
use App\Models\Kelurahan;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class PenggunaImport implements ToModel, WithHeadingRow, WithValidation
{
    use Importable;
    private $Role;
    private $Kelurahan;
    public function __construct()
    {
        $this->Role = Role::select('id', 'name')->get();
        $this->Kelurahan = Kelurahan::select('id', 'nama_kelurahan')->get();
    }

    public function rules(): array
    {
        return [
            'nama' => 'required|max:255',
            'role' => 'required',
            'kelurahan' => 'required',
            'jenis_kelamin' => 'required|in:P,L',
            'nik_ktp' => 'required|size:16|unique:users,nik_ktp',
            'no_hp' => 'required|max:50'
        ];
    }

    public function customValidationMessages()
    {
        return [
            'nama.required' => 'Nama tidak boleh kosong.',
            'role.required' => 'Silahkan masukkan nama role pengguna terlebih dahulu.',
            'kelurahan.required' => 'Silahkan masukkan nama kelurahan yang diampu pengguna terlebih dahulu.',
            'jenis_kelamin.required' => 'Jenis kelamin pengguna tidak diperbolehkan kosong.',
            'jenis_kelamin.in' => 'Jenis kelamin pengguna tidak diperbolehkan selain laki-laki atau perempuan.',
            'nik_ktp.required' => 'NIK KTP wajib diisi.',
            'nik_ktp.size' => 'NIK KTP harus 16 karakter.',
            'nik_ktp.unique' => 'NIK KTP sudah terdaftar.',
            'no_hp.required' => 'Nomor HP tidak boleh kosong.'
        ];
    }

    public function model(array $row)
    {
        $username = RandomHelper::generateUsername($row['nama']);
        $existingUser = User::where('username', $username)->first();
        if ($existingUser) {
            $username .= '_' . rand(100, 999);
        }

        $role = $this->Role->where('name', $row['role'])->first();
        if (!$role) {
            throw new \Exception("Role '" . $row['role'] . "' tidak ditemukan.");
        }

        $kelurahan = $this->Kelurahan->where('nama_kelurahan', $row['kelurahan'])->first();
        if (!$kelurahan) {
            throw new \Exception("Nama kelurahan '" . $row['kelurahan'] . "' tidak ditemukan.");
        }

        $jenisKelamin = $row['jenis_kelamin'] === 'P' ? 0 : 1;

        $password = RandomHelper::generatePasswordBasic();
        $userData = [
            'nama' => $row['nama'],
            'username' => $username,
            'jenis_kelamin' => $jenisKelamin,
            'nik_ktp' => $row['nik_ktp'],
            'no_hp' => $row['no_hp'],
            'tgl_diangkat' => $row['tgl_diangkat'],
            'role_id' => $role->id,
            'kelurahan_id' => $kelurahan->id,
            'status_aktif' => true,
            'password' => Hash::make($password),
        ];
        $createUser = User::create($userData);
        $createUser->assignRole($role->name);
    }
}
