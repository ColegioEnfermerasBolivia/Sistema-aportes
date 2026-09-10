<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aportes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('afiliado_id')
                ->constrained('afiliados')
                ->restrictOnDelete();

            $table->foreignId('colegio_id')
                ->constrained('colegios')
                ->restrictOnDelete();

            $table->foreignId('pago_id')
                ->constrained('pagos')
                ->restrictOnDelete();

            $table->unsignedTinyInteger('mes');

            $table->unsignedSmallInteger('year');

            $table->decimal('monto', 10, 2);

            $table->string('estado', 30);

            $table->text('observaciones')
                ->nullable();

            $table->foreignId('registrado_por')
                ->constrained('usuarios')
                ->restrictOnDelete();

            $table->timestamp('fecha_registro')
                ->useCurrent();

            $table->foreignId('verificado_por')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete();

            $table->timestamp('fecha_verificacion')
                ->nullable();

            $table->timestamp('creado_en')
                ->useCurrent();

            $table->timestamp('actualizado_en')
                ->useCurrent()
                ->useCurrentOnUpdate();

            $table->unique([
                'afiliado_id',
                'mes',
                'year',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aportes');
    }
};