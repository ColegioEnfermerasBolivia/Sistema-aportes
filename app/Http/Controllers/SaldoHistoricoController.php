<?php

namespace App\Http\Controllers;

use App\Models\Afiliado;
use App\Models\Saldo;
use App\Services\Importaciones\CiMatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SaldoHistoricoController extends Controller
{
    public function __construct(
        private CiMatcher $matcher
    ) {}

    /**
     * Pantalla principal de carga de saldos históricos.
     */
    public function index()
    {
        return view('carga-datos.index');
    }

    /**
     * Recibe el CSV y construye el preview.
     */
    public function preview(Request $request)
    {
        $datos = $request->validate([
            'archivo' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:10240',
            ],
        ]);

        $archivo = $datos['archivo'];

        $handle = fopen(
            $archivo->getRealPath(),
            'r'
        );

        if ($handle === false) {
            return back()
                ->withErrors([
                    'archivo' => 'No se pudo abrir el archivo CSV.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Detectar separador del CSV
        |--------------------------------------------------------------------------
        */

        $primeraLinea = fgets($handle);

        if ($primeraLinea === false) {
            return back()->withErrors([
                'archivo' => 'El archivo CSV está vacío.',
            ]);
        }

        $separador = match (true) {
            str_contains($primeraLinea, ';') => ';',
            str_contains($primeraLinea, "\t") => "\t",
            default => ',',
        };

        try {
            /*
             * ---------------------------------------------------------
             * CABECERA
             * ---------------------------------------------------------
             */

            rewind($handle);

            $cabecera = fgetcsv($handle, 0, $separador);

            $cabeceraOriginal = $cabecera;

            $cabecera = array_map(
                fn ($valor) => trim(
                    preg_replace(
                        '/^\xEF\xBB\xBF/',
                        '',
                        (string) $valor
                    )
                ),
                $cabecera ?? []
            );

            $cabeceraNormalizada = array_map(
                fn ($valor) => mb_strtolower($valor),
                $cabecera
            );


            if ($cabeceraNormalizada !== ['ci', 'saldo']) {
                return back()->withErrors([
                    'archivo' =>
                        'El CSV debe contener exactamente las columnas CI y SALDO. Revisa el registro de depuración.',
                ]);
            }

            /*
             * ---------------------------------------------------------
             * ÍNDICE DE AFILIADOS
             * ---------------------------------------------------------
             */

            $indiceAfiliados =
                $this->matcher->construirIndiceAfiliados();

            /*
             * ---------------------------------------------------------
             * PREVIEW
             * ---------------------------------------------------------
             */

            $filas = [];

            $numeroFila = 1;

            while (($fila = fgetcsv(
                $handle, 0, $separador)) !== false) {

                $numeroFila++;

                /*
                 * Ignoramos filas completamente vacías.
                 */

                if (
                    count($fila) === 1 &&
                    trim((string) $fila[0]) === ''
                ) {
                    continue;
                }

                /*
                 * El CSV debe tener exactamente:
                 *
                 * CI
                 * SALDO
                 */

                if (count($fila) !== 2) {
                    $filas[] = [
                        'fila' => $numeroFila,
                        'ci_original' => $fila[0] ?? '',
                        'ci' => null,
                        'saldo' => null,
                        'afiliado_id' => null,
                        'nombre' => null,
                        'estado' => 'ERROR',
                        'mensaje' =>
                            'La fila debe contener exactamente CI y SALDO.',
                    ];

                    continue;
                }

                $ciOriginal = trim(
                    (string) $fila[0]
                );

                $saldoOriginal = trim(
                    (string) $fila[1]
                );

                /*
                 * -----------------------------------------------------
                 * VALIDAR CI
                 * -----------------------------------------------------
                 */

                $ciNormalizado =
                    $this->matcher->normalizarCi(
                        $ciOriginal
                    );

                if ($ciNormalizado === null) {

                    $filas[] = [
                        'fila' => $numeroFila,
                        'ci_original' => $ciOriginal,
                        'ci' => null,
                        'saldo' => null,
                        'afiliado_id' => null,
                        'nombre' => null,
                        'estado' => 'ERROR',
                        'mensaje' => 'El CI no es válido.',
                    ];

                    continue;
                }

                /*
                 * -----------------------------------------------------
                 * VALIDAR SALDO
                 * -----------------------------------------------------
                 */

                $saldo = $this->normalizarMonto(
                    $saldoOriginal
                );

                if ($saldo === null) {

                    $filas[] = [
                        'fila' => $numeroFila,
                        'ci_original' => $ciOriginal,
                        'ci' => $ciNormalizado,
                        'saldo' => null,
                        'afiliado_id' => null,
                        'nombre' => null,
                        'estado' => 'ERROR',
                        'mensaje' =>
                            'El saldo no es un número válido.',
                    ];

                    continue;
                }

                if ($saldo <= 0) {

                    $filas[] = [
                        'fila' => $numeroFila,
                        'ci_original' => $ciOriginal,
                        'ci' => $ciNormalizado,
                        'saldo' => $saldo,
                        'afiliado_id' => null,
                        'nombre' => null,
                        'estado' => 'ERROR',
                        'mensaje' =>
                            'El saldo debe ser mayor que cero.',
                    ];

                    continue;
                }

                /*
                 * -----------------------------------------------------
                 * BUSCAR AFILIADO
                 * -----------------------------------------------------
                 */

                $afiliado =
                    $indiceAfiliados->get(
                        $ciNormalizado
                    );

                if (!$afiliado) {

                    $filas[] = [
                        'fila' => $numeroFila,
                        'ci_original' => $ciOriginal,
                        'ci' => $ciNormalizado,
                        'saldo' => $saldo,
                        'afiliado_id' => null,
                        'nombre' => null,
                        'estado' => 'NO_ENCONTRADO',
                        'mensaje' =>
                            'No se encontró un afiliado con ese CI.',
                    ];

                    continue;
                }

                /*
                 * -----------------------------------------------------
                 * VALIDAR SI YA EXISTE SALDO ACTIVO
                 * -----------------------------------------------------
                 */

                $saldoExistente = Saldo::query()
                    ->where(
                        'afiliado_id',
                        $afiliado->id
                    )
                    ->where(
                        'estado',
                        'ACTIVO'
                    )
                    ->exists();

                if ($saldoExistente) {

                    $filas[] = [
                        'fila' => $numeroFila,
                        'ci_original' => $ciOriginal,
                        'ci' => $ciNormalizado,
                        'saldo' => $saldo,
                        'afiliado_id' => $afiliado->id,
                        'nombre' =>
                            $afiliado->nombres
                            . ' '
                            . $afiliado->apellidos,
                        'estado' => 'YA_EXISTE',
                        'mensaje' =>
                            'El afiliado ya tiene un saldo activo.',
                    ];

                    continue;
                }

                /*
                 * -----------------------------------------------------
                 * FILA CORRECTA
                 * -----------------------------------------------------
                 */

                $filas[] = [
                    'fila' => $numeroFila,
                    'ci_original' => $ciOriginal,
                    'ci' => $ciNormalizado,
                    'saldo' => $saldo,
                    'afiliado_id' => $afiliado->id,
                    'nombre' =>
                        $afiliado->nombres
                        . ' '
                        . $afiliado->apellidos,
                    'estado' => 'OK',
                    'mensaje' => null,
                ];
            }

            fclose($handle);

            /*
             * ---------------------------------------------------------
             * ESTADÍSTICAS
             * ---------------------------------------------------------
             */

            $total = count($filas);

            $correctos = collect($filas)
                ->where('estado', 'OK')
                ->count();

            $errores = collect($filas)
                ->where('estado', 'ERROR')
                ->count();

            $noEncontrados = collect($filas)
                ->where('estado', 'NO_ENCONTRADO')
                ->count();

            $yaExisten = collect($filas)
                ->where('estado', 'YA_EXISTE')
                ->count();

            /*
             * ---------------------------------------------------------
             * GUARDAR PREVIEW TEMPORAL
             * ---------------------------------------------------------
             *
             * No usamos la sesión para almacenar todas las filas.
             */

            $token = (string) Str::uuid();

            $ruta = 'importaciones/saldos/' . $token . '.json';

            Storage::disk('local')->put(
                $ruta,
                json_encode(
                    [
                        'usuario_id' => Auth::id(),
                        'creado_en' => now()->toIso8601String(),
                        'filas' => $filas,
                    ],
                    JSON_UNESCAPED_UNICODE
                )
            );

            return view(
                'carga-datos.index',
                compact(
                    'filas',
                    'token',
                    'total',
                    'correctos',
                    'errores',
                    'noEncontrados',
                    'yaExisten'
                )
            );

        } catch (\Throwable $e) {

            if (is_resource($handle)) {
                fclose($handle);
            }

            return back()
                ->withErrors([
                    'archivo' =>
                        'Ocurrió un error al procesar el CSV: '
                        . $e->getMessage(),
                ]);
        }
    }

    /**
     * Actualiza una fila del preview.
     *
     * Este método será utilizado por el modal de corrección.
     */
    public function corregir(
        Request $request,
        string $token
    ) {
                $datos = $request->validate([
            'fila' => [
                'required',
                'integer',
                'min:2',
            ],

            'ci' => [
                'required',
                'string',
                'max:50',
            ],

            'saldo' => [
                'required',
                'numeric',
                'gt:0',
            ],
        ]);

        $datosPreview =
            $this->obtenerPreview(
                $token
            );

        $filas = $datosPreview['filas'];

        $indice = collect($filas)
            ->search(
                fn ($fila) =>
                    $fila['fila'] === (int) $datos['fila']
            );

        if ($indice === false) {
            return back()
                ->withErrors([
                    'fila' =>
                        'No se encontró la fila que deseas corregir.',
                ]);
        }

        /*
         * Normalizamos nuevamente el CI.
         */

        $ci = $this->matcher->normalizarCi(
            $datos['ci']
        );

        if ($ci === null) {
            return back()
                ->withErrors([
                    'ci' =>
                        'El CI ingresado no es válido.',
                ]);
        }

        /*
         * Buscamos nuevamente el afiliado.
         */

        $indiceAfiliados =
            $this->matcher->construirIndiceAfiliados();

        $afiliado =
            $indiceAfiliados->get($ci);

        if (!$afiliado) {
            return back()
                ->withErrors([
                    'ci' =>
                        'No se encontró ningún afiliado con el CI ingresado.',
                ]);
        }

        /*
         * Evitamos duplicar saldos activos.
         */

        $saldoExistente = Saldo::query()
            ->where(
                'afiliado_id',
                $afiliado->id
            )
            ->where(
                'estado',
                'ACTIVO'
            )
            ->exists();

        if ($saldoExistente) {
            return back()
                ->withErrors([
                    'ci' =>
                        'El afiliado ya tiene un saldo activo registrado.',
                ]);
        }

        /*
         * Actualizamos la fila.
         */

        $filas[$indice]['ci'] = $ci;
        $filas[$indice]['saldo'] =
            round((float) $datos['saldo'], 2);

        $filas[$indice]['afiliado_id'] =
            $afiliado->id;

        $filas[$indice]['nombre'] =
            $afiliado->nombres
            . ' '
            . $afiliado->apellidos;

        $filas[$indice]['estado'] = 'OK';

        $filas[$indice]['mensaje'] = null;

        /*
         * Guardamos nuevamente el preview.
         */

        $this->guardarPreview(
            $token,
            $datosPreview,
            $filas
        );

        return back()->with(
            'success',
            'La fila fue corregida correctamente.'
        );
    }

    /**
     * Confirma definitivamente la importación.
     */
    public function confirmar(
        Request $request,
        string $token
    ) {
        $datosPreview =
            $this->obtenerPreview(
                $token
            );

        $filas = collect(
            $datosPreview['filas']
        );

        /*
         * Solo se pueden importar filas OK.
         */

        $filasConError = $filas
            ->where('estado', '!=', 'OK');

        if ($filasConError->isNotEmpty()) {

            return back()
                ->withErrors([
                    'importacion' =>
                        'No se puede confirmar la importación mientras existan filas con errores o CI no encontrados.',
                ]);
        }

        if ($filas->isEmpty()) {

            return back()
                ->withErrors([
                    'importacion' =>
                        'No existen registros para importar.',
                ]);
        }

        $usuario = Auth::user();

        try {

            DB::transaction(function () use (
                $filas,
                $usuario
            ) {

                foreach ($filas as $fila) {

                    /*
                     * Protección adicional:
                     * volvemos a comprobar que el afiliado existe.
                     */

                    $afiliado = Afiliado::find(
                        $fila['afiliado_id']
                    );

                    if (!$afiliado) {
                        throw new \Exception(
                            "El afiliado de la fila {$fila['fila']} ya no existe."
                        );
                    }

                    /*
                     * Protección contra duplicados.
                     */

                    $saldoExistente = Saldo::query()
                        ->where(
                            'afiliado_id',
                            $afiliado->id
                        )
                        ->where(
                            'estado',
                            'ACTIVO'
                        )
                        ->exists();

                    if ($saldoExistente) {
                        throw new \Exception(
                            "El afiliado {$afiliado->ci} ya tiene un saldo activo."
                        );
                    }

                    /*
                     * SALDO HISTÓRICO
                     *
                     * No tiene pago asociado.
                     *
                     * El monto importado es el saldo original
                     * y también el saldo actualmente disponible.
                     */

                    Saldo::create([
                        'afiliado_id' =>
                            $afiliado->id,

                        'pago_id' => null,

                        'monto' =>
                            round(
                                (float) $fila['saldo'],
                                2
                            ),

                        'monto_disponible' =>
                            round(
                                (float) $fila['saldo'],
                                2
                            ),

                        'estado' => 'ACTIVO',

                        'observacion' =>
                            'Saldo histórico importado.',

                    ]);
                }
            });

            /*
             * Eliminamos el archivo temporal después
             * de una importación exitosa.
             */

            $ruta =
                'importaciones/saldos/'
                . $token
                . '.json';

            Storage::disk('local')->delete(
                $ruta
            );

            return redirect()
                ->route('saldos.index')
                ->with(
                    'success',
                    'Los saldos históricos fueron importados correctamente.'
                );

        } catch (\Throwable $e) {

            return back()
                ->withErrors([
                    'importacion' =>
                        'No se pudo completar la importación: '
                        . $e->getMessage(),
                ]);
        }
    }

    /**
     * Obtiene un preview previamente almacenado.
     */
    private function obtenerPreview(
        string $token
    ): array {

        $ruta =
            'importaciones/saldos/'
            . $token
            . '.json';

        if (!Storage::disk('local')->exists($ruta)) {
            abort(
                404,
                'El preview de importación ya no existe o expiró.'
            );
        }

        $contenido =
            Storage::disk('local')->get($ruta);

        $datos =
            json_decode(
                $contenido,
                true
            );

        if (
            !is_array($datos) ||
            !isset($datos['usuario_id']) ||
            !isset($datos['filas'])
        ) {
            abort(
                422,
                'El preview de importación no es válido.'
            );
        }

        /*
         * Un usuario solamente puede modificar
         * su propio preview.
         */

        if (
            (int) $datos['usuario_id']
            !== (int) Auth::id()
        ) {
            abort(
                403,
                'No tienes permiso para modificar este preview.'
            );
        }

        return $datos;
    }

    /**
     * Guarda las modificaciones realizadas al preview.
     */
    private function guardarPreview(
        string $token,
        array $datosPreview,
        array $filas
    ): void {

        $ruta =
            'importaciones/saldos/'
            . $token
            . '.json';

        $datosPreview['filas'] = $filas;

        Storage::disk('local')->put(
            $ruta,
            json_encode(
                $datosPreview,
                JSON_UNESCAPED_UNICODE
            )
        );
    }

    /**
     * Convierte un monto del CSV a un número decimal válido.
     */
    
    private function normalizarMonto(string $valor): ?float
    {
        $valor = trim($valor);

        if ($valor === '') {
            return null;
        }

        // Eliminar espacios normales y espacios especiales.
        $valor = preg_replace('/[\s\x{00A0}]+/u', '', $valor);

        if ($valor === null || $valor === '') {
            return null;
        }

        // Formato decimal con coma: 9,00 o 1.250,50.
        if (str_contains($valor, ',')) {
            // Si también hay puntos, deben ser separadores de miles.
            if (str_contains($valor, '.')) {
                if (!preg_match('/^\d{1,3}(?:\.\d{3})+,\d{1,2}$/', $valor)) {
                    return null;
                }

                $valor = str_replace('.', '', $valor);
            } elseif (!preg_match('/^\d+,\d{1,2}$/', $valor)) {
                return null;
            }

            $valor = str_replace(',', '.', $valor);
        } else {
            // Formato decimal con punto: 9.00 o entero: 9.
            if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $valor)) {
                return null;
            }
        }

        return round((float) $valor, 2);
    }

}