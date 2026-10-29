<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saldos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('afiliado_id')
                ->constrained('afiliados')
                ->restrictOnDelete();

            $table->foreignId('pago_id')
                ->nullable()
                ->constrained('pagos')
                ->nullOnDelete();

            $table->decimal('monto', 10, 2);

            $table->decimal('monto_disponible', 10, 2);

            $table->enum('estado', [
                'ACTIVO',
                'APLICADO',
            ])->default('ACTIVO');

            $table->text('observacion')->nullable();

            $table->timestamp('creado_en')
                ->useCurrent();

            $table->timestamp('actualizado_en')
                ->useCurrent()
                ->useCurrentOnUpdate();

            $table->index(
                ['afiliado_id', 'estado'],
                'saldos_afiliado_estado_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saldos');
    }
};

