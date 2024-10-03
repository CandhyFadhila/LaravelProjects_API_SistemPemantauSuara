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
        Schema::create('suara_kpus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partai_id')->constrained('partais');
            $table->foreignId('kelurahan_id')->constrained('kelurahans');
            $table->integer('tps'); // 1 sampai jumlah tps yang ada
            $table->integer('jumlah_suara');
            $table->integer('jumlah_dpt'); // suara tetap
            $table->integer('suara_caleg')->nullable(); // suara tetap
            $table->integer('suara_partai')->nullable(); // suara tetap
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suara_kpus');
    }
};
