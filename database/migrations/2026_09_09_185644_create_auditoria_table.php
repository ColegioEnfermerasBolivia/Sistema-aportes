<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria', function (Blueprint $table) {
            $table->id();

            $table->foreignId('usuario_id')
                ->constrained('usuarios')
                ->restrictOnDelete();

            $table->string('accion', 50);
            $table->string('tabla_afectada', 100);

            $table->unsignedBigInteger('registro_id')->nullable();

            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();

            $table->timestamp('fecha_registro')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria');
    }
};