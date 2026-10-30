<?php

namespace Tests\Unit;

use App\Models\Saldo;
use App\Services\SaldoService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Pure-math invariants of SaldoService::distribuir(), in integer cents.
 * If your distribuir() receives something other than an Eloquent collection of
 * Saldo models, adjust balances() only.
 */
class SaldoServiceInvariantsTest extends TestCase
{
    public static function scenarios(): array
    {
        // [previous balance, payment total, monthly fee], all in cents
        return [
            'no balance, exact'            => [0, 10000, 10000],
            'no balance, leftover'         => [0, 25000, 10000],
            'below one period'             => [0, 9999, 10000],
            'repeating thirds'             => [0, 10000, 3333],
            'balance completes a period'   => [5000, 5000, 10000],
            'balance alone covers periods' => [30000, 0, 10000],
            'balance + total, leftover'    => [5000, 6000, 10000],
            'one cent fee'                 => [0, 99, 1],
            'very large payment'           => [0, 9999999999, 10000],
            'large balance and payment'    => [999999999, 9999999999, 3333],
        ];
    }

    #[DataProvider('scenarios')]
    public function test_distribution_conserves_every_cent(int $previous, int $total, int $monthly): void
    {
        $result = app(SaldoService::class)->distribuir($this->balances($previous), $total, $monthly);

        $this->assertSame($previous, $result['saldo_anterior_centavos']);
        $this->assertSame(
            $result['cantidad_periodos'] * $monthly,
            $result['costo_periodos_centavos']
        );
        $this->assertSame(
            $previous + $total,
            $result['costo_periodos_centavos'] + $result['saldo_final_centavos'],
            'Money was created or lost.'
        );
        $this->assertGreaterThanOrEqual(0, $result['saldo_final_centavos']);
        $this->assertLessThan($monthly, $result['saldo_final_centavos'], 'A full period was left unconsumed.');
    }

    public function test_results_are_integers_not_floats(): void
    {
        $result = app(SaldoService::class)->distribuir($this->balances(1234), 5678, 333);

        foreach (['cantidad_periodos', 'costo_periodos_centavos', 'saldo_anterior_centavos', 'saldo_final_centavos'] as $key) {
            $this->assertIsInt($result[$key], "{$key} must be an int.");
        }
    }

    private function balances(int $cents): Collection
    {
        if ($cents === 0) {
            return new Collection();
        }

        $saldo = (new Saldo)->forceFill([
            'monto_disponible' => number_format($cents / 100, 2, '.', ''),
            'estado' => 'ACTIVO',
        ]);

        return new Collection([$saldo]);
    }
}
