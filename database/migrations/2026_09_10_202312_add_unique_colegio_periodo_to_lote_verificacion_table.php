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
        Schema::table('lote_verificacion', function (Blueprint $table) {
            $table->unique(
                ['colegio_id', 'mes', 'year'],
                'lote_verificacion_colegio_mes_year_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lote_verificacion', function (Blueprint $table) {
            $table->dropUnique('lote_verificacion_colegio_mes_year_unique');
        });
    }
};
