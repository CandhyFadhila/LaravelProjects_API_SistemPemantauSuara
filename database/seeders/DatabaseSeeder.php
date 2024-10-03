<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Database\Seeders\Account\AccountSeeder;
use Database\Seeders\Account\PermissionSeeder;
use Database\Seeders\Account\RoleSeeder;
use Database\Seeders\Aktivitas\AktivitasPelaksanaSeeder;
use Database\Seeders\Aktivitas\StatusAktivitasSeeder;
use Database\Seeders\Semarang\KabupatenSeeder;
use Database\Seeders\Semarang\KecamatanSeeder;
use Database\Seeders\Semarang\KelurahanSeeder;
use Database\Seeders\Semarang\ProvinsiSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            AccountSeeder::class,

            UserSeeder::class,

            ProvinsiSeeder::class,
            KabupatenSeeder::class,
            KecamatanSeeder::class,
            KelurahanSeeder::class,

            PartaiSeeder::class,

            StatusAktivitasSeeder::class,
            AktivitasPelaksanaSeeder::class,
        ]);
    }
}
