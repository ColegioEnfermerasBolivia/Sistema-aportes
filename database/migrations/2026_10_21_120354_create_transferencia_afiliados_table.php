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
        Schema::create('transferencias_afiliados', function (Blueprint $table) {
            $table->id();

            $table->foreignId('afiliado_id')
                ->constrained('afiliados')
                ->restrictOnDelete();

            $table->foreignId('colegio_id_origen')
                ->constrained('colegios')
                ->restrictOnDelete();

            $table->foreignId('colegio_id_destino')
                ->constrained('colegios')
                ->restrictOnDelete();

            $table->date('fecha_transferencia');

            $table->timestamp('creado_en')
                ->useCurrent();

            $table->timestamp('actualizado_en')
                ->useCurrent()
                ->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transferencia_afiliados');
    }
};
