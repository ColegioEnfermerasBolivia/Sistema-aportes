<?php

namespace App\Services;

use App\Models\Departamento;
use App\Models\Egresada;
use App\Models\EgresadaFormacion;
use App\Models\Universidad;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ImportacionEgresadasService
{
    private const COLUMNAS_REQUERIDAS = [
        'ci',
        'nombres',
        'apellidos',
        'universidad',
        'departamento',
        'carrera',
    ];

    private const COLUMNAS_PERMITIDAS = [
        'ci',
        'nombres',
        'apellidos',
        'celular',
        'universidad',
        'departamento',
        'carrera',
        'fecha_egreso',
        'estado_titulacion',
        'numero_titulo',
    ];

    public function importar(string $ruta, int $usuarioId): array
    {
        $filas = $this->leerCsv($ruta);
        $universidades = Universidad::query()
            ->get(['id', 'nombre'])
            ->groupBy(fn (Universidad $universidad): string =>
                $this->normalizarTexto($universidad->nombre)
            );
        $departamentos = Departamento::query()
            ->get(['id', 'codigo', 'nombre']);
        $porDepartamento = [];

        foreach ($departamentos as $departamento) {
            $porDepartamento[$this->normalizarTexto($departamento->codigo)]
                = $departamento;
            $porDepartamento[$this->normalizarTexto($departamento->nombre)]
                = $departamento;
        }

        $registros = [];
        $errores = [];
        $personasCsv = [];
        $formacionesCsv = [];

        foreach ($filas as $fila) {
            $numero = $fila['numero'];
            $datos = $fila['datos'];
            foreach ([
                'celular',
                'fecha_egreso',
                'estado_titulacion',
                'numero_titulo',
            ] as $opcional) {
                if (($datos[$opcional] ?? null) === '') {
                    $datos[$opcional] = null;
                }
            }

            $validador = Validator::make($datos, [
                'ci' => ['required', 'string', 'max:30'],
                'nombres' => ['required', 'string', 'max:150'],
                'apellidos' => ['required', 'string', 'max:150'],
                'celular' => ['nullable', 'string', 'max:30'],
                'universidad' => ['required', 'string', 'max:200'],
                'departamento' => ['required', 'string', 'max:100'],
                'carrera' => ['required', 'string', 'max:200'],
                'fecha_egreso' => [
                    'nullable',
                    'date',
                    'after_or_equal:1900-01-01',
                ],
                'estado_titulacion' => [
                    'nullable',
                    Rule::in(EgresadaFormacion::ESTADOS_TITULACION),
                ],
                'numero_titulo' => ['nullable', 'string', 'max:100'],
            ]);

            if ($validador->fails()) {
                foreach ($validador->errors()->all() as $error) {
                    $errores[] = "Fila {$numero}: {$error}";
                }

                continue;
            }

            $datos = $validador->validated();
            $ci = $this->normalizarCi($datos['ci']);
            $coincidenciasUniversidad = $universidades->get(
                $this->normalizarTexto($datos['universidad'])
            );
            $universidad = $coincidenciasUniversidad?->count() === 1
                ? $coincidenciasUniversidad->first()
                : null;
            $departamento = $porDepartamento[
                $this->normalizarTexto($datos['departamento'])
            ] ?? null;

            if (!$universidad) {
                $errores[] = "Fila {$numero}: la universidad no existe o su nombre es ambiguo.";
            }

            if (!$departamento) {
                $errores[] = "Fila {$numero}: el departamento no existe.";
            }

            if (!$universidad || !$departamento) {
                continue;
            }

            $persona = [
                'nombres' => $this->normalizarTexto($datos['nombres']),
                'apellidos' => $this->normalizarTexto($datos['apellidos']),
            ];

            if (isset($personasCsv[$ci]) && $personasCsv[$ci] !== $persona) {
                $errores[] = "Fila {$numero}: el CI aparece con nombres o apellidos diferentes.";
                continue;
            }

            $personasCsv[$ci] = $persona;
            $claveFormacion = implode('|', [
                $ci,
                $departamento->id,
                $universidad->id,
                $this->normalizarTexto($datos['carrera']),
            ]);
            $duplicadaEnArchivo = isset($formacionesCsv[$claveFormacion]);
            $formacionesCsv[$claveFormacion] = true;

            $registros[] = [
                'fila' => $numero,
                'ci' => $ci,
                'datos' => $datos,
                'departamento_id' => (int) $departamento->id,
                'universidad_id' => (int) $universidad->id,
                'duplicada_en_archivo' => $duplicadaEnArchivo,
            ];
        }

        if ($errores !== []) {
            throw ValidationException::withMessages([
                'archivo' => $errores,
            ]);
        }

        $ciExistentes = array_values(array_unique(array_column($registros, 'ci')));
        if ($ciExistentes === []) {
            return [
                'filas_procesadas' => 0,
                'egresadas_creadas' => 0,
                'formaciones_importadas' => 0,
                'formaciones_duplicadas' => 0,
            ];
        }

        $existentesPorCi = Egresada::query()
            ->with('afiliado')
            ->whereRaw(
                'UPPER(TRIM(ci)) IN ('.implode(
                    ',',
                    array_fill(0, count($ciExistentes), '?')
                ).')',
                $ciExistentes
            )
            ->get()
            ->keyBy(fn (Egresada $egresada): string =>
                $this->normalizarCi($egresada->ci)
            );

        foreach ($existentesPorCi as $ci => $egresada) {
            $esperada = $personasCsv[$ci];

            if ($egresada->estaConvertida()) {
                $errores[] = "El CI {$ci} corresponde a una egresada ya convertida en afiliada.";
                continue;
            }

            if (
                $this->normalizarTexto($egresada->nombres) !== $esperada['nombres']
                || $this->normalizarTexto($egresada->apellidos) !== $esperada['apellidos']
            ) {
                $errores[] = "El CI {$ci} ya existe con nombres o apellidos diferentes.";
            }
        }

        if ($errores !== []) {
            throw ValidationException::withMessages([
                'archivo' => $errores,
            ]);
        }

        return DB::transaction(function () use (
            $registros,
            $existentesPorCi,
            $usuarioId
        ): array {
            $egresadasPorCi = $existentesPorCi->all();
            $resultado = [
                'filas_procesadas' => count($registros),
                'egresadas_creadas' => 0,
                'formaciones_importadas' => 0,
                'formaciones_duplicadas' => 0,
            ];
            $duplicadasEnArchivo = [];
            $egresadasBloqueadas = [];

            foreach ($registros as $registro) {
                $ci = $registro['ci'];

                if (isset($egresadasBloqueadas[$ci])) {
                    $egresada = $egresadasBloqueadas[$ci];
                } elseif (!isset($egresadasPorCi[$ci])) {
                    $egresada = Egresada::query()
                        ->whereRaw('UPPER(TRIM(ci)) = ?', [$ci])
                        ->lockForUpdate()
                        ->first();

                    if (!$egresada) {
                        $egresada = Egresada::create([
                            'ci' => $ci,
                            'nombres' => trim($registro['datos']['nombres']),
                            'apellidos' => trim($registro['datos']['apellidos']),
                            'celular' => $registro['datos']['celular'] ?? null,
                            'registrado_por' => $usuarioId,
                        ]);
                        $resultado['egresadas_creadas']++;
                    } else {
                        $egresada->load('afiliado');

                        if ($egresada->estaConvertida()) {
                            throw ValidationException::withMessages([
                                'archivo' => "El CI {$ci} fue convertido en afiliada durante la importación.",
                            ]);
                        }
                    }

                    $egresadasPorCi[$ci] = $egresada;
                    $egresadasBloqueadas[$ci] = $egresada;
                } else {
                    $egresada = Egresada::query()
                        ->with('afiliado')
                        ->lockForUpdate()
                        ->findOrFail($egresadasPorCi[$ci]->id);

                    if ($egresada->estaConvertida()) {
                        throw ValidationException::withMessages([
                            'archivo' => "El CI {$ci} fue convertido en afiliada durante la importación.",
                        ]);
                    }

                    $egresadasBloqueadas[$ci] = $egresada;
                }

                $claveFormacion = implode('|', [
                    $ci,
                    $registro['departamento_id'],
                    $registro['universidad_id'],
                    $this->normalizarTexto($registro['datos']['carrera']),
                ]);

                if (
                    $registro['duplicada_en_archivo']
                    || isset($duplicadasEnArchivo[$claveFormacion])
                    || EgresadaFormacion::query()
                        ->where('egresada_id', $egresada->id)
                        ->where('departamento_id', $registro['departamento_id'])
                        ->where('universidad_id', $registro['universidad_id'])
                        ->get(['carrera'])
                        ->contains(fn (EgresadaFormacion $formacion): bool =>
                            $this->normalizarTexto($formacion->carrera)
                                === $this->normalizarTexto(
                                    $registro['datos']['carrera']
                                )
                        )
                ) {
                    $duplicadasEnArchivo[$claveFormacion] = true;
                    $resultado['formaciones_duplicadas']++;
                    continue;
                }

                EgresadaFormacion::create([
                    'egresada_id' => $egresada->id,
                    'universidad_id' => $registro['universidad_id'],
                    'departamento_id' => $registro['departamento_id'],
                    'carrera' => trim($registro['datos']['carrera']),
                    'fecha_egreso' => $registro['datos']['fecha_egreso'] ?? null,
                    'estado_titulacion' => $registro['datos']['estado_titulacion']
                        ?: 'EGRESADA',
                    'numero_titulo' => $registro['datos']['numero_titulo'] ?? null,
                    'registrado_por' => $usuarioId,
                ]);
                $resultado['formaciones_importadas']++;
            }

            return $resultado;
        });
    }

    private function leerCsv(string $ruta): array
    {
        if (!is_readable($ruta)) {
            throw new RuntimeException('No se puede leer el archivo CSV.');
        }

        $archivo = fopen($ruta, 'rb');

        if ($archivo === false) {
            throw new RuntimeException('No se pudo abrir el archivo CSV.');
        }

        try {
            $primeraLinea = fgets($archivo);

            if ($primeraLinea === false) {
                throw ValidationException::withMessages([
                    'archivo' => 'El archivo CSV está vacío.',
                ]);
            }

            $separador = substr_count($primeraLinea, ';')
                > substr_count($primeraLinea, ',') ? ';' : ',';
            rewind($archivo);

            $encabezados = fgetcsv($archivo, 0, $separador, '"', '');

            if ($encabezados === false || $encabezados === [null]) {
                throw ValidationException::withMessages([
                    'archivo' => 'No se pudieron leer los encabezados del CSV.',
                ]);
            }

            $encabezados[0] = preg_replace(
                '/^\xEF\xBB\xBF/',
                '',
                (string) $encabezados[0]
            );
            $encabezados = array_map(
                fn ($encabezado): string => mb_strtolower(
                    trim((string) $encabezado),
                    'UTF-8'
                ),
                $encabezados
            );

            if (count($encabezados) !== count(array_unique($encabezados))) {
                throw ValidationException::withMessages([
                    'archivo' => 'El CSV contiene encabezados duplicados.',
                ]);
            }

            $desconocidos = array_diff(
                $encabezados,
                self::COLUMNAS_PERMITIDAS
            );

            if ($desconocidos !== []) {
                throw ValidationException::withMessages([
                    'archivo' => 'El CSV contiene columnas no reconocidas: '
                        .implode(', ', $desconocidos).'.',
                ]);
            }

            $faltantes = array_diff(self::COLUMNAS_REQUERIDAS, $encabezados);

            if ($faltantes !== []) {
                throw ValidationException::withMessages([
                    'archivo' => 'Faltan columnas requeridas: '
                        .implode(', ', $faltantes).'.',
                ]);
            }

            $filas = [];
            $numero = 1;

            while (($valores = fgetcsv($archivo, 0, $separador, '"', '')) !== false) {
                $numero++;

                if ($valores === [null] || count(array_filter(
                    $valores,
                    fn ($valor): bool => trim((string) $valor) !== ''
                )) === 0) {
                    continue;
                }

                if (count($valores) !== count($encabezados)) {
                    throw ValidationException::withMessages([
                        'archivo' => "La fila {$numero} no tiene la misma cantidad de columnas que el encabezado.",
                    ]);
                }

                $datos = array_combine($encabezados, $valores);
                $filas[] = [
                    'numero' => $numero,
                    'datos' => array_map(
                        fn ($valor): string => trim((string) $valor),
                        $datos
                    ),
                ];
            }

            return $filas;
        } finally {
            fclose($archivo);
        }
    }

    private function normalizarCi(string $ci): string
    {
        return mb_strtoupper(trim($ci), 'UTF-8');
    }

    private function normalizarTexto(string $texto): string
    {
        return Str::upper(
            Str::ascii(
                preg_replace('/\s+/u', ' ', trim($texto)) ?? ''
            )
        );
    }
}
