<?php

namespace Database\Seeders\Account;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'Manajemen Pengguna' => ['create pengguna', 'edit pengguna', 'view pengguna', 'import pengguna', 'export pengguna', 'aktifkan pengguna', 'reset password'],
            'Manajemen Aktivitas' => ['create aktivitas', 'edit aktivitas', 'delete aktivitas', 'view aktivitas', 'import aktivitas', 'export aktivitas'],
            'Publik Request' => ['view publikRequest'],
        ];

        foreach ($permissions as $group => $perms) {
            foreach ($perms as $permission) {
                Permission::create(['name' => $permission, 'group' => $group]);
            }
        }
    }
}
