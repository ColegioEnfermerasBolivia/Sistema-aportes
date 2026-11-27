<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('egresada_formacion', function (Blueprint $table) {
            $table->id();

            $table->foreignId('egresada_id')
                ->constrained('egresadas')
                ->restrictOnDelete();

            $table->foreignId('universidad_id')
                ->constrained('universidades')
                ->restrictOnDelete();

            $table->foreignId('departamento_id')
                ->constrained('departamentos')
                ->restrictOnDelete();

            $table->string('carrera', 200);

            $table->date('fecha_egreso')->nullable();

            $table->string('estado_titulacion', 20)
                ->default('EGRESADA');

            $table->string('numero_titulo', 100)->nullable();

            $table->foreignId('registrado_por')
                ->constrained('usuarios')
                ->restrictOnDelete();

            $table->foreignId('actualizado_por')
                ->nullable()
                ->constrained('usuarios')
                ->restrictOnDelete();

            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')
                ->useCurrent()
                ->useCurrentOnUpdate();

            $table->index(['departamento_id', 'carrera']);
            $table->index('fecha_egreso');
            $table->index('estado_titulacion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egresada_formacion');
    }
};
