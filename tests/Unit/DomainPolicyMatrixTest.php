<?php

namespace Tests\Unit;

use App\Models\Afiliado;
use App\Models\Colegio;
use App\Models\LoteVerificacion;
use App\Models\Pago;
use App\Models\Rol;
use App\Models\Usuario;
use App\Policies\AfiliadoPolicy;
use App\Policies\LoteVerificacionPolicy;
use App\Policies\PagoPolicy;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

class DomainPolicyMatrixTest extends TestCase
{
    public function test_affiliate_policy_covers_role_and_active_state_rules(): void
    {
        $policy = new AfiliadoPolicy();
        $activeAffiliate = new Afiliado([
            'colegio_id' => 10,
            'estado' => 'ACTIVO',
        ]);
        $inactiveAffiliate = new Afiliado([
            'colegio_id' => 10,
            'estado' => 'INACTIVO',
        ]);
        $admin = $this->user('ADMIN', 10);
        $superAdmin = $this->user('SUPER ADMIN', 99);
        $localUser = $this->user('USUARIO', 10);
        $otherUser = $this->user('USUARIO', 20);
        $unknownRole = $this->user('OTRO', 10);

        $this->assertTrue($policy->viewAny($admin));
        $this->assertTrue($policy->viewAny($superAdmin));
        $this->assertTrue($policy->viewAny($localUser));
        $this->assertFalse($policy->viewAny($unknownRole));

        $this->assertFalse($policy->create($superAdmin));
        $this->assertTrue($policy->create($admin));
        $this->assertTrue($policy->create($localUser));
        $this->assertFalse($policy->create($unknownRole));

        $this->assertTrue($policy->update($admin, $activeAffiliate));
        $this->assertTrue($policy->update($superAdmin, $activeAffiliate));
        $this->assertTrue($policy->update($localUser, $activeAffiliate));
        $this->assertFalse($policy->update($otherUser, $activeAffiliate));
        $this->assertFalse($policy->update($admin, $inactiveAffiliate));

        $this->assertTrue($policy->changeColegio($superAdmin, $activeAffiliate));
        $this->assertFalse($policy->changeColegio($admin, $activeAffiliate));
        $this->assertFalse($policy->changeColegio($superAdmin, $inactiveAffiliate));
        $this->assertTrue($policy->changeEstado($admin, $inactiveAffiliate));
        $this->assertFalse($policy->changeEstado($superAdmin, $activeAffiliate));
        $this->assertTrue($policy->createInColegio($admin, 100));
        $this->assertTrue($policy->createInColegio($localUser, 10));
        $this->assertFalse($policy->createInColegio($localUser, 20));
        $this->assertFalse($policy->createInColegio($superAdmin, 10));

        $this->assertTrue($policy->viewLaboral($admin, $activeAffiliate));
        $this->assertFalse($policy->viewLaboral($otherUser, $activeAffiliate));
        $this->assertTrue($policy->createLaboral($localUser, $activeAffiliate));
        $this->assertFalse($policy->createLaboral($localUser, $inactiveAffiliate));
        $this->assertTrue($policy->updateLaboral($localUser, $activeAffiliate));
        $this->assertTrue($policy->viewAcademic($localUser, $activeAffiliate));
        $this->assertTrue($policy->createAcademic($admin, $activeAffiliate));
        $this->assertTrue($policy->updateAcademic($localUser, $activeAffiliate));
        $this->assertTrue($policy->deleteAcademic($localUser, $activeAffiliate));
        $this->assertFalse($policy->createAcademic($superAdmin, $activeAffiliate));
    }

    public function test_departmental_user_may_access_child_colleges_but_not_unrelated_colleges(): void
    {
        $department = new Colegio(['tipo' => 'DEPARTAMENTAL']);
        $department->id = 10;
        $child = new Colegio();
        $child->id = 11;
        $department->setRelation('hijos', new Collection([$child]));

        $user = $this->user('USUARIO', 10);
        $user->setRelation('colegio', $department);
        $policy = new AfiliadoPolicy();

        $this->assertTrue($policy->viewLaboral(
            $user,
            new Afiliado(['colegio_id' => 11, 'estado' => 'ACTIVO'])
        ));
        $this->assertFalse($policy->viewLaboral(
            $user,
            new Afiliado(['colegio_id' => 12, 'estado' => 'ACTIVO'])
        ));
    }

    public function test_payment_policy_covers_store_update_and_observation(): void
    {
        $policy = new PagoPolicy();
        $affiliate = new Afiliado(['colegio_id' => 10, 'estado' => 'ACTIVO']);
        $admin = $this->user('ADMIN', 99);
        $superAdmin = $this->user('SUPER ADMIN', 99);
        $localUser = $this->user('USUARIO', 10);
        $otherUser = $this->user('USUARIO', 11);

        $this->assertTrue($policy->store($admin, $affiliate));
        $this->assertTrue($policy->store($localUser, $affiliate));
        $this->assertFalse($policy->store($otherUser, $affiliate));
        $this->assertFalse($policy->store($superAdmin, $affiliate));

        $openBatch = new LoteVerificacion(['estado' => 'ABIERTO', 'colegio_id' => 10]);
        $closedBatch = new LoteVerificacion(['estado' => 'ENVIADO', 'colegio_id' => 10]);
        $payment = new Pago(['estado' => 'EN_LOTE']);
        $payment->setRelation('loteVerificacion', $openBatch);
        $this->assertTrue($policy->update($localUser, $payment));
        $this->assertFalse($policy->update($admin, $payment));
        $this->assertFalse($policy->update($otherUser, $payment));
        $payment->setRelation('loteVerificacion', $closedBatch);
        $this->assertFalse($policy->update($localUser, $payment));

        $reviewBatch = new LoteVerificacion([
            'estado' => 'EN_VERIFICACION',
            'colegio_id' => 10,
        ]);
        $payment->estado = 'EN_LOTE';
        $payment->setRelation('loteVerificacion', $reviewBatch);
        $this->assertTrue($policy->observar($admin, $payment));
        $this->assertFalse($policy->observar($localUser, $payment));
        $payment->estado = 'VERIFICADO';
        $this->assertFalse($policy->observar($admin, $payment));
    }

    public function test_batch_policy_enforces_state_and_role_for_each_transition(): void
    {
        $policy = new LoteVerificacionPolicy();
        $user = $this->user('USUARIO', 10);
        $otherUser = $this->user('USUARIO', 11);
        $admin = $this->user('ADMIN', 10);
        $superAdmin = $this->user('SUPER ADMIN', 10);
        $open = new LoteVerificacion(['estado' => 'ABIERTO', 'colegio_id' => 10]);
        $sent = new LoteVerificacion(['estado' => 'ENVIADO', 'colegio_id' => 10]);
        $reviewing = new LoteVerificacion([
            'estado' => 'EN_VERIFICACION',
            'colegio_id' => 10,
        ]);

        $this->assertTrue($policy->viewAny($user));
        $this->assertTrue($policy->viewAny($admin));
        $this->assertTrue($policy->viewAny($superAdmin));
        $this->assertFalse($policy->viewAny($this->user('OTRO', 10)));
        $this->assertTrue($policy->create($user));
        $this->assertFalse($policy->create($admin));

        $this->assertTrue($policy->cerrar($user, $open));
        $this->assertFalse($policy->cerrar($otherUser, $open));
        $this->assertFalse($policy->cerrar($admin, $open));
        $this->assertFalse($policy->cerrar($user, $sent));

        $this->assertTrue($policy->revisar($admin, $sent));
        $this->assertFalse($policy->revisar($admin, $open));
        $this->assertTrue($policy->previewAportes($admin, $reviewing));
        $this->assertTrue($policy->confirmarAportes($admin, $reviewing));
        $this->assertFalse($policy->confirmarAportes($user, $reviewing));
        $this->assertTrue($policy->reabrir($superAdmin, $sent));
        $this->assertFalse($policy->reabrir($superAdmin, $reviewing));
        $this->assertFalse($policy->reabrir($admin, $sent));
    }

    private function user(string $role, int $collegeId): Usuario
    {
        $user = new Usuario();
        $user->colegio_id = $collegeId;
        $user->setRelation('rol', new Rol(['nombre' => $role]));
        $user->setRelation('colegio', new Colegio([
            'tipo' => 'REGIONAL',
        ]));

        return $user;
    }
}
