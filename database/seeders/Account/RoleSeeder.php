<?php

namespace Database\Seeders\Account;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $created_at = Carbon::now('Asia/Jakarta')->subDays(rand(0, 365));
        $updated_at = Carbon::now('Asia/Jakarta');

        $roleSuperAdmin = Role::create([
            'name' => 'Super Admin',
            'deskripsi' => 'Ini adalah role paling GG',
            'created_at' => $created_at,
            'updated_at' => $updated_at,
        ]);

        Role::create([
            'name' => 'Penanggung Jawab',
            'deskripsi' => 'Ini adalah role Admin yang menjadi penganggung jawab',
            'created_at' => $created_at,
            'updated_at' => $updated_at,
        ]);

        Role::create([
            'name' => 'Pelaksana',
            'deskripsi' => 'Ini adalah role User yang menjadi pelaksanan tiap kecamatan kota semarang',
            'created_at' => $created_at,
            'updated_at' => $updated_at,
        ]);

        $roleSuperAdmin->givePermissionTo([
            'create role',
            'edit role',
            'view role',

            'edit permission',
            'delete permission',
            'view permission'
        ]);
    }
}
