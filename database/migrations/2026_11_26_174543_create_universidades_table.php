<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('universidades', function (Blueprint $table) {
            $table->id();

            $table->string('nombre', 200)->unique();
            $table->string('sigla', 30)->nullable();

            $table->string('tipo', 20);
            $table->string('estado', 20)->default('ACTIVA');

            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')
                ->useCurrent()
                ->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('universidades');
    }
};
