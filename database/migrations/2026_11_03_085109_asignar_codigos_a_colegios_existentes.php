
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $codigos = [
            1 => 'LPZ',
            2 => 'SCZ',
            3 => 'CBB',
            4 => 'ORU',
            5 => 'POT',
            6 => 'PAN',
            7 => 'CHQ',
            8 => 'TAR',
            9 => 'BEN',
            10 => 'ELA',
            11 => 'TPZ',
            12 => 'CMR',
            13 => 'CTV',
            14 => 'YCB',
        ];

        foreach ($codigos as $id => $codigo) {
            DB::table('colegios')
                ->where('id', $id)
                ->whereNull('codigo')
                ->update(['codigo' => $codigo]);
        }
    }

    public function down(): void
    {
        DB::table('colegios')
            ->whereIn('codigo', [
                'LPZ', 'SCZ', 'CBB', 'ORU', 'POT', 'PAN',
                'CHQ', 'TAR', 'BEN', 'ELA', 'TPZ', 'CMR',
                'CTV', 'YCB',
            ])
            ->update(['codigo' => null]);
    }
};
