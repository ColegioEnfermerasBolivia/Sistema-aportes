<?php

namespace Tests\Feature;

use App\Models\LoteVerificacion;
use Tests\Concerns\BuildsDomainRecords;
use Tests\TestCase;

class LoteWorkflowTest extends TestCase
{
    use BuildsDomainRecords;

    public function test_user_can_create_and_close_a_batch_for_their_college(): void
    {
        $college = $this->createCollege(['codigo' => 'LPZ']);
        $user = $this->createUser('USUARIO', $college);

        $this->actingAs($user)
            ->post('/aportes/lotes', ['mes' => 3])
            ->assertRedirect(route('lotes.index'));

        $batch = LoteVerificacion::query()->sole();
        $this->assertSame('ABIERTO', $batch->estado);
        $this->assertSame($college->id, $batch->colegio_id);
        $this->assertSame(now()->year, $batch->year);
        $this->assertSame('LPZ-'.now()->year.'-03', $batch->codigo_lote);

        $this->patch("/lotes/{$batch->id}/cerrar")
            ->assertRedirect();

        $this->assertSame('ENVIADO', $batch->fresh()->estado);
        $this->assertSame($user->id, $batch->fresh()->enviado_por);
    }

    public function test_user_cannot_create_a_batch_with_an_invalid_month(): void
    {
        $college = $this->createCollege();
        $user = $this->createUser('USUARIO', $college);

        $this->actingAs($user)
            ->from('/aportes/lotes/crear')
            ->post('/aportes/lotes', ['mes' => 13])
            ->assertRedirect('/aportes/lotes/crear')
            ->assertSessionHasErrors('mes');

        $this->assertDatabaseCount('lote_verificacion', 0);
    }

    public function test_departmental_user_can_create_and_manage_separate_parent_and_child_batches(): void
    {
        $parent = $this->createCollege([
            'codigo' => 'LPZ',
            'tipo' => 'DEPARTAMENTAL',
        ]);
        $child = $this->createCollege([
            'codigo' => 'ORU',
            'colegio_padre_id' => $parent->id,
        ]);
        $user = $this->createUser('USUARIO', $parent);

        $this->actingAs($user)
            ->post('/aportes/lotes', [
                'mes' => 3,
                'colegio_id' => $parent->id,
            ])
            ->assertRedirect(route('lotes.index'));

        $this->post('/aportes/lotes', [
            'mes' => 3,
            'colegio_id' => $child->id,
        ])->assertRedirect(route('lotes.index'));

        $parentBatch = LoteVerificacion::query()
            ->where('colegio_id', $parent->id)
            ->sole();
        $childBatch = LoteVerificacion::query()
            ->where('colegio_id', $child->id)
            ->sole();

        $this->assertSame('LPZ-'.now()->year.'-03', $parentBatch->codigo_lote);
        $this->assertSame('ORU-'.now()->year.'-03', $childBatch->codigo_lote);

        $this->get(route('lotes.index'))
            ->assertOk()
            ->assertSee($parentBatch->codigo_lote)
            ->assertSee($childBatch->codigo_lote);

        $affiliate = $this->createAffiliate($child);
        $this->post('/pagos', [
            'afiliado_id' => $affiliate->id,
            'monto_mensual' => 100,
            'monto_total' => 100,
        ])->assertRedirect();

        $this->assertDatabaseHas('pagos', [
            'afiliado_id' => $affiliate->id,
            'lote_verificacion_id' => $childBatch->id,
        ]);

        $this->patch("/lotes/{$childBatch->id}/cerrar")
            ->assertRedirect();
        $this->assertSame('ENVIADO', $childBatch->fresh()->estado);
    }

    public function test_user_cannot_close_a_batch_owned_by_another_college(): void
    {
        $user = $this->createUser('USUARIO', $this->createCollege());
        $otherCollegeBatch = $this->createBatch(
            $this->createCollege(),
            ['estado' => 'ABIERTO']
        );

        $this->actingAs($user)
            ->patch("/lotes/{$otherCollegeBatch->id}/cerrar")
            ->assertForbidden();

        $this->assertSame('ABIERTO', $otherCollegeBatch->fresh()->estado);
    }
}
