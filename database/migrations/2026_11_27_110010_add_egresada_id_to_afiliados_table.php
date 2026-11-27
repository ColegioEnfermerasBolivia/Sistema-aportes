<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('afiliados', function (Blueprint $table) {
            $table->foreignId('egresada_id')
                ->nullable()
                ->after('id')
                ->unique()
                ->constrained('egresadas')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('afiliados', function (Blueprint $table) {
            $table->dropConstrainedForeignId('egresada_id');
        });
    }
};
