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
        Schema::create('aktivitas_pelaksanas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pelaksana_id')->constrained('users');
            $table->string('nama_aktivitas');
            $table->foreignId('status_aktivitas_id')->constrained('status_aktivitas');
            $table->text('deskripsi')->nullable();
            $table->string('tgl_mulai');
            $table->string('tgl_selesai');
            $table->string('tempat_aktivitas');
            $table->string('foto_aktivitas');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aktivitas_pelaksanas');
    }
};
