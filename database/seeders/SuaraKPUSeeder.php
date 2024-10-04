<?php

namespace Database\Seeders;

use App\Models\KategoriSuara;
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
                'partai_id' => Partai::inRandomOrder()->first()->id,
                'kelurahan_id' => Kelurahan::inRandomOrder()->first()->id,
                'tahun' => 2024,
                'tps' => rand(1, 10),
                'kategori_suara_id' => KategoriSuara::inRandomOrder()->first()->id,
                'alamat' => 'Jalan Jalan',
                'cakupan_wilayah' => 'Cakupan wilayah dari import',
                'jumlah_suara' => rand(100, 500),
                'dpt_laki' => rand(500, 1000),
                'dpt_perempuan' => rand(500, 1000),
                'jumlah_dpt' => rand(500, 1000),
                'suara_caleg' => rand(50, 250),
                'suara_partai' => rand(50, 250),
            ]);
        }
    }
}
