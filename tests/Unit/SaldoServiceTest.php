<?php

namespace Tests\Unit;

use App\Models\Saldo;
use App\Services\SaldoService;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SaldoServiceTest extends TestCase
{
    public function test_it_distributes_money_in_integer_centavos(): void
    {
        $service = new SaldoService();
        $saldos = new Collection([
            new Saldo(['monto_disponible' => '0.10']),
            new Saldo(['monto_disponible' => '0.20']),
        ]);

        $result = $service->distribuir($saldos, 1049, 100);

        $this->assertSame(30, $result['saldo_anterior_centavos']);
        $this->assertSame(10, $result['cantidad_periodos']);
        $this->assertSame(1000, $result['costo_periodos_centavos']);
        $this->assertSame(79, $result['saldo_final_centavos']);
        $this->assertSame(970, $result['consumo_pago_centavos']);
    }

    public function test_it_rejects_a_non_positive_monthly_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'El monto mensual debe ser mayor que cero.'
        );

        (new SaldoService())->distribuir(new Collection(), 100, 0);
    }

    public function test_it_returns_zero_periods_for_an_amount_below_one_period(): void
    {
        $result = (new SaldoService())->distribuir(
            new Collection(),
            99,
            100
        );

        $this->assertSame(0, $result['cantidad_periodos']);
        $this->assertSame(99, $result['saldo_final_centavos']);
        $this->assertSame(0, $result['consumo_pago_centavos']);
    }
}
