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
        Schema::create('quick_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pasangan_calon_id')->constrained('pasangan_calons');
            $table->integer('periode'); // tahun
            $table->integer('jumlah_suara')->nullable();
            $table->foreignId('kategori_suara_id')->constrained('kategori_suaras');
            // $table->string('bukti_coblos');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quick_counts');
    }
};
