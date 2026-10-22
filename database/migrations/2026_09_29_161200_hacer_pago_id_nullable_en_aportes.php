<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aportes', function (Blueprint $table) {
            $table->dropForeign(['pago_id']);

            $table->foreignId('pago_id')
                ->nullable()
                ->change();

            $table->foreign('pago_id')
                ->references('id')
                ->on('pagos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('aportes', function (Blueprint $table) {
            $table->dropForeign(['pago_id']);

            $table->foreignId('pago_id')
                ->nullable(false)
                ->change();

            $table->foreign('pago_id')
                ->references('id')
                ->on('pagos')
                ->restrictOnDelete();
        });
    }
};