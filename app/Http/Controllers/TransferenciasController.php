<?php

namespace App\Http\Controllers;

use App\Models\Afiliado;
use App\Models\TransferenciaAfiliado;
use App\Services\AuditoriaService;
use App\Services\ImportadorTransferenciasHistoricas;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class TransferenciasController extends Controller
{
    private const SESION_FILAS = 'filas_revision_transferencias';
    private const SESION_RUTA = 'transferencias_importacion.ruta';

    public function __construct(
        private ImportadorTransferenciasHistoricas $importador,
        private AuditoriaService $auditoria,
    ) {
    }

    /**
     * Analiza el CSV sin modificar la base de datos.
     */
    public function analizar(Request $request): RedirectResponse
    {
        $this->autorizarAcceso();

        $request->validate([
            'archivo' => ['required', 'file', 'mimes:csv,txt', 'max:51200'],
        ], [
            'archivo.required' => 'Selecciona un archivo CSV.',
            'archivo.file' => 'El archivo seleccionado no es válido.',
            'archivo.mimes' => 'El archivo debe tener formato CSV o TXT.',
            'archivo.max' => 'El archivo no puede superar los 50 MB.',
        ]);

        $this->limpiarRevision();

        $ruta = $request->file('archivo')->store(
            'transferencias-temporales',
            'local'
        );

        try {
            $resultado = $this->importador->analizar(
                Storage::disk('local')->path($ruta)
            );

            if (empty($resultado['filas'])) {
                Storage::disk('local')->delete($ruta);

                return back()->withErrors([
                    'archivo' => 'El archivo no contiene transferencias para revisar.',
                ]);
            }

            $filas = $this->prepararFilas($resultado['filas']);
            $filas = $this->validarSecuencias($filas);

            session([
                self::SESION_RUTA => $ruta,
                self::SESION_FILAS => $filas,
                'mostrar_revision_transferencias' => true,
            ]);

            return back()->with(
                'success',
                'Archivo analizado. Revisa los resultados antes de importar.'
            );
        } catch (Throwable $e) {
            Storage::disk('local')->delete($ruta);

            report($e);

            return back()->withErrors([
                'archivo' => 'No se pudo analizar el CSV. Verifica su estructura y vuelve a intentarlo.',
            ]);
        }
    }

    /**
     * Revalida una fila corregida desde el modal.
     */
    public function validarFila(Request $request): JsonResponse
    {
        $this->autorizarAcceso();

        $datos = $request->validate([
            'clave' => ['required', 'string'],
            'ci' => ['required', 'string', 'max:50'],
            'colegio_origen' => ['required', 'string', 'max:255'],
            'colegio_destino' => ['required', 'string', 'max:255'],
            'fecha_transferencia' => ['required', 'string', 'max:30'],
        ]);

        $filas = session(self::SESION_FILAS, []);
        $ruta = session(self::SESION_RUTA);

        if (!$ruta || !Storage::disk('local')->exists($ruta) || !$filas) {
            return response()->json([
                'message' => 'La revisión ha expirado. Sube nuevamente el archivo.',
            ], 422);
        }

        $indice = null;

        foreach ($filas as $i => $fila) {
            if (($fila['clave'] ?? null) === $datos['clave']) {
                $indice = $i;
                break;
            }
        }

        if ($indice === null) {
            return response()->json([
                'message' => 'No se encontró la fila que intentas corregir.',
            ], 404);
        }

        try {
            // Actualiza la fila en memoria y vuelve a analizar el CSV completo.
            $filas[$indice]['ci'] = trim($datos['ci']);
            $filas[$indice]['colegio_origen'] = trim($datos['colegio_origen']);
            $filas[$indice]['colegio_destino'] = trim($datos['colegio_destino']);
            $filas[$indice]['fecha_transferencia'] = trim($datos['fecha_transferencia']);

            $this->escribirCsv($ruta, $filas);

            $resultado = $this->importador->analizar(
                Storage::disk('local')->path($ruta)
            );

            $filasActualizadas = $this->prepararFilas(
                $resultado['filas']
            );

            $filasActualizadas = $this->validarSecuencias(
                $filasActualizadas
            );

            session([
                self::SESION_FILAS => $filasActualizadas,
                'mostrar_revision_transferencias' => true,
            ]);

            $filaActualizada = collect($filasActualizadas)->first(
                fn (array $fila) => $fila['clave'] === $datos['clave']
            );

            if (!$filaActualizada) {
                return response()->json([
                    'message' => 'No se pudo recuperar la fila corregida. Vuelve a analizar el archivo.',
                ], 422);
            }

            return response()->json([
                'message' => 'Fila validada.',
                'fila' => $filaActualizada,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo validar la corrección. Revisa los datos e inténtalo nuevamente.',
            ], 500);
        }
    }

    /**
     * Confirma la importación. Solo admite filas sin errores.
     */
    public function confirmar(): JsonResponse
    {
        $this->autorizarAcceso();

        $filas = session(self::SESION_FILAS, []);
        $ruta = session(self::SESION_RUTA);

        if (!$ruta || !Storage::disk('local')->exists($ruta) || !$filas) {
            return response()->json([
                'message' => 'La revisión ha expirado. Sube nuevamente el archivo.',
            ], 422);
        }

        // No confiamos en los estados que estén guardados en la sesión.
        try {
            $resultado = $this->importador->analizar(
                Storage::disk('local')->path($ruta)
            );

            $filas = $this->prepararFilas($resultado['filas']);
            $filas = $this->validarSecuencias($filas);

            session([self::SESION_FILAS => $filas]);

            $errores = array_filter(
                $filas,
                fn (array $fila) => $fila['estado'] !== 'LISTO'
            );

            if ($errores) {
                return response()->json([
                    'message' => 'Hay transferencias con errores o inconsistencias. Corrígelas antes de importar.',
                ], 422);
            }

            $importadas = DB::transaction(function () use ($filas) {
                $cantidad = 0;
                $ultimosDestinos = [];

                foreach ($filas as $fila) {
                    $afiliadoId = (int) $fila['afiliado_id'];

                    // Bloqueamos el afiliado durante la importación.
                    $afiliado = Afiliado::query()
                        ->lockForUpdate()
                        ->findOrFail($afiliadoId);

                    $origenId = (int) $fila['colegio_id_origen'];
                    $destinoId = (int) $fila['colegio_id_destino'];

                    // Protección adicional ante una transferencia sin cambio.
                    if ($origenId === $destinoId) {
                        throw new \RuntimeException(
                            "La transferencia de la fila {$fila['numero_fila']} tiene el mismo origen y destino."
                        );
                    }

                    if (empty($fila['fecha_normalizada'])) {
                        throw new \RuntimeException(
                            "La transferencia de la fila {$fila['numero_fila']} "
                            . "no tiene una fecha de transferencia válida."
                        );
                    }

                    $transferencia = TransferenciaAfiliado::create([
                        'afiliado_id' => $afiliadoId,
                        'colegio_id_origen' => $origenId,
                        'colegio_id_destino' => $destinoId,
                        'fecha_transferencia' => $fila['fecha_normalizada'],
                    ]);

                    $this->auditoria->registrar(
                        (int) auth()->id(),
                        'IMPORTAR',
                        'transferencias_afiliados',
                        (int) $transferencia->id,
                        null,
                        [
                            'afiliado_id' => $afiliadoId,
                            'colegio_id_origen' => $origenId,
                            'colegio_id_destino' => $destinoId,
                            'fecha_transferencia' => $fila['fecha_normalizada'],
                            'origen_importacion' => 'CSV historico',
                        ]
                    );

                    $fechaActual = $fila['fecha_normalizada'];

                    if (
                        !isset($ultimosDestinos[$afiliadoId])
                        || $fechaActual > $ultimosDestinos[$afiliadoId]['fecha']
                    ) {
                        $ultimosDestinos[$afiliadoId] = [
                            'colegio_id' => $destinoId,
                            'fecha' => $fechaActual,
                        ];
                    }

                    $cantidad++;
                }

                // El colegio actual se actualiza una sola vez por afiliado,
                // usando el destino de la transferencia histórica más reciente.
                foreach ($ultimosDestinos as $afiliadoId => $ultimo) {
                    $afiliado = Afiliado::query()
                        ->lockForUpdate()
                        ->findOrFail($afiliadoId);

                    $colegioAnterior = (int) $afiliado->colegio_id;
                    $colegioNuevo = (int) $ultimo['colegio_id'];

                    if ($colegioAnterior === $colegioNuevo) {
                        continue;
                    }

                    $afiliado->colegio_id = $colegioNuevo;
                    $afiliado->save();

                    $this->auditoria->registrar(
                        (int) auth()->id(),
                        'ACTUALIZAR',
                        'afiliados',
                        (int) $afiliado->id,
                        ['colegio_id' => $colegioAnterior],
                        ['colegio_id' => $colegioNuevo]
                    );
                }

                return $cantidad;
            });

            $this->limpiarRevision();

            return response()->json([
                'message' => "Se importaron {$importadas} transferencias correctamente.",
                'importadas' => $importadas,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'No se completó la importación. Se revirtieron las operaciones de base de datos.',
            ], 500);
        }
    }

    /**
     * Cancela y limpia la revisión pendiente.
     */
    public function cancelar(): RedirectResponse
    {
        $this->autorizarAcceso();
        $this->limpiarRevision();

        return back()->with('success', 'Se canceló la revisión de transferencias.');
    }

    /**
     * Normaliza la estructura del servicio para el modal.
     */
    private function prepararFilas(array $filas): array
    {
        $resultado = [];

        foreach ($filas as $indice => $fila) {
            $errores = $fila['errores'] ?? [];

            if (is_string($errores)) {
                $errores = [$errores];
            }

            // El importador conserva la fecha original en fecha_original.
            $fechaOriginal = (string) (
                $fila['fecha_original']
                ?? $fila['fecha']
                ?? $fila['fecha_transferencia']
                ?? ''
            );

            // El importador devuelve la fecha normalizada en fecha_transferencia.
            $fechaNormalizada = $fila['fecha_normalizada']
                ?? $fila['fecha_transferencia_normalizada']
                ?? $fila['fecha_transferencia']
                ?? null;

            $resultado[] = array_merge($fila, [
                'clave' => (string) (
                    $fila['clave']
                    ?? sha1('transferencia-' . ($fila['numero_fila'] ?? ($indice + 2)))
                ),
                'numero_fila' => $fila['numero_fila']
                    ?? $fila['fila']
                    ?? ($indice + 2),
                'ci' => (string) ($fila['ci'] ?? ''),
                'colegio_origen' => (string) ($fila['colegio_origen'] ?? ''),
                'colegio_destino' => (string) ($fila['colegio_destino'] ?? ''),
                'fecha_transferencia' => $fechaOriginal,
                'fecha_normalizada' => $fechaNormalizada,
                'estado' => ($fila['estado'] ?? 'ERROR') === 'LISTO'
                    ? 'LISTO'
                    : 'ERROR',
                'errores' => array_values($errores),
                'editando' => false,
            ]);
        }

        return $resultado;
    }

    /**
     * Verifica que las transferencias de cada afiliado formen
     * una secuencia cronológica coherente.
     */
    private function validarSecuencias(array $filas): array
    {
        $grupos = [];

        foreach ($filas as $indice => $fila) {
            if (
                ($fila['estado'] ?? null) !== 'LISTO'
                || empty($fila['afiliado_id'])
                || empty($fila['fecha_normalizada'])
            ) {
                continue;
            }

            $grupos[$fila['afiliado_id']][] = $indice;
        }

        foreach ($grupos as $indices) {
            usort($indices, function ($a, $b) use ($filas) {
                return strcmp(
                    $filas[$a]['fecha_normalizada'],
                    $filas[$b]['fecha_normalizada']
                );
            });

            $destinoAnterior = null;
            $fechaAnterior = null;

            foreach ($indices as $indice) {
                $fila = $filas[$indice];
                $origen = (int) ($fila['colegio_id_origen'] ?? 0);
                $destino = (int) ($fila['colegio_id_destino'] ?? 0);
                $fecha = $fila['fecha_normalizada'];

                if ($origen === $destino) {
                    $filas[$indice]['estado'] = 'ERROR';
                    $filas[$indice]['errores'][] =
                        'El colegio de origen y destino son iguales.';
                    continue;
                }

                if ($fechaAnterior !== null && $fecha < $fechaAnterior) {
                    $filas[$indice]['estado'] = 'ERROR';
                    $filas[$indice]['errores'][] =
                        'La fecha no respeta el orden cronológico.';
                    continue;
                }

                if (
                    $destinoAnterior !== null
                    && $origen !== $destinoAnterior
                ) {
                    $filas[$indice]['estado'] = 'ERROR';
                    $filas[$indice]['errores'][] =
                        'El colegio de origen no coincide con el destino de la transferencia anterior.';
                    continue;
                }

                $destinoAnterior = $destino;
                $fechaAnterior = $fecha;
            }
        }

        return $filas;
    }

    /**
     * Regenera el CSV con las correcciones del modal.
     */
    private function escribirCsv(string $ruta, array $filas): void
    {
        $archivo = fopen(Storage::disk('local')->path($ruta), 'w');

        if ($archivo === false) {
            throw new \RuntimeException('No se pudo escribir el CSV temporal.');
        }

        try {
            fputcsv($archivo, [
                'ci',
                'colegio_origen',
                'colegio_destino',
                'fecha_transferencia',
            ]);

            foreach ($filas as $fila) {
                fputcsv($archivo, [
                    $fila['ci'] ?? '',
                    $fila['colegio_origen'] ?? '',
                    $fila['colegio_destino'] ?? '',
                    $fila['fecha_transferencia'] ?? '',
                ]);
            }
        } finally {
            fclose($archivo);
        }
    }

    private function limpiarRevision(): void
    {
        $ruta = session(self::SESION_RUTA);

        if ($ruta && Storage::disk('local')->exists($ruta)) {
            Storage::disk('local')->delete($ruta);
        }

        session()->forget([
            self::SESION_RUTA,
            self::SESION_FILAS,
            'mostrar_revision_transferencias',
        ]);
    }

    private function autorizarAcceso(): void
    {
        abort_unless(
            auth()->check()
            && in_array(
                auth()->user()->rol?->nombre,
                ['ADMIN', 'SUPER ADMIN'],
                true
            ),
            403,
            'No tienes permiso para gestionar transferencias.'
        );
    }
}
