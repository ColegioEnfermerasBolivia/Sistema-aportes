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
        Schema::create('observaciones_afiliados', function (Blueprint $table) {
            $table->id();

            $table->foreignId('afiliado_id')
                ->constrained('afiliados')
                ->restrictOnDelete();

            $table->text('observacion');

            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete();

            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();

            $table->index(['afiliado_id', 'creado_en']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('observaciones_afiliados');
    }
};
