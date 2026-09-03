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
    Schema::create('usuarios', function (Blueprint $table) {
        $table->id();
        $table->string('ci');
        $table->string('nombres');
        $table->string('apellidos');
        $table->string('username')->unique();
        $table->string('password');
        $table->boolean('debe_cambiar_password')->default(true);
        $table->string('cel');
        $table->foreignId('rol_id')
            ->constrained('roles')
            ->restrictOnDelete();
        $table->foreignId('colegio_id')
            ->nullable()
            ->constrained('colegios')
            ->restrictOnDelete();
        $table->enum('estado', ['ACTIVO', 'INACTIVO'])->default('ACTIVO');
        $table->timestamp('creado_en')->useCurrent();
        $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
