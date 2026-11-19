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
        Schema::table('users', function (Blueprint $table) {
            $table->string('nip', 50)->nullable()->unique();
            $table->string('pangkat_golongan')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('unit_kerja')->nullable();
            $table->string('kecamatan_binaan')->nullable();
        });

        Schema::table('kelompoks', function (Blueprint $table) {
            $table->foreignId('petugas_id')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::create('desa_user', function (Blueprint $table) {
            $table->foreignId('desa_id')->constrained('desas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unique(['user_id', 'desa_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('desa_user');

        Schema::table('kelompoks', function (Blueprint $table) {
            $table->dropForeign(['petugas_id']);
            $table->dropColumn('petugas_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['nip']);
            $table->dropColumn([
                'nip',
                'pangkat_golongan',
                'jabatan',
                'unit_kerja',
                'kecamatan_binaan',
            ]);
        });
    }
};
