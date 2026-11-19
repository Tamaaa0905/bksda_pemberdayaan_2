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
        Schema::table('kelompoks', function (Blueprint $table) {
            $table->string('status_keaktifan', 20)->nullable();
            $table->text('kendala')->nullable();
            $table->boolean('butuh_bantuan_lanjutan')->nullable();
            $table->boolean('butuh_pendampingan')->nullable();
            $table->text('catatan_kebutuhan')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kelompoks', function (Blueprint $table) {
            $table->dropColumn([
                'status_keaktifan',
                'kendala',
                'butuh_bantuan_lanjutan',
                'butuh_pendampingan',
                'catatan_kebutuhan',
            ]);
        });
    }
};
