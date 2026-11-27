<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('egresadas', function (Blueprint $table) {
            $table->id();

            $table->string('ci', 30)->unique();
            $table->string('nombres', 150);
            $table->string('apellidos', 150);

            $table->string('estado', 20)->default('ACTIVA');
            $table->string('celular', 30)->nullable();

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

            $table->index(['apellidos', 'nombres']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egresadas');
    }
};
