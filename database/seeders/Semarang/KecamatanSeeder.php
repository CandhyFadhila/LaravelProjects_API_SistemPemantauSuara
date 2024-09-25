<?php

namespace Database\Seeders\Semarang;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class KecamatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kecamatan = [
            [
                'nama_kecamatan' => 'Mijen',
                'provinsi_id' => 1,
                'kabupaten_id' => 1,
                'created_at' => Carbon::now('Asia/Jakarta'),
                'updated_at' => Carbon::now('Asia/Jakarta'),
            ],
            [
                'nama_kecamatan' => 'Gunung Pati',
                'provinsi_id' => 1,
                'kabupaten_id' => 1,
                'created_at' => Carbon::now('Asia/Jakarta'),
                'updated_at' => Carbon::now('Asia/Jakarta'),
            ],
            [
                'nama_kecamatan' => 'Banyumanik',
                'provinsi_id' => 1,
                'kabupaten_id' => 1,
                'created_at' => Carbon::now('Asia/Jakarta'),
                'updated_at' => Carbon::now('Asia/Jakarta'),
            ],
            [
                'nama_kecamatan' => 'Gajah Mungkur',
                'provinsi_id' => 1,
                'kabupaten_id' => 1,
                'created_at' => Carbon::now('Asia/Jakarta'),
                'updated_at' => Carbon::now('Asia/Jakarta'),
            ],
            [
                'nama_kecamatan' => 'Semarang Selatan',
                'provinsi_id' => 1,
                'kabupaten_id' => 1,
                'created_at' => Carbon::now('Asia/Jakarta'),
                'updated_at' => Carbon::now('Asia/Jakarta'),
            ],
            [
                'nama_kecamatan' => 'Candisari',
                'provinsi_id' => 1,
                'kabupaten_id' => 1,
                'created_at' => Carbon::now('Asia/Jakarta'),
                'updated_at' => Carbon::now('Asia/Jakarta'),
            ],
            [
                'nama_kecamatan' => 'Tembalang',
                'provinsi_id' => 1,
                'kabupaten_id' => 1,
                'created_at' => Carbon::now('Asia/Jakarta'),
                'updated_at' => Carbon::now('Asia/Jakarta'),
            ],
            [
                'nama_kecamatan' => 'Pedurungan',
                'provinsi_id' => 1,
                'kabupaten_id' => 1,
                'created_at' => Carbon::now('Asia/Jakarta'),
                'updated_at' => Carbon::now('Asia/Jakarta'),
            ],
            [
                'nama_kecamatan' => 'Genuk',
                'provinsi_id' => 1,
                'kabupaten_id' => 1,
                'created_at' => Carbon::now('Asia/Jakarta'),
                'updated_at' => Carbon::now('Asia/Jakarta'),
            ],
            [
                'nama_kecamatan' => 'Gayamsari',
                'provinsi_id' => 1,
                'kabupaten_id' => 1,
                'created_at' => Carbon::now('Asia/Jakarta'),
                'updated_at' => Carbon::now('Asia/Jakarta'),
            ],
            [
                'nama_kecamatan' => 'Semarang Timur',
                'provinsi_id' => 1,
                'kabupaten_id' => 1,
                'created_at' => Carbon::now('Asia/Jakarta'),
                'updated_at' => Carbon::now('Asia/Jakarta'),
            ],
            [
                'nama_kecamatan' => 'Semarang Utara',
                'provinsi_id' => 1,
                'kabupaten_id' => 1,
                'created_at' => Carbon::now('Asia/Jakarta'),
                'updated_at' => Carbon::now('Asia/Jakarta'),
            ],
            [
                'nama_kecamatan' => 'Semarang Tengah',
                'provinsi_id' => 1,
                'kabupaten_id' => 1,
                'created_at' => Carbon::now('Asia/Jakarta'),
                'updated_at' => Carbon::now('Asia/Jakarta'),
            ],
            [
                'nama_kecamatan' => 'Semarang Barat',
                'provinsi_id' => 1,
                'kabupaten_id' => 1,
                'created_at' => Carbon::now('Asia/Jakarta'),
                'updated_at' => Carbon::now('Asia/Jakarta'),
            ],
            [
                'nama_kecamatan' => 'Tugu',
                'provinsi_id' => 1,
                'kabupaten_id' => 1,
                'created_at' => Carbon::now('Asia/Jakarta'),
                'updated_at' => Carbon::now('Asia/Jakarta'),
            ],
            [
                'nama_kecamatan' => 'Ngaliyan',
                'provinsi_id' => 1,
                'kabupaten_id' => 1,
                'created_at' => Carbon::now('Asia/Jakarta'),
                'updated_at' => Carbon::now('Asia/Jakarta'),
            ],
        ];
        DB::table('kecamatans')->insert($kecamatan);
    }
}
