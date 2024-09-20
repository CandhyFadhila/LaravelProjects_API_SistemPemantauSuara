<?php

namespace Database\Seeders\Account;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roleSuperAdmin = User::create([
            'nama' => 'Super Admin',
            'email' => 'super.admin@gmail.com',
            'role_id' => 1,
            'password' => Hash::make('SAP_super_admin_password'),
        ]);
        $roleSuperAdmin->assignRole('Super Admin');

        $roleAdmin = User::create([
            'nama' => 'Penanggung Jawab',
            'email' => 'tanggung.jawab.pelaksana@gmail.com',
            'role_id' => 2,
            'password' => Hash::make('SAP_super_admin_password'),
        ]);
        $roleAdmin->assignRole('Penanggung Jawab');

        $rolePelaksana = User::create([
            'nama' => 'Pelaksana',
            'email' => 'pelaksana@gmail.com',
            'role_id' => 3,
            'password' => Hash::make('SAP_super_admin_password'),
        ]);
        $rolePelaksana->assignRole('Pelaksana');
    }
}
