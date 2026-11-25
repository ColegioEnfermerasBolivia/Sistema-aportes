<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Genera pagos EN_LOTE para pruebas de carga, sin saldos y con
 * monto_total = monto_mensual * cantidad_periodos (divisibles exactos).
 *
 *   php artisan carga:pagos 2 2000
 *   php artisan carga:pagos 2 --limpiar --desde-id=25
 *
 * Solo para desarrollo: se niega a correr en producción.
 */
class GenerarPagosDeCarga extends Command
{
    protected $signature = 'carga:pagos
        {lote : ID de un lote existente donde se insertan los pagos}
        {cantidad=1000 : Cantidad de pagos a generar}
        {--usuario= : ID del usuario que figura como registrado_por}
        {--max-periodos=12 : Máximo de períodos por pago}
        {--cualquier-colegio : No limitar los afiliados al colegio del lote}
        {--limpiar : Borra los pagos EN_LOTE del lote con id >= --desde-id}
        {--desde-id= : Primer id a borrar cuando se usa --limpiar}';

    protected $description = 'Genera (o limpia) pagos EN_LOTE para pruebas de carga';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Este comando no puede ejecutarse en producción.');

            return self::FAILURE;
        }

        $lote = DB::table('lote_verificacion')->where('id', $this->argument('lote'))->first();

        if (! $lote) {
            $this->error('El lote indicado no existe.');

            return self::FAILURE;
        }

        return $this->option('limpiar') ? $this->limpiar($lote) : $this->generar($lote);
    }

    private function generar(object $lote): int
    {
        $cantidad = (int) $this->argument('cantidad');
        $maxPeriodos = max(1, (int) $this->option('max-periodos'));

        if ($cantidad < 1) {
            $this->error('La cantidad debe ser al menos 1.');

            return self::FAILURE;
        }

        $usuarioId = $this->option('usuario')
            ?: DB::table('pagos')->orderByDesc('id')->value('registrado_por');

        if (! $usuarioId || ! DB::table('usuarios')->where('id', $usuarioId)->exists()) {
            $this->error('Indica un usuario válido con --usuario=ID.');

            return self::FAILURE;
        }

        // Afiliados activos, sin saldos activos y sin pago previo en este lote.
        $elegibles = DB::table('afiliados')
            ->where('estado', 'ACTIVO')
            ->whereNotNull('ci')
            ->where('ci', '<>', '')
            ->whereNotNull('registro_departamental')
            ->where('registro_departamental', '<>', '')
            ->whereNotNull('matricula_profesional')
            ->where('matricula_profesional', '<>', '')
            ->when(
                ! $this->option('cualquier-colegio'),
                fn ($q) => $q->where('colegio_id', $lote->colegio_id)
            )
            ->whereNotExists(
                fn ($q) => $q->select(DB::raw(1))
                    ->from('saldos')
                    ->whereColumn('saldos.afiliado_id', 'afiliados.id')
                    ->where('saldos.estado', 'ACTIVO')
                    ->where('saldos.monto_disponible', '>', 0)
            )
            ->whereNotExists(
                fn ($q) => $q->select(DB::raw(1))
                    ->from('pagos')
                    ->whereColumn('pagos.afiliado_id', 'afiliados.id')
                    ->where('pagos.lote_verificacion_id', $lote->id)
            )
            ->pluck('id')
            ->all();

        if (count($elegibles) < $cantidad) {
            $this->error(
                'Solo hay '.count($elegibles)." afiliados elegibles y pediste {$cantidad}. "
                .'Reduce la cantidad o usa --cualquier-colegio.'
            );

            return self::FAILURE;
        }

        shuffle($elegibles);
        $elegidos = array_slice($elegibles, 0, $cantidad);

        $idAntes = (int) (DB::table('pagos')->max('id') ?? 0);
        $ahora = now()->format('Y-m-d H:i:s');
        $sumaTotalCentavos = 0;
        $sumaPeriodos = 0;

        DB::disableQueryLog();

        DB::transaction(function () use (
            $elegidos, $lote, $usuarioId, $maxPeriodos, $ahora, &$sumaTotalCentavos, &$sumaPeriodos
        ) {
            foreach (array_chunk($elegidos, 1000) as $bloque) {
                $filas = [];

                foreach ($bloque as $afiliadoId) {
                    $mensualCentavos = random_int(1000, 5000);   // 10.00 a 50.00
                    $periodos = random_int(1, $maxPeriodos);
                    $totalCentavos = $mensualCentavos * $periodos; // divisible exacto

                    $sumaTotalCentavos += $totalCentavos;
                    $sumaPeriodos += $periodos;

                    $filas[] = [
                        'afiliado_id' => $afiliadoId,
                        'lote_verificacion_id' => $lote->id,
                        'monto_total' => number_format($totalCentavos / 100, 2, '.', ''),
                        'monto_mensual' => number_format($mensualCentavos / 100, 2, '.', ''),
                        'cantidad_periodos' => $periodos,
                        'estado' => 'EN_LOTE',
                        'observaciones' => null,
                        'registrado_por' => $usuarioId,
                        'verificado_por' => null,
                        'fecha_verificacion' => null,
                        'creado_en' => $ahora,
                        'actualizado_en' => $ahora,
                    ];
                }

                DB::table('pagos')->insert($filas);
            }

            // VERIFICA contra PagoController::store que el lote se actualiza igual.
            DB::table('lote_verificacion')->where('id', $lote->id)->update([
                'cantidad_aportes' => DB::raw('cantidad_aportes + '.(int) $sumaPeriodos),
                'monto_total' => DB::raw(
                    'monto_total + '.number_format($sumaTotalCentavos / 100, 2, '.', '')
                ),
            ]);
        });

        $this->info("Pagos insertados: {$cantidad} en el lote {$lote->id}.");
        $this->line('Ids nuevos: mayores a '.$idAntes.' (limpiar con --desde-id='.($idAntes + 1).').');
        $this->line('Períodos totales: '.$sumaPeriodos
            .' | Monto total: '.number_format($sumaTotalCentavos / 100, 2));

        return self::SUCCESS;
    }

    private function limpiar(object $lote): int
    {
        $desde = (int) $this->option('desde-id');

        if ($desde < 1) {
            $this->error('Indica desde qué id borrar con --desde-id=N.');

            return self::FAILURE;
        }

        $consulta = DB::table('pagos')
            ->where('lote_verificacion_id', $lote->id)
            ->where('estado', 'EN_LOTE')
            ->where('id', '>=', $desde);

        $resumen = (clone $consulta)
            ->selectRaw('COUNT(*) AS c, COALESCE(SUM(cantidad_periodos), 0) AS p, COALESCE(SUM(monto_total), 0) AS t')
            ->first();

        if ((int) $resumen->c === 0) {
            $this->info('No hay pagos que limpiar.');

            return self::SUCCESS;
        }

        if (! $this->confirm("Se borrarán {$resumen->c} pagos EN_LOTE del lote {$lote->id} (id >= {$desde}). ¿Continuar?")) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($consulta, $lote, $resumen) {
            $consulta->delete();

            DB::table('lote_verificacion')->where('id', $lote->id)->update([
                'cantidad_aportes' => DB::raw('GREATEST(cantidad_aportes - '.(int) $resumen->p.', 0)'),
                'monto_total' => DB::raw(
                    'GREATEST(monto_total - '.number_format((float) $resumen->t, 2, '.', '').', 0)'
                ),
            ]);
        });

        $this->info("Pagos borrados: {$resumen->c}.");

        return self::SUCCESS;
    }
}