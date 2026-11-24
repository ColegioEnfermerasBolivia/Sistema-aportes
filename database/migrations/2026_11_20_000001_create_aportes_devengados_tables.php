<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devengado_lotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colegio_id')
                ->constrained('colegios')
                ->restrictOnDelete();
            $table->string('codigo_lote', 100)->unique();
            $table->string('estado', 30)->default('ABIERTO');
            $table->text('observaciones')->nullable();
            $table->foreignId('creado_por')
                ->constrained('usuarios')
                ->restrictOnDelete();
            $table->foreignId('enviado_por')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete();
            $table->foreignId('revisado_por')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete();
            $table->timestamp('fecha_envio')->nullable();
            $table->timestamp('fecha_revision')->nullable();
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
            $table->index(['colegio_id', 'estado']);
        });

        Schema::create('devengado_periodos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('devengado_lote_id')
                ->constrained('devengado_lotes')
                ->restrictOnDelete();
            $table->foreignId('afiliado_id')
                ->constrained('afiliados')
                ->restrictOnDelete();
            $table->unsignedTinyInteger('mes');
            $table->unsignedSmallInteger('year');
            $table->decimal('monto', 10, 2);
            $table->string('metodo_determinacion', 30);
            $table->text('observaciones')->nullable();
            $table->string('documento_path', 500)->nullable();
            $table->string('estado', 30)->default('PENDIENTE');
            $table->text('observacion_revision')->nullable();
            $table->foreignId('revisado_por')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete();
            $table->timestamp('fecha_revision')->nullable();
            $table->foreignId('aporte_id')
                ->nullable()
                ->unique()
                ->constrained('aportes')
                ->restrictOnDelete();
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
            $table->unique(
                ['devengado_lote_id', 'afiliado_id', 'mes', 'year'],
                'devengado_periodo_lote_afiliado_unique'
            );
            $table->index(['afiliado_id', 'year', 'mes']);
            $table->index(['estado', 'devengado_lote_id']);
        });

        Schema::create('devengado_pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('afiliado_id')
                ->constrained('afiliados')
                ->restrictOnDelete();
            $table->decimal('monto_total', 18, 2);
            $table->uuid('clave_idempotencia')->unique();
            $table->text('observaciones')->nullable();
            $table->foreignId('registrado_por')
                ->constrained('usuarios')
                ->restrictOnDelete();
            $table->timestamp('fecha_pago');
            $table->timestamp('creado_en')->useCurrent();
            $table->index(['afiliado_id', 'fecha_pago']);
        });

        Schema::create('devengado_pago_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('devengado_pago_id')
                ->constrained('devengado_pagos')
                ->restrictOnDelete();
            $table->foreignId('devengado_periodo_id')
                ->constrained('devengado_periodos')
                ->restrictOnDelete();
            $table->decimal('monto', 10, 2);
            $table->timestamp('creado_en')->useCurrent();
            $table->unique(
                ['devengado_pago_id', 'devengado_periodo_id'],
                'devengado_pago_periodo_unique'
            );
            $table->index('devengado_periodo_id');
        });

        Schema::create('devengado_exoneraciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('devengado_periodo_id')
                ->constrained('devengado_periodos')
                ->restrictOnDelete();
            $table->decimal('monto', 10, 2);
            $table->text('motivo');
            $table->string('presidente_nombre', 200);
            $table->string('autorizacion_referencia', 200);
            $table->string('documento_path', 500);
            $table->foreignId('registrado_por')
                ->constrained('usuarios')
                ->restrictOnDelete();
            $table->timestamp('fecha_exoneracion');
            $table->timestamp('creado_en')->useCurrent();
            $table->index('devengado_periodo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devengado_exoneraciones');
        Schema::dropIfExists('devengado_pago_detalles');
        Schema::dropIfExists('devengado_pagos');
        Schema::dropIfExists('devengado_periodos');
        Schema::dropIfExists('devengado_lotes');
    }
};
