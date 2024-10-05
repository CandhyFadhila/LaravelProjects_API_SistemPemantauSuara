<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UpcomingTPSSeeder extends Seeder
{
    public function run(): void
    {
        $tpsData = [
            ['kelurahan_id' => 1, 'tahun' => 2024, 'jumlah_tps' => 12],
            ['kelurahan_id' => 2, 'tahun' => 2024, 'jumlah_tps' => 10],
            ['kelurahan_id' => 3, 'tahun' => 2024, 'jumlah_tps' => 15],
            ['kelurahan_id' => 4, 'tahun' => 2024, 'jumlah_tps' => 8],
            ['kelurahan_id' => 5, 'tahun' => 2024, 'jumlah_tps' => 14],
            ['kelurahan_id' => 6, 'tahun' => 2024, 'jumlah_tps' => 11],
            ['kelurahan_id' => 7, 'tahun' => 2024, 'jumlah_tps' => 13],
            ['kelurahan_id' => 8, 'tahun' => 2024, 'jumlah_tps' => 9],
            ['kelurahan_id' => 9, 'tahun' => 2024, 'jumlah_tps' => 16],
            ['kelurahan_id' => 10, 'tahun' => 2024, 'jumlah_tps' => 7],
        ];

        foreach ($tpsData as $data) {
            DB::table('upcoming_tps')->insert([
                'kelurahan_id' => $data['kelurahan_id'],
                'tahun' => $data['tahun'],
                'jumlah_tps' => $data['jumlah_tps'],
                'created_at' => Carbon::now('Asia/Jakarta'),
                'updated_at' => Carbon::now('Asia/Jakarta'),
            ]);
        }
    }
}
