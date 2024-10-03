<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('id_ID');

        for ($i = 1; $i <= 20; $i++) {
            $jenisKelamin = $faker->randomElement([0, 1]); // 0 = perempuan, 1 = laki-laki

            $user = User::create([
                'nama' => $faker->name($jenisKelamin ? 'female' : 'male'),
                'username' => $faker->unique()->userName,
                'nik_ktp' => $faker->unique()->numerify('###############'),
                'foto_profil' => null,
                'no_hp' => $faker->phoneNumber,
                'tgl_diangkat' => $faker->date(),
                'jenis_kelamin' => $jenisKelamin,
                'password' => Hash::make('password123'),
                'status_aktif' => rand(0, 1),
            ]);

            $role = Role::where('name', 'Pelaksana')->first();
            if ($role) {
                $user->assignRole($role);
            }
        }
    }
}
