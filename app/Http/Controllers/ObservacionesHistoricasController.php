<?php

namespace App\Http\Controllers;

use App\Models\Afiliado;
use App\Models\ObservacionAfiliado;
use App\Services\Importaciones\CiMatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ObservacionesHistoricasController extends Controller
{
    public function __construct(
        private CiMatcher $matcher
    ) {}

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
        $handle = fopen($archivo->getRealPath(), 'r');

        if ($handle === false) {
            return back()->withErrors([
                'archivo' => 'No se pudo abrir el CSV.',
            ]);
        }

        try {
            // Detectar el separador y eliminar el BOM de Excel.
            $primeraLinea = fgets($handle);

            if ($primeraLinea === false) {
                return back()->withErrors([
                    'archivo' => 'El CSV está vacío.',
                ]);
            }

            $separador = match (true) {
                str_contains($primeraLinea, ';') => ';',
                str_contains($primeraLinea, "\t") => "\t",
                default => ',',
            };

            rewind($handle);

            $cabecera = fgetcsv($handle, 0, $separador);

            if ($cabecera === false) {
                return back()->withErrors([
                    'archivo' => 'No se pudo leer la cabecera del CSV.',
                ]);
            }

            $cabeceraNormalizada = array_map(
                fn ($valor) => mb_strtolower(
                    trim(
                        preg_replace(
                            '/^\xEF\xBB\xBF/',
                            '',
                            (string) $valor
                        )
                    )
                ),
                $cabecera
            );

            if ($cabeceraNormalizada !== ['ci', 'observacion']) {
                return back()->withErrors([
                    'archivo' =>
                        'El CSV debe contener exactamente las columnas CI y OBSERVACION, en ese orden.',
                ]);
            }


            $indiceAfiliados =
                $this->matcher->construirIndiceAfiliados();

            $filas = [];
            $numeroFila = 1;

            while (($fila = fgetcsv(
                $handle,
                0,
                $separador
            )) !== false) {
                $numeroFila++;

                // Ignorar filas completamente vacías.
                if (
                    count($fila) === 1 &&
                    trim((string) $fila[0]) === ''
                ) {
                    continue;
                }

                $ciOriginal = trim((string) ($fila[0] ?? ''));
                $observacion = trim((string) ($fila[1] ?? ''));

                $registro = [
                    'fila' => $numeroFila,
                    'ci_original' => $ciOriginal,
                    'ci' => null,
                    'afiliado_id' => null,
                    'nombre' => null,
                    'observacion' => $observacion,
                    'estado' => 'OK',
                    'mensaje' => null,
                ];

                if (count($fila) !== 2) {
                    $registro['estado'] = 'ERROR';
                    $registro['mensaje'] =
                        'La fila debe contener exactamente CI y OBSERVACION.';

                    $filas[] = $registro;
                    continue;
                }

                if ($observacion === '') {
                    $registro['estado'] = 'ERROR';
                    $registro['mensaje'] =
                        'La observación no puede estar vacía.';

                    $filas[] = $registro;
                    continue;
                }

                $ciNormalizado =
                    $this->matcher->normalizarCi($ciOriginal);

                if ($ciNormalizado === null) {
                    $registro['estado'] = 'ERROR';
                    $registro['mensaje'] = 'El CI no es válido.';

                    $filas[] = $registro;
                    continue;
                }

                $registro['ci'] = $ciNormalizado;

                $afiliado = $indiceAfiliados->get($ciNormalizado);

                if (!$afiliado) {
                    $registro['estado'] = 'NO_ENCONTRADO';
                    $registro['mensaje'] =
                        'No se encontró un afiliado con ese CI.';

                    $filas[] = $registro;
                    continue;
                }

                $registro['afiliado_id'] = $afiliado->id;
                $registro['nombre'] = trim(
                    $afiliado->nombres . ' ' . $afiliado->apellidos
                );

                $filas[] = $registro;
            }

            $token = (string) Str::uuid();

            $ruta = "importaciones/observaciones/{$token}.json";

            $guardado = Storage::disk('local')->put($ruta, json_encode([
                'usuario_id' => Auth::id(),
                'creado_en' => now()->toIso8601String(),
                'filas' => $filas,
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

            if (!$guardado) {
                throw new \RuntimeException(
                    'No se pudo guardar la previsualización de observaciones.'
                );
            }

            $total = count($filas);
            $correctos = collect($filas)
                ->where('estado', 'OK')->count();
            $errores = collect($filas)
                ->where('estado', 'ERROR')->count();
            $noEncontrados = collect($filas)
                ->where('estado', 'NO_ENCONTRADO')->count();

            return view('carga-datos.index', [
                'observacionesToken' => $token,
                'observacionesFilas' => $filas,
                'observacionesTotal' => $total,
                'observacionesCorrectos' => $correctos,
                'observacionesErrores' => $errores,
                'observacionesNoEncontrados' => $noEncontrados,
            ]);

        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'archivo' =>
                    'No se pudo procesar el CSV. Revisa su formato e inténtalo nuevamente.',
            ]);
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }
    }

    public function confirmar(string $token)
    {
        $datosPreview = $this->obtenerPreview($token);
        $filas = collect($datosPreview['filas'])
            ->where('estado', 'OK')
            ->values();

        if ($filas->isEmpty()) {
            return redirect()
                ->route('carga-datos.index')
                ->withErrors([
                    'importacion' =>
                        'No hay observaciones válidas para importar.',
                ]);
        }

        try {
            $importadas = DB::transaction(function () use ($filas) {
                foreach ($filas as $fila) {
                    if (
                        !is_array($fila) ||
                        !isset($fila['afiliado_id']) ||
                        filter_var(
                            $fila['afiliado_id'],
                            FILTER_VALIDATE_INT
                        ) === false ||
                        !isset($fila['fila']) ||
                        filter_var(
                            $fila['fila'],
                            FILTER_VALIDATE_INT
                        ) === false ||
                        !isset($fila['observacion']) ||
                        !is_string($fila['observacion']) ||
                        trim($fila['observacion']) === ''
                    ) {
                        throw new \RuntimeException(
                            'La previsualización contiene una fila válida incompleta.'
                        );
                    }

                    $afiliado = Afiliado::query()
                        ->lockForUpdate()
                        ->find((int) $fila['afiliado_id']);

                    if (!$afiliado) {
                        throw new \RuntimeException(
                            "El afiliado de la fila {$fila['fila']} ya no existe."
                        );
                    }

                    ObservacionAfiliado::create([
                        'afiliado_id' => $afiliado->id,
                        'observacion' => trim($fila['observacion']),
                        'usuario_id' => Auth::id(),
                    ]);
                }

                return $filas->count();
            });
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('carga-datos.index')
                ->withErrors([
                    'importacion' =>
                        'No se pudieron importar las observaciones. Revisa los datos e inténtalo nuevamente.',
                ]);
        }

        $ruta = "importaciones/observaciones/{$token}.json";
        try {
            $previewEliminado = Storage::disk('local')->delete($ruta);
        } catch (\Throwable $e) {
            report($e);
            $previewEliminado = false;
        }

        if (!$previewEliminado) {
            report(new \RuntimeException(
                "No se pudo eliminar la previsualización de observaciones {$token} después de importarla."
            ));

            return redirect()
                ->route('carga-datos.index')
                ->with(
                    'warning',
                    "Se importaron {$importadas} observaciones, pero no se pudo eliminar la previsualización. No vuelvas a confirmar este archivo."
                );
        }

        return redirect()
            ->route('carga-datos.index')
            ->with(
                'success',
                "Se importaron {$importadas} observaciones correctamente."
            );
    }

    private function obtenerPreview(string $token): array
    {
        if (!Str::isUuid($token)) {
            abort(404, 'La previsualización de observaciones no existe.');
        }

        $ruta = "importaciones/observaciones/{$token}.json";
        $disco = Storage::disk('local');

        if (!$disco->exists($ruta)) {
            abort(404, 'La previsualización de observaciones expiró o no existe.');
        }

        try {
            $datos = json_decode(
                $disco->get($ruta),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $e) {
            report($e);
            abort(422, 'La previsualización de observaciones no es válida.');
        }

        if (
            !is_array($datos) ||
            !isset($datos['usuario_id']) ||
            !is_array($datos['filas'])
        ) {
            abort(422, 'La previsualización de observaciones no es válida.');
        }

        if ((int) $datos['usuario_id'] !== (int) Auth::id()) {
            abort(403, 'No tienes permiso para confirmar esta previsualización.');
        }

        return $datos;
    }
}
