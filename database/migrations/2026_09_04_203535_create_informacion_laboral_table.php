<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('informacion_laboral', function (Blueprint $table) {
            $table->id();

            $table->foreignId('afiliado_id')
                ->constrained('afiliados')
                ->restrictOnDelete();

            $table->boolean('trabaja');

            $table->string('institucion', 150)
                ->nullable();

            $table->string('tipo_contrato', 100)
                ->nullable();

            $table->decimal('haber_basico', 10, 2)
                ->nullable();

            $table->decimal('monto_aporte', 10, 2);

            $table->date('fecha_ingreso_trabajo')
                ->nullable();

            $table->boolean('culminado')
                ->default(false);

            $table->date('fecha_retiro')
                ->nullable();

            $table->timestamp('creado_en')
                ->useCurrent();

            $table->timestamp('actualizado_en')
                ->useCurrent()
                ->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('informacion_laboral');
    }
};