<?php

namespace Database\Seeders\Counters;

use App\Models\QuickCount;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QuickCountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        QuickCount::create([
            'pasangan_calon_id' => 1,
            'periode' => 2024,
            'kategori_suara_id' => 2,
        ]);

        QuickCount::create([
            'pasangan_calon_id' => 2,
            'periode' => 2024,
            'kategori_suara_id' => 2,
        ]);
    }
}
