<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Concurrency;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\BuildsDomainRecords;
use Tests\Concerns\BuildsLoteScenarios;
use Tests\Support\ConfirmRequest;
use Tests\TestCase;

/**
 * EXPERIMENTAL. Real concurrency: two child PHP processes hit the confirm
 * endpoint at the same time against the *_testing MySQL database.
 *
 * It uses DatabaseTruncation (not RefreshDatabase) because the children must
 * see committed rows. Run on its own:
 *   php artisan test --group=concurrency
 */
#[Group('concurrency')]
class ConcurrentConfirmationTest extends TestCase
{
    use DatabaseTruncation;
    use BuildsDomainRecords;
    use BuildsLoteScenarios;

    public function test_two_simultaneous_confirmations_are_rejected_when_not_authenticated(): void
    {
        $college = $this->makeCollege();
        $afiliado = $this->makeAffiliate($college, '2026-01-10');
        $lote = $this->makeLote($college);
        $pago = $this->makePayment($lote, $afiliado, '100.00', '250.00');
        $userId = $this->reviewer()->id;
        $loteId = $lote->id;
        $pagoIds = [$pago->id];

        $statuses = Concurrency::run([
            static fn () => ConfirmRequest::send($userId, $loteId, $pagoIds),
            static fn () => ConfirmRequest::send($userId, $loteId, $pagoIds),
        ]);

        sort($statuses);

        $this->assertSame([401, 401], $statuses, 'Both child requests are rejected without a valid authenticated session.');
        $this->assertSame(0, $afiliado->aportes()->count(), 'No contribution should be created for unauthenticated requests.');
        $this->assertSame(0, $this->activeBalanceCents($afiliado));
    }
}
