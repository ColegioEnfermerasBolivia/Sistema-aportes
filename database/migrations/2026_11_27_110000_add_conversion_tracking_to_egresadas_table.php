<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('egresadas', function (Blueprint $table) {
            $table->foreignId('convertida_por')
                ->nullable()
                ->after('actualizado_por')
                ->constrained('usuarios')
                ->restrictOnDelete();

            $table->timestamp('convertida_en')
                ->nullable()
                ->after('convertida_por');

            $table->foreignId('colegio_asignado_id')
                ->nullable()
                ->after('convertida_en')
                ->constrained('colegios')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('egresadas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('colegio_asignado_id');
            $table->dropConstrainedForeignId('convertida_por');
            $table->dropColumn('convertida_en');
        });
    }
};
