<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            DB::table('devengado_periodos')->whereNotNull('documento_path')->exists()
            || DB::table('devengado_exoneraciones')->whereNotNull('documento_path')->exists()
        ) {
            throw new RuntimeException(
                'Hay rutas documentales existentes; la migración se detuvo para preservar esos datos.'
            );
        }

        Schema::table('devengado_lotes', function (Blueprint $table): void {
            $table->unsignedTinyInteger('mes')->nullable()->after('colegio_id');
            $table->unsignedSmallInteger('year')->nullable()->after('mes');
            $table->unique(
                ['colegio_id', 'mes', 'year'],
                'devengado_lotes_colegio_mes_year_unique'
            );
        });

        Schema::table('devengado_pagos', function (Blueprint $table): void {
            $table->foreignId('devengado_lote_id')
                ->nullable()
                ->after('id')
                ->constrained('devengado_lotes')
                ->restrictOnDelete();
            $table->decimal('monto_mensual', 10, 2)->nullable();
            $table->decimal('saldo_generado', 10, 2)->default(0);
            $table->unsignedInteger('cantidad_periodos')->default(0);
            $table->string('estado', 30)->default('EN_LOTE');
            $table->foreignId('verificado_por')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete();
            $table->timestamp('fecha_verificacion')->nullable();
            $table->index(
                ['devengado_lote_id', 'estado'],
                'devengado_pagos_lote_estado_idx'
            );
        });

        Schema::table('devengado_periodos', function (Blueprint $table): void {
            $table->dropColumn('documento_path');
        });

        Schema::table('devengado_exoneraciones', function (Blueprint $table): void {
            $table->dropColumn('documento_path');
        });
    }

    public function down(): void
    {
        Schema::table('devengado_exoneraciones', function (Blueprint $table): void {
            $table->string('documento_path', 500)->nullable();
        });

        Schema::table('devengado_periodos', function (Blueprint $table): void {
            $table->string('documento_path', 500)->nullable();
        });

        Schema::table('devengado_pagos', function (Blueprint $table): void {
            $table->dropIndex('devengado_pagos_lote_estado_idx');
            $table->dropForeign(['verificado_por']);
            $table->dropForeign(['devengado_lote_id']);
            $table->dropColumn([
                'devengado_lote_id',
                'monto_mensual',
                'saldo_generado',
                'cantidad_periodos',
                'estado',
                'verificado_por',
                'fecha_verificacion',
            ]);
        });

        Schema::table('devengado_lotes', function (Blueprint $table): void {
            $table->dropUnique('devengado_lotes_colegio_mes_year_unique');
            $table->dropColumn(['mes', 'year']);
        });
    }
};
