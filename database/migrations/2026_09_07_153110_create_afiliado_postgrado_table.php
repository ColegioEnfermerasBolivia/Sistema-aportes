<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('afiliado_postgrado', function (Blueprint $table) {
            $table->id();

            $table->foreignId('afiliado_id')
                ->constrained('afiliados')
                ->restrictOnDelete();

            $table->foreignId('postgrado_id')
                ->constrained('postgrados')
                ->restrictOnDelete();

            $table->foreignId('grado_academico_id')
                ->constrained('grados_academicos')
                ->restrictOnDelete();

            $table->string('institucion', 150);

            $table->string('numero_titulo', 50);

            $table->date('fecha_titulo');

            $table->string('numero_certificado', 50);

            $table->timestamp('creado_en')
                ->useCurrent();

            $table->timestamp('actualizado_en')
                ->useCurrent()
                ->useCurrentOnUpdate();

            $table->unique(
                'numero_titulo',
                'afiliado_postgrado_numero_titulo_unique'
            );

            $table->unique(
                'numero_certificado',
                'afiliado_postgrado_numero_certificado_unique'
            );

            $table->unique(
                [
                    'afiliado_id',
                    'postgrado_id',
                    'grado_academico_id',
                ],
                'afiliado_postgrado_combinacion_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('afiliado_postgrado');
    }
};