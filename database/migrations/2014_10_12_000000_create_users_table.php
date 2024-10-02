<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('username')->unique();
            $table->string('nik_ktp', 16)->nullable(); // wajib
            $table->string('foto_profil')->nullable();
            $table->string('no_hp', 50)->nullable(); // wajib
            $table->string('tgl_diangkat')->nullable();
            $table->boolean('jenis_kelamin'); // 1 = laki-laki, 0 = perempuan
            $table->foreignId('role_id')->nullable();
            $table->boolean('status_aktif')->default(true); // 0 = non aktif, 1 = aktif
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
