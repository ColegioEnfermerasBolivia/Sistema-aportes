<?php

namespace Database\Factories;

use App\Models\Pago;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pago>
 */
class PagoFactory extends Factory
{
    protected $model = Pago::class;

    public function definition(): array
    {
        return [
            'afiliado_id' => AfiliadoFactory::new(),
            'lote_verificacion_id' => LoteVerificacionFactory::new(),
            'monto_total' => '100.00',
            'monto_mensual' => '100.00',
            'cantidad_periodos' => 1,
            'estado' => 'EN_LOTE',
            'observaciones' => null,
            'registrado_por' => UsuarioFactory::new(),
            'verificado_por' => null,
            'fecha_verificacion' => null,
        ];
    }
}
