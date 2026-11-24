<?php

namespace Tests\Feature;

use App\Services\Importaciones\AportesHistoricos\AfiliadoMatcher;
use Tests\Concerns\BuildsDomainRecords;
use Tests\TestCase;

class AfiliadoSearchTest extends TestCase
{
    use BuildsDomainRecords;

    public function test_affiliate_search_requires_each_term_but_ignores_name_order(): void
    {
        $college = $this->createCollege();
        $admin = $this->createUser('ADMIN', $college);
        $target = $this->createAffiliate($college, [
            'ci' => '2175678',
            'nombres' => 'Lucy',
            'apellidos' => 'Achu Yahuita',
        ]);
        $other = $this->createAffiliate($college, [
            'ci' => '1234567',
            'nombres' => 'María',
            'apellidos' => 'López',
        ]);

        foreach ([
            'Achu Yahuita Lucy',
            'Yahuita Lucy',
            'Lucy',
            'Achu Yahuita',
        ] as $query) {
            $this->actingAs($admin)
                ->get('/afiliados?buscar='.urlencode($query))
                ->assertOk()
                ->assertSee($target->ci)
                ->assertDontSee($other->ci);
        }

        $this->actingAs($admin)
            ->get('/afiliados?buscar=Lucy%20Inexistente')
            ->assertOk()
            ->assertDontSee($target->ci);
    }

    public function test_historical_contribution_matcher_ignores_word_order(): void
    {
        $affiliate = $this->createAffiliate(null, [
            'nombres' => 'Lucy',
            'apellidos' => 'Achu Yahuita',
        ]);
        $matcher = new AfiliadoMatcher();

        foreach ([
            'Lucy Achu Yahuita',
            'Achu Yahuita Lucy',
            'Lucy',
            'Achu Yahuita',
            'Yahuita Lucy',
        ] as $name) {
            $match = $matcher->buscar($name);

            $this->assertContains(
                $match['estado'],
                ['ENCONTRADA', 'ENCONTRADA_POR_ABREVIACION'],
                $name
            );
            $this->assertSame($affiliate->id, $match['afiliado']->id);
        }
    }

    public function test_historical_contribution_matcher_keeps_partial_names_ambiguous(): void
    {
        $this->createAffiliate(null, [
            'nombres' => 'Lucy',
            'apellidos' => 'Achu Yahuita',
        ]);
        $this->createAffiliate(null, [
            'nombres' => 'Lucy',
            'apellidos' => 'Mamani',
        ]);

        $match = (new AfiliadoMatcher())->buscar('Lucy');

        $this->assertSame('AMBIGUA', $match['estado']);
        $this->assertNull($match['afiliado']);
        $this->assertCount(2, $match['afiliados']);
    }

    public function test_payment_lookup_searches_names_in_either_order_and_reports_ambiguity(): void
    {
        $college = $this->createCollege();
        $admin = $this->createUser('ADMIN', $college);
        $target = $this->createAffiliate($college, [
            'ci' => null,
            'nombres' => 'Lucy',
            'apellidos' => 'Achu Yahuita',
        ]);
        $this->createAffiliate($college, [
            'nombres' => 'Lucy',
            'apellidos' => 'Mamani',
        ]);
        $porCi = $this->createAffiliate($college, [
            'ci' => '987654321',
        ]);

        foreach ([
            'Achu Yahuita Lucy',
            'Lucy Achu Yahuita',
        ] as $query) {
            $this->actingAs($admin)
                ->getJson(route('afiliados.buscar-ci', ['buscar' => $query]))
                ->assertOk()
                ->assertJsonPath('id', $target->id)
                ->assertJsonPath('ci', null);
        }

        $this->actingAs($admin)
            ->getJson(route('afiliados.buscar-ci', ['buscar' => 'Lucy']))
            ->assertOk()
            ->assertJsonPath('ambigua', true)
            ->assertJsonCount(2, 'resultados');

        $this->actingAs($admin)
            ->getJson(route('afiliados.buscar-ci', ['ci' => $porCi->ci]))
            ->assertOk()
            ->assertJsonPath('id', $porCi->id);
    }
}
