<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('afiliado_id')
                ->constrained('afiliados')
                ->restrictOnDelete();

            $table->foreignId('lote_verificacion_id')
                ->nullable()
                ->constrained('lote_verificacion')
                ->nullOnDelete();

            $table->decimal('monto_total', 10, 2);

            $table->unsignedInteger('cantidad_periodos');

            $table->string('estado', 30);

            $table->text('observaciones')
                ->nullable();

            $table->foreignId('registrado_por')
                ->constrained('usuarios')
                ->restrictOnDelete();

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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};