<?php

namespace Database\Seeders;

use App\Models\Partai;
use App\Models\SuaraKPU;
use App\Models\Kelurahan;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SuaraKPUSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            SuaraKPU::create([
                'partai_id' => Partai::inRandomOrder()->first()->id, // Mengambil partai secara acak
                'kelurahan_id' => Kelurahan::inRandomOrder()->first()->id, // Mengambil kelurahan secara acak
                'tahun' => 2024,
                'tps' => rand(1, 10), // TPS antara 1 sampai 10
                'jumlah_suara' => rand(100, 500), // Jumlah suara antara 100 sampai 500
                'jumlah_dpt' => rand(500, 1000), // Jumlah DPT antara 500 sampai 1000
                'suara_caleg' => rand(50, 250), // Suara caleg antara 50 sampai 250
                'suara_partai' => rand(50, 250), // Suara partai antara 50 sampai 250
            ]);
        }
    }
}
