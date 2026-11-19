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
        Schema::create('kegiatan_pemberdayaans', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun');
            $table->string('jenis', 32);
            $table->unsignedSmallInteger('urutan');
            $table->text('nama_kegiatan');
            $table->text('target')->nullable();
            $table->text('waktu')->nullable();
            $table->string('lead_by')->nullable();
            $table->text('anggota_tim')->nullable();
            $table->json('data_sumber')->nullable();
            $table->timestamps();

            $table->index(['tahun', 'jenis', 'urutan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kegiatan_pemberdayaans');
    }
};
