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
        Schema::create('afiliados', function (Blueprint $table) {
            $table->id();

            $table->string('registro_departamental', 30)->unique();
            $table->string('matricula_profesional', 30)->unique();
            $table->string('ci', 20)->unique();

            $table->foreignId('colegio_id')
                ->constrained('colegios')
                ->restrictOnDelete();

            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('celular', 20);

            $table->enum('estado', [
                'ACTIVO',
                'INACTIVO',
            ])->default('ACTIVO');

            $table->date('fecha_afiliacion');

            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('afiliados');
    }
};
