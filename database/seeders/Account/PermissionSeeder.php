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
            'Manajemen Pengguna' => ['create pengguna', 'edit pengguna', 'delete pengguna', 'view pengguna'],
            'Manajemen Aktivitas' => ['create aktivitas', 'edit aktivitas', 'delete aktivitas', 'view aktivitas'],
        ];

        foreach ($permissions as $group => $perms) {
            foreach ($perms as $permission) {
                Permission::create(['name' => $permission, 'group' => $group]);
            }
        }
    }
}
