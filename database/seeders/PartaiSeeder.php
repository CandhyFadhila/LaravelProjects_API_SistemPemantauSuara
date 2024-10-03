<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PartaiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $status = ['PKB', 'GERINDRA', 'PDIP', 'GOLKAR', 'NASDEM', 'BURUH', 'GELORA', 'PKS', 'PKN', 'HANURA', 'GARUDA', 'PAN', 'PBB', 'PSI', 'PERINDO', 'PPP', 'UMAT'];
        foreach ($status as $status) {
            DB::table('partais')->insert([
                'nama' => $status,
                'created_at' => Carbon::now('Asia/Jakarta'),
                'updated_at' => Carbon::now('Asia/Jakarta'),
            ]);
        }
    }
}
