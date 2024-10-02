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
            'username' => 'super.admin',
            'tgl_diangkat' => '2024-10-01',
            'jenis_kelamin' => 1,
            'role_id' => 1,
            'password' => Hash::make('SAP_super_admin_password'),
        ]);
        $roleSuperAdmin->assignRole('Super Admin');
    }
}
