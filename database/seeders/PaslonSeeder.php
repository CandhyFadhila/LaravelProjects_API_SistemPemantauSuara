<?php

namespace Database\Seeders;

use App\Models\PasanganCalon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaslonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PasanganCalon::create([
            'nama' => 'Yoyok Joss',
            'partai_id' => 1,
        ]);

        PasanganCalon::create([
            'nama' => 'Agustin Iswar',
            'partai_id' => 4,
        ]);
    }
}
