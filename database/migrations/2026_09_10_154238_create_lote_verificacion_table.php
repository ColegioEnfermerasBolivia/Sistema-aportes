<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lote_verificacion', function (Blueprint $table) {
            $table->id();

            $table->foreignId('colegio_id')
                ->constrained('colegios')
                ->restrictOnDelete();

            $table->unsignedTinyInteger('mes');

            $table->unsignedSmallInteger('year');

            $table->string('codigo_lote', 100)
                ->unique();

            $table->unsignedInteger('cantidad_aportes');

            $table->decimal('monto_total', 12, 2);

            $table->string('estado', 30);

            $table->timestamp('fecha_cierre_departamental')
                ->nullable();

            $table->timestamp('fecha_recepcion_col_nacional')
                ->nullable();

            $table->timestamp('fecha_verificacion')
                ->nullable();

            $table->foreignId('enviado_por')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete();

            $table->foreignId('verificado_por')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete();

            $table->text('observaciones_generales')
                ->nullable();

            $table->timestamp('creado_en')
                ->useCurrent();

            $table->timestamp('actualizado_en')
                ->useCurrent()
                ->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lote_verificacion');
    }
};