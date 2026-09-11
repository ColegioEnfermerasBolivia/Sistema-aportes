<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Aporte;
use App\Models\LoteVerificacion;
use App\Models\Pago;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;

class LoteVerificacionController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', LoteVerificacion::class);

        $usuario = Auth::user();

        $consulta = LoteVerificacion::with([
            'colegio',
            'pagos' => function ($query) {
                $query
                    ->where('estado', 'EN_LOTE')
                    ->with('afiliado');
            },
        ]);

        /*
        * ADMIN y SUPER ADMIN pueden consultar todos los lotes.
        */
        if (in_array($usuario->rol?->nombre, [
            'SUPER ADMIN',
            'ADMIN',
        ])) {

            // No agregamos ningún filtro de colegio.

        /*
        * USUARIO solamente puede consultar
        * los lotes de su propio colegio.
        */
        } elseif ($usuario->rol?->nombre === 'USUARIO') {

            $consulta->where(
                'colegio_id',
                $usuario->colegio_id
            );
        }

        $lotes = $consulta
            ->orderByDesc('year')
            ->orderByDesc('mes')
            ->paginate(10);

        return view(
            'aportes.lotes.index',
            compact('lotes')
        );
    }

    public function registrarAportes(LoteVerificacion $lote)
    {
        $usuario = Auth::user();

        /*Validar rol*/

        if ($usuario->rol_id !== 2) {
            return back()
                ->withErrors([
                    'lote' => 'No tienes permiso para registrar aportes.',
                ]);
        }

        /*Validar estado del lote*/

        if ($lote->estado !== 'EN_VERIFICACION') {
            return back()
                ->withErrors([
                    'lote' => 'El lote debe encontrarse en estado EN_VERIFICACION para registrar los aportes.',
                ]);
        }

        DB::transaction(function () use ($lote, $usuario) {

            $pagos = $lote->pagos()
                ->where('estado', 'VERIFICADO')
                ->with('afiliado')
                ->get();

            foreach ($pagos as $pago) {

                /*Evitar procesar nuevamente un pago*/

                if ($pago->aportes()->exists()) {
                    continue;
                }

                $afiliado = $pago->afiliado;

                /*Determinar primer período a registrar*/

                $ultimoAporte = $afiliado->aportes()
                    ->orderByDesc('year')
                    ->orderByDesc('mes')
                    ->first();

                if ($ultimoAporte) {

                    $fechaInicio = Carbon::create(
                        $ultimoAporte->year,
                        $ultimoAporte->mes,
                        1
                    )->addMonth();

                } else {

                    $fechaInicio = Carbon::parse(
                        $afiliado->fecha_afiliacion
                    )->startOfMonth();
                }

                /*Calcular monto por período*/

                $montoPorPeriodo = $pago->monto_total / $pago->cantidad_periodos;

                /*Generar aportes*/

                for ($i = 0; $i < $pago->cantidad_periodos; $i++) {

                    $fechaPeriodo = $fechaInicio->copy()->addMonths($i);

                    Aporte::create([
                        'afiliado_id' => $afiliado->id,
                        'colegio_id' => $afiliado->colegio_id,
                        'pago_id' => $pago->id,
                        'mes' => $fechaPeriodo->month,
                        'year' => $fechaPeriodo->year,
                        'monto' => $montoPorPeriodo,
                        'estado' => 'VERIFICADO',
                        'observaciones' => null,
                        'registrado_por' => $pago->registrado_por,
                        'fecha_registro' => now(),
                        'verificado_por' => $usuario->id,
                        'fecha_verificacion' => now(),
                    ]);
                }
            }
        });

        return back()->with(
            'success',
            'Los aportes del lote fueron registrados correctamente.'
        );
    }

    public function revisar(LoteVerificacion $lote)
    {
        Gate::authorize('revisar', $lote);

        if (!in_array($lote->estado, [
            'ENVIADO',
            'EN_VERIFICACION'
        ])) {
            return back()
                ->withErrors([
                    'lote' => 'Este lote no está disponible para revisión.',
                ]);
        }

        if ($lote->estado === 'ENVIADO') {
            $lote->update([
                'estado' => 'EN_VERIFICACION',
                'fecha_recepcion_col_nacional' => now(),
            ]);
        }

        return redirect()
            ->route('lotes.index')
            ->with('lote_revisar', $lote->id);
    }

    public function show(LoteVerificacion $lote)
    {
        Gate::authorize('view', $lote);
        $lote->load([
            'colegio',
            'pagos.afiliado',
        ]);

        return view('aportes.lotes.show', compact('lote'));
    }

    public function previsualizarAportes(
        Request $request,
        LoteVerificacion $lote
    ) {
        $usuario = Auth::user();

        // Validar rol
        if ($usuario->rol_id !== 2) {
            return response()->json([
                'message' => 'No tienes permiso para registrar aportes.',
            ], 403);
        }

        // Validar estado del lote
        if ($lote->estado !== 'EN_VERIFICACION') {
            return response()->json([
                'message' => 'El lote debe encontrarse en estado EN_VERIFICACION.',
            ], 422);
        }

        // Validar pagos seleccionados
        $datos = $request->validate([
            'pago_ids' => ['required', 'array', 'min:1'],
            'pago_ids.*' => ['required', 'integer', 'distinct'],
        ]);

        /*
        * Obtener únicamente los pagos:
        * - pertenecientes a este lote
        * - que continúen EN_LOTE
        * - que hayan sido seleccionados
        */
        $pagos = $lote->pagos()
            ->where('estado', 'EN_LOTE')
            ->whereIn('id', $datos['pago_ids'])
            ->with('afiliado')
            ->get();

        /*
        * Si alguno de los pagos seleccionados ya no está
        * disponible, no continuamos.
        */
        if ($pagos->count() !== count($datos['pago_ids'])) {
            return response()->json([
                'message' => 'Uno o más pagos seleccionados ya no están disponibles para revisión.',
            ], 422);
        }

        $aportesPreview = [];

        foreach ($pagos as $pago) {

            $afiliado = $pago->afiliado;

            /*
            * Buscar el último aporte oficial del afiliado.
            */
            $ultimoAporte = $afiliado->aportes()
                ->orderByDesc('year')
                ->orderByDesc('mes')
                ->first();

            /*
            * Determinar el primer período que corresponde.
            */
            if ($ultimoAporte) {

                $fechaInicio = Carbon::create(
                    $ultimoAporte->year,
                    $ultimoAporte->mes,
                    1
                )->addMonth();

            } else {

                $fechaInicio = Carbon::parse(
                    $afiliado->fecha_afiliacion
                )->startOfMonth();
            }

            /*
            * El pago ya fue validado al momento de registrarse,
            * por lo que cantidad_periodos representa exactamente
            * cuántos aportes debemos generar.
            */
            $montoPorPeriodo =
                $pago->monto_total / $pago->cantidad_periodos;

            /*
            * Generar únicamente una previsualización.
            *
            * Todavía NO hacemos Aporte::create().
            */
            for ($i = 0; $i < $pago->cantidad_periodos; $i++) {

                $fechaPeriodo = $fechaInicio
                    ->copy()
                    ->addMonths($i);

                $aportesPreview[] = [
                    'pago_id' => $pago->id,
                    'afiliado_id' => $afiliado->id,
                    'nombre' => $afiliado->nombres . ' ' . $afiliado->apellidos,
                    'ci' => $afiliado->ci,
                    'mes' => $fechaPeriodo->month,
                    'year' => $fechaPeriodo->year,
                    'monto' => number_format(
                        $montoPorPeriodo,
                        2,
                        '.',
                        ''
                    ),
                ];
            }
        }

        return response()->json([
            'aportes' => $aportesPreview,
        ]);
    }

    public function previewAportes(Request $request, LoteVerificacion $lote)
    {
        $usuario = Auth::user();

        /*Validar rol*/

        if ($usuario->rol_id !== 2) {
            return response()->json([
                'message' => 'No tienes permiso para previsualizar aportes.',
            ], 403);
        }

        /*Validar estado del lote*/

        if ($lote->estado !== 'EN_VERIFICACION') {
            return response()->json([
                'message' => 'El lote debe encontrarse en estado EN_VERIFICACION.',
            ], 422);
        }

        /*Validar pagos seleccionados*/

        $datos = $request->validate([
            'pago_ids' => ['required', 'array', 'min:1'],
            'pago_ids.*' => ['required', 'integer', 'distinct'],
        ]);

        /*Obtener únicamente pagos válidos del lote*/

        $pagos = $lote->pagos()
            ->whereIn('id', $datos['pago_ids'])
            ->where('estado', 'EN_LOTE')
            ->with('afiliado')
            ->get();

        /*Verificar que todos los pagos enviados sean válidos */

        if ($pagos->count() !== count($datos['pago_ids'])) {
            return response()->json([
                'message' => 'Uno o más pagos seleccionados ya no están disponibles para procesar.',
            ], 422);
        }

        /*Generar previsualización*/

        $aportesPreview = [];

        foreach ($pagos as $pago) {

            $afiliado = $pago->afiliado;

            /*
            | Determinar primer período pendiente
            */

            $ultimoAporte = $afiliado->aportes()
                ->orderByDesc('year')
                ->orderByDesc('mes')
                ->first();

            if ($ultimoAporte) {

                $fechaInicio = Carbon::create(
                    $ultimoAporte->year,
                    $ultimoAporte->mes,
                    1
                )->addMonth();

            } else {

                $fechaInicio = Carbon::parse(
                    $afiliado->fecha_afiliacion
                )->startOfMonth();
            }

            /*Calcular monto por período*/

            $montoPorPeriodo = $pago->monto_total / $pago->cantidad_periodos;

            /*Generar filas de previsualización*/

            for ($i = 0; $i < $pago->cantidad_periodos; $i++) {

                $fechaPeriodo = $fechaInicio->copy()->addMonths($i);

                $aportesPreview[] = [
                    'pago_id' => $pago->id,
                    'afiliado_id' => $afiliado->id,
                    'nombre' => $afiliado->nombres . ' ' . $afiliado->apellidos,
                    'ci' => $afiliado->ci,
                    'mes' => $fechaPeriodo->month,
                    'year' => $fechaPeriodo->year,
                    'monto' => number_format($montoPorPeriodo, 2, '.', ''),
                    'seleccionado' => true,
                ];
            }
        }

        return response()->json([
            'aportes' => $aportesPreview,
        ]);
    }

    public function confirmarAportes(Request $request, LoteVerificacion $lote)
    {
        $usuario = Auth::user();

        // 1. Solo ADMIN puede confirmar aportes
        if ($usuario->rol_id !== 2) {
            return response()->json([
                'message' => 'No tienes permiso para confirmar aportes.',
            ], 403);
        }

        // 2. El lote debe estar en proceso de verificación
        if ($lote->estado !== 'EN_VERIFICACION') {
            return response()->json([
                'message' => 'El lote no se encuentra disponible para verificación.',
            ], 422);
        }

        // 3. Validamos únicamente los IDs de pagos
        $datos = $request->validate([
            'pago_ids' => ['required', 'array', 'min:1'],
            'pago_ids.*' => ['required', 'integer', 'distinct'],

            'pagos_observados' => ['nullable', 'array'],
            'pagos_observados.*' => ['required', 'integer', 'distinct'],
        ]);

        $pagoIds = $datos['pago_ids'];
        $pagosObservadosIds = $datos['pagos_observados'] ?? [];

        // 4. Los pagos observados deben formar parte de los pagos seleccionados
        if (!empty(array_diff($pagosObservadosIds, $pagoIds))) {
            return response()->json([
                'message' => 'Se recibió un pago observado que no pertenece a la selección.',
            ], 422);
        }

        try {

            $resultado = DB::transaction(function () use (
                $lote,
                $usuario,
                $pagoIds,
                $pagosObservadosIds
            ) {

                // 5. Volvemos a consultar los pagos desde la BD.
                // Nunca usamos los datos enviados por Alpine para
                // determinar meses, montos o cantidad de períodos.
                $pagos = $lote->pagos()
                    ->whereIn('id', $pagoIds)
                    ->where('estado', 'EN_LOTE')
                    ->with('afiliado')
                    ->lockForUpdate()
                    ->get();

                // Verificamos que todos sigan disponibles
                if ($pagos->count() !== count($pagoIds)) {
                    throw new \Exception(
                        'Uno o más pagos seleccionados ya no están disponibles para procesar.'
                    );
                }

                $pagosProcesados = 0;
                $pagosObservados = 0;
                $aportesGenerados = 0;

                foreach ($pagos as $pago) {

                    /*PAGO OBSERVADO*/

                    if (in_array($pago->id, $pagosObservadosIds)) {

                        $pago->update([
                            'estado' => 'OBSERVADO',
                            'verificado_por' => $usuario->id,
                            'fecha_verificacion' => now(),
                            'observaciones' =>
                                'Pago observado durante la revisión: uno o más períodos fueron desmarcados para conciliación con el colegio de origen.',
                        ]);

                        $pagosObservados++;

                        continue;
                    }

                    /*PAGO VERIFICADO*/

                    $afiliado = $pago->afiliado;

                    // Buscamos el último aporte oficial del afiliado
                    $ultimoAporte = $afiliado->aportes()
                        ->orderByDesc('year')
                        ->orderByDesc('mes')
                        ->first();

                    if ($ultimoAporte) {

                        $fechaInicio = Carbon::create(
                            $ultimoAporte->year,
                            $ultimoAporte->mes,
                            1
                        )->addMonth();

                    } else {

                        $fechaInicio = Carbon::parse(
                            $afiliado->fecha_afiliacion
                        )->startOfMonth();
                    }

                    // Dividimos usando centavos para evitar errores
                    // de precisión con decimales.
                    $montoTotalCentavos = (int) round(
                        $pago->monto_total * 100
                    );

                    if ($montoTotalCentavos % $pago->cantidad_periodos !== 0) {
                        throw new \Exception(
                            "El pago {$pago->id} no puede dividirse exactamente entre sus períodos."
                        );
                    }

                    $montoPorPeriodoCentavos = intdiv(
                        $montoTotalCentavos,
                        $pago->cantidad_periodos
                    );

                    /*
                    * Generamos exactamente la cantidad de períodos
                    * indicada por el pago.
                    */
                    for ($i = 0; $i < $pago->cantidad_periodos; $i++) {

                        $fechaPeriodo = $fechaInicio
                            ->copy()
                            ->addMonths($i);

                        // Evitamos duplicar un período oficial
                        $existe = Aporte::where('afiliado_id', $afiliado->id)
                            ->where('mes', $fechaPeriodo->month)
                            ->where('year', $fechaPeriodo->year)
                            ->exists();

                        if ($existe) {
                            throw new \Exception(
                                "El afiliado {$afiliado->id} ya tiene un aporte registrado para {$fechaPeriodo->month}/{$fechaPeriodo->year}."
                            );
                        }

                        Aporte::create([
                            'afiliado_id' => $afiliado->id,
                            'colegio_id' => $afiliado->colegio_id,
                            'pago_id' => $pago->id,
                            'mes' => $fechaPeriodo->month,
                            'year' => $fechaPeriodo->year,
                            'monto' => $montoPorPeriodoCentavos / 100,
                            'estado' => 'VERIFICADO',
                            'observaciones' => null,
                            'registrado_por' => $pago->registrado_por,
                            'fecha_registro' => now(),
                            'verificado_por' => $usuario->id,
                            'fecha_verificacion' => now(),
                        ]);

                        $aportesGenerados++;
                    }

                    // El pago queda definitivamente verificado
                    $pago->update([
                        'estado' => 'VERIFICADO',
                        'verificado_por' => $usuario->id,
                        'fecha_verificacion' => now(),
                    ]);

                    $pagosProcesados++;
                }

                /*ACTUALIZAR ESTADO DEL LOTE*/
                $pagosPendientes = Pago::where('lote_verificacion_id', $lote->id)
                    ->where('estado', 'EN_LOTE')
                    ->count();

                $loteVerificado = false;

                if ($pagosPendientes === 0) {

                    $lote->update([
                        'estado' => 'VERIFICADO',
                        'verificado_por' => $usuario->id,
                        'fecha_verificacion' => now(),
                    ]);

                    $loteVerificado = true;
                }
                
                return [
                    'pagos_procesados' => $pagosProcesados,
                    'pagos_observados' => $pagosObservados,
                    'aportes_generados' => $aportesGenerados,
                    'lote_verificado' => $loteVerificado,
                ];
            });

            return response()->json([
                'message' => 'La revisión del lote se procesó correctamente.',
                'resultado' => $resultado,
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
