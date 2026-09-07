<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postgrados', function (Blueprint $table) {
            $table->id();

            $table->string('nombre', 150)->unique();

            $table->enum('estado', [
                'ACTIVO',
                'INACTIVO',
            ])->default('ACTIVO');

            $table->timestamp('creado_en')
                ->useCurrent();

            $table->timestamp('actualizado_en')
                ->useCurrent()
                ->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postgrados');
    }
};