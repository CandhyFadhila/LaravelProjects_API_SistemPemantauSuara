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
        Schema::create('status_aktivitas', function (Blueprint $table) {
            $table->id();
            $table->string('label'); // 1 = Belum Terlaksana, 2 = Dilaksanakan, 3 = Telah Dilaksanakan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_aktivitas');
    }
};
