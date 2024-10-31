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
        Schema::create('save_winners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quick_count_id')->constrained('quick_counts');
            $table->foreignId('kelurahan_id')->constrained('kelurahans');
            $table->integer('tps');
            $table->integer('jumlah_suara')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('save_winners');
    }
};
