<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = [
        'ci',
        'registro_departamental',
        'matricula_profesional',
    ];

    public function up(): void
    {
        Schema::table('afiliados', function (Blueprint $table) {
            $table->string('ci', 20)->nullable()->change();
            $table->string('registro_departamental', 30)->nullable()->change();
            $table->string('matricula_profesional', 30)->nullable()->change();
        });
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $column) {
            if (DB::table('afiliados')->whereNull($column)->exists()) {
                throw new LogicException(
                    "No se puede revertir la migración: hay afiliadas sin {$column}."
                );
            }
        }

        Schema::table('afiliados', function (Blueprint $table) {
            $table->string('ci', 20)->nullable(false)->change();
            $table->string('registro_departamental', 30)->nullable(false)->change();
            $table->string('matricula_profesional', 30)->nullable(false)->change();
        });
    }
};
