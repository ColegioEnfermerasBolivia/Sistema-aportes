<?php

namespace App\Services\Importaciones\AportesHistoricos;

use App\Models\Aporte;
use App\Models\Afiliado;
use App\Services\AuditoriaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ImportadorAportesHistoricos
{
    private const ESTADO_VERIFICADO = 'VERIFICADO';

    private const TAMANO_LOTE_AFILIADOS = 500;

    private const MESES = [
        'enero' => 1,
        'febrero' => 2,
        'marzo' => 3,
        'abril' => 4,
        'mayo' => 5,
        'junio' => 6,
        'julio' => 7,
        'agosto' => 8,
        'septiembre' => 9,
        'octubre' => 10,
        'noviembre' => 11,
        'diciembre' => 12,
    ];

    private const ENCABEZADOS = [
        'apellidos y nombres',
        'year',
        'enero',
        'febrero',
        'marzo',
        'abril',
        'mayo',
        'junio',
        'julio',
        'agosto',
        'septiembre',
        'octubre',
        'noviembre',
        'diciembre',
    ];

    public function __construct(
        private AfiliadoMatcher $matcher,
        private AuditoriaService $auditoriaService,
        private array $cacheMatchesAfiliados = []
    ) {
    }

    /**
     * Analiza el CSV sin modificar la base de datos.
     *
     * Este método será utilizado por el Controller para mostrar
     * la previsualización antes de importar.
     */
    public function analizar(string $rutaArchivo): array
    {
        $filas = $this->leerCsv($rutaArchivo);

        $registros = [];

        foreach ($filas as $numeroFila => $fila) {

            $resultadoFila = $this->analizarFila(
                $fila,
                $numeroFila
            );

            $registros[] = $resultadoFila;
        }

        $aportesExistentes = $this->precargarAportesExistentes(
            $registros
        );
        $resumen = [
            'filas_csv' => count($filas),
            'filas_procesadas' => 0,
            'afiliados_encontrados' => 0,
            'aportes_validos' => 0,
            'afiliadas_no_encontradas' => 0,
            'afiliadas_ambiguas' => 0,
            'datos_incompletos' => 0,
            'montos_invalidos' => 0,
            'aportes_duplicados' => 0,
        ];

        foreach ($registros as &$resultadoFila) {
            if (($resultadoFila['estado'] ?? null) === 'LISTO') {
                $resultadoFila['duplicados'] = array_values(array_filter(
                    $resultadoFila['aportes'],
                    fn (array $aporte): bool => isset(
                        $aportesExistentes[$this->claveAporte(
                            $resultadoFila['afiliado']['id'],
                            $aporte['year'],
                            $aporte['mes']
                        )]
                    )
                ));

                if (count($resultadoFila['duplicados']) > 0) {
                    $resultadoFila['estado'] = 'APORTE_DUPLICADO';
                    $resultadoFila['mensaje'] =
                        'Uno o más aportes ya existen en la base de datos.';
                }
            }

            $resumen['filas_procesadas']++;

            switch ($resultadoFila['estado']) {

                case 'LISTO':
                    $resumen['afiliados_encontrados']++;
                    $resumen['aportes_validos']
                        += count($resultadoFila['aportes']);
                    break;

                case 'AFILIADA_NO_ENCONTRADA':
                    $resumen['afiliadas_no_encontradas']++;
                    break;

                case 'AFILIADA_AMBIGUA':
                    $resumen['afiliadas_ambiguas']++;
                    break;

                case 'DATOS_INCOMPLETOS':
                    $resumen['datos_incompletos']++;
                    break;

                case 'MONTO_INVALIDO':
                    $resumen['montos_invalidos']++;
                    break;

                case 'APORTE_DUPLICADO':
                    $resumen['aportes_duplicados']
                        += count($resultadoFila['duplicados']);
                    break;
            }
        }
        unset($resultadoFila);

        return [
            'resumen' => $resumen,
            'registros' => $registros,
        ];
    }

    private function normalizarClaveNombre(
        string $nombre
    ): string {

        $nombre = trim($nombre);

        if ($nombre === '') {
            return '';
        }

        return mb_strtoupper(
            preg_replace(
                '/\s+/',
                ' ',
                $nombre
            ) ?? '',
            'UTF-8'
        );
    }

    /**
     * Importa los registros previamente analizados.
     *
     * Solo deben llegar aquí registros que estén en estado LISTO.
     */
    public function importar(
        array $registros,
        int $usuarioId
    ): array {

        $resultado = [
            'filas_importadas' => 0,
            'aportes_importados' => 0,
            'errores' => [],
        ];

        DB::transaction(function () use (
            $registros,
            $usuarioId,
            &$resultado
        ) {

            $aportesExistentes = $this->precargarAportesExistentes(
                $registros
            );

            foreach ($registros as $registro) {

                if (($registro['estado'] ?? null) !== 'LISTO') {
                    continue;
                }

                $afiliadoId = $registro['afiliado']['id'];

                foreach ($registro['aportes'] as $aporteData) {

                    $claveAporte = $this->claveAporte(
                        $afiliadoId,
                        $aporteData['year'],
                        $aporteData['mes']
                    );

                    if (isset($aportesExistentes[$claveAporte])) {
                        $resultado['errores'][] = [
                            'nombre' => $registro['nombre_csv'],
                            'year' => $aporteData['year'],
                            'mes' => $aporteData['mes'],
                            'motivo' => 'El aporte ya existe en la base de datos.',
                        ];

                        continue;
                    }

                    Aporte::create([
                        'afiliado_id' => $afiliadoId,
                        'colegio_id' => $registro['afiliado']['colegio_id'],
                        'pago_id' => null,
                        'mes' => $aporteData['mes'],
                        'year' => $aporteData['year'],
                        'monto' => $aporteData['monto'],
                        'estado' => self::ESTADO_VERIFICADO,
                        'observaciones' => 'Importación histórica de aportes',
                        'registrado_por' => $usuarioId,
                        'fecha_registro' => now(),
                        'verificado_por' => $usuarioId,
                        'fecha_verificacion' => now(),
                    ]);

                    $aportesExistentes[$claveAporte] = true;
                    $resultado['aportes_importados']++;
                }
                $resultado['filas_importadas']++;
            }

            $this->auditoriaService->registrar(
                $usuarioId,
                'IMPORTACION_APORTES_HISTORICOS',
                'aportes',
                null,
                null,
                [
                    'filas_importadas' =>
                        $resultado['filas_importadas'],

                    'aportes_importados' =>
                        $resultado['aportes_importados'],

                    'fecha' => now()->toDateTimeString(),
                ]
            );
        });

        return $resultado;
    }

    private function analizarFila(
        array $fila,
        int $numeroFila
    ): array {

        $nombreCsv = trim(
            $fila['apellidos y nombres'] ?? ''
        );

        $year = trim(
            $fila['year'] ?? ''
        );

        if ($nombreCsv === '' || $year === '') {

            return [
                'fila' => $numeroFila,
                'estado' => 'DATOS_INCOMPLETOS',
                'nombre_csv' => $nombreCsv,
                'year' => $year,
                'mensaje' => 'El nombre o el año están vacíos.',
                'afiliado' => null,
                'aportes' => [],
                'duplicados' => [],
            ];
        }

        if (
            !is_numeric($year)
            || (int) $year < 1900
            || (int) $year > 2100
        ) {

            return [
                'fila' => $numeroFila,
                'estado' => 'DATOS_INCOMPLETOS',
                'nombre_csv' => $nombreCsv,
                'year' => $year,
                'mensaje' => 'El año no es válido.',
                'afiliado' => null,
                'aportes' => [],
                'duplicados' => [],
            ];
        }

        $year = (int) $year;

        /*
         * Buscar afiliada exclusivamente por nombre.
         */
        /*
 * Buscar afiliada exclusivamente por nombre.
 *
 * El mismo nombre puede aparecer en muchas filas del
 * histórico, normalmente una por cada año.
 *
 * Guardamos el resultado para no ejecutar nuevamente
 * el matcher para el mismo nombre.
 */
    $nombreClave = $this->normalizarClaveNombre($nombreCsv);

    if (isset($this->cacheMatchesAfiliados[$nombreClave])) {

        $match = $this->cacheMatchesAfiliados[$nombreClave];

    } else {

        $match = $this->matcher->buscar($nombreCsv);

        $this->cacheMatchesAfiliados[$nombreClave] = $match;
    }

        if ($match['estado'] === 'DATOS_INCOMPLETOS') {

            return [
                'fila' => $numeroFila,
                'estado' => 'DATOS_INCOMPLETOS',
                'nombre_csv' => $nombreCsv,
                'year' => $year,
                'mensaje' => 'El nombre está vacío.',
                'afiliado' => null,
                'aportes' => [],
                'duplicados' => [],
            ];
        }

        if ($match['estado'] === 'NO_ENCONTRADA') {

            return [
                'fila' => $numeroFila,
                'estado' => 'AFILIADA_NO_ENCONTRADA',
                'nombre_csv' => $nombreCsv,
                'year' => $year,
                'mensaje' =>
                    'No se encontró una afiliada compatible en la base de datos.',
                'afiliado' => null,
                'aportes' => [],
                'duplicados' => [],
            ];
        }

        if ($match['estado'] === 'AMBIGUA') {

            return [
                'fila' => $numeroFila,
                'estado' => 'AFILIADA_AMBIGUA',
                'nombre_csv' => $nombreCsv,
                'year' => $year,
                'mensaje' =>
                    'El nombre coincide con más de una afiliada.',
                'afiliado' => null,
                'afiliados' =>
                    $this->formatearAfiliados(
                        $match['afiliados']
                    ),
                'aportes' => [],
                'duplicados' => [],
            ];
        }

        $afiliado = $match['afiliado'];

        /*
         * Construimos únicamente los meses que tengan
         * un aporte positivo.
         */
        $aportes = [];
        $montosInvalidos = [];

        foreach (self::MESES as $nombreMes => $numeroMes) {

            $valorOriginal = $fila[$nombreMes] ?? null;

            /*
             * Celda vacía = no hay aporte.
             */
            if (
                $valorOriginal === null
                || trim((string) $valorOriginal) === ''
            ) {
                continue;
            }

            $valor = $this->normalizarMonto(
                $valorOriginal
            );

            /*
             * Cero = no existe aporte.
             */
            if ($valor === 0.0) {
                continue;
            }

            if ($valor === null) {

                $montosInvalidos[] = [
                    'mes' => $numeroMes,
                    'nombre_mes' => $nombreMes,
                    'valor' => $valorOriginal,
                ];

                continue;
            }

            $aportes[] = [
                'mes' => $numeroMes,
                'nombre_mes' => $nombreMes,
                'year' => $year,
                'monto' => $valor,
                'valor_original' => $valorOriginal,
            ];
        }

        /*
         * Si existe algún monto inválido, la fila completa
         * se manda a revisión.
         */
        if (count($montosInvalidos) > 0) {

            return [
                'fila' => $numeroFila,
                'estado' => 'MONTO_INVALIDO',
                'nombre_csv' => $nombreCsv,
                'year' => $year,
                'mensaje' =>
                    'Uno o más meses contienen valores que no pudieron interpretarse como monto.',
                'afiliado' =>
                    $this->formatearAfiliado($afiliado),
                'aportes' => [],
                'montos_invalidos' => $montosInvalidos,
                'duplicados' => [],
            ];
        }

        /*
         * Si toda la fila tiene ceros/vacíos, no hay nada
         * que importar.
         */
        if (count($aportes) === 0) {

            return [
                'fila' => $numeroFila,
                'estado' => 'SIN_APORTES',
                'nombre_csv' => $nombreCsv,
                'year' => $year,
                'mensaje' =>
                    'La fila no contiene ningún aporte positivo.',
                'afiliado' =>
                    $this->formatearAfiliado($afiliado),
                'aportes' => [],
                'duplicados' => [],
            ];
        }

        return [
            'fila' => $numeroFila,
            'estado' => 'LISTO',
            'nombre_csv' => $nombreCsv,
            'year' => $year,

            /*
             * Guardamos el tipo de coincidencia porque
             * será útil para mostrarlo en la vista.
             */
            'tipo_match' => $match['estado'],

            'afiliado' =>
                $this->formatearAfiliado($afiliado),

            'aportes' => $aportes,

            'duplicados' => [],
        ];
    }

    private function precargarAportesExistentes(
        array $registros
    ): array {
        $candidatosPorAnio = [];

        foreach ($registros as $registro) {
            if (($registro['estado'] ?? null) !== 'LISTO') {
                continue;
            }

            $afiliadoId = (int) $registro['afiliado']['id'];

            foreach ($registro['aportes'] as $aporte) {
                $year = (int) $aporte['year'];
                $mes = (int) $aporte['mes'];

                $candidatosPorAnio[$year][$afiliadoId][$mes] = true;
            }
        }

        $aportesExistentes = [];

        foreach ($candidatosPorAnio as $year => $afiliados) {
            $meses = [];

            foreach ($afiliados as $mesesAfiliado) {
                foreach ($mesesAfiliado as $mes => $_) {
                    $meses[$mes] = true;
                }
            }

            $meses = array_keys($meses);

            foreach (
                array_chunk(
                    array_keys($afiliados),
                    self::TAMANO_LOTE_AFILIADOS
                ) as $afiliadoIds
            ) {
                $aportes = Aporte::query()
                    ->select(['afiliado_id', 'year', 'mes'])
                    ->where('year', $year)
                    ->whereIn('afiliado_id', $afiliadoIds)
                    ->whereIn('mes', $meses)
                    ->get();

                foreach ($aportes as $aporte) {
                    $aportesExistentes[$this->claveAporte(
                        $aporte->afiliado_id,
                        $aporte->year,
                        $aporte->mes
                    )] = true;
                }
            }
        }

        return $aportesExistentes;
    }

    private function claveAporte(
        int|string $afiliadoId,
        int|string $year,
        int|string $mes
    ): string {
        return "{$afiliadoId}:{$year}:{$mes}";
    }

    private function leerCsv(string $rutaArchivo): array
    {
        if (!is_readable($rutaArchivo)) {
            throw new \RuntimeException(
                'No se pudo leer el archivo CSV.'
            );
        }

        $handle = fopen($rutaArchivo, 'r');

        if ($handle === false) {
            throw new \RuntimeException(
                'No se pudo abrir el archivo CSV.'
            );
        }

        /*
         * Detectamos el separador leyendo la primera línea.
         *
         * El CSV definitivo debería utilizar coma.
         */
        $primeraLinea = fgets($handle);

        if ($primeraLinea === false) {
            fclose($handle);

            throw new \RuntimeException(
                'El archivo CSV está vacío.'
            );
        }

        $separador = $this->detectarSeparador(
            $primeraLinea
        );

        rewind($handle);

        $encabezados = fgetcsv(
            $handle,
            0,
            $separador
        );

        if ($encabezados === false) {
            fclose($handle);

            throw new \RuntimeException(
                'No se pudieron leer los encabezados del CSV.'
            );
        }

        $encabezados = array_map(
            fn ($encabezado) =>
                $this->normalizarEncabezado($encabezado),
            $encabezados
        );

        $this->validarEncabezados(
            $encabezados
        );

        $filas = [];

        while (($fila = fgetcsv(
            $handle,
            0,
            $separador
        )) !== false) {

            /*
             * Ignorar líneas completamente vacías.
             */
            if (
                count(array_filter(
                    $fila,
                    fn ($valor) =>
                        trim((string) $valor) !== ''
                )) === 0
            ) {
                continue;
            }

            $fila = array_pad(
                $fila,
                count($encabezados),
                null
            );

            $filas[] = array_combine(
                $encabezados,
                array_slice(
                    $fila,
                    0,
                    count($encabezados)
                )
            );
        }

        fclose($handle);

        return $filas;
    }

    private function detectarSeparador(
        string $linea
    ): string {

        $comas = substr_count(
            $linea,
            ','
        );

        $puntoComa = substr_count(
            $linea,
            ';'
        );

        return $puntoComa > $comas
            ? ';'
            : ',';
    }

    private function validarEncabezados(
        array $encabezados
    ): void {

        foreach (self::ENCABEZADOS as $encabezado) {

            if (!in_array(
                $encabezado,
                $encabezados,
                true
            )) {

                throw new \RuntimeException(
                    "Falta la columna obligatoria: {$encabezado}"
                );
            }
        }
    }

    private function normalizarEncabezado(
        ?string $valor
    ): string {

        if ($valor === null) {
            return '';
        }

        $valor = trim($valor);

        /*
         * Elimina BOM UTF-8.
         */
        $valor = preg_replace(
            '/^\xEF\xBB\xBF/',
            '',
            $valor
        );

        $valor = mb_strtolower(
            $valor,
            'UTF-8'
        );

        return trim($valor);
    }

    private function normalizarMonto(
        mixed $valor
    ): ?float {

        if ($valor === null) {
            return null;
        }

        if (is_int($valor) || is_float($valor)) {
            return round((float) $valor, 2);
        }

        $valor = trim((string) $valor);

        if ($valor === '') {
            return null;
        }

        /*
         * Elimina espacios.
         */
        $valor = str_replace(
            ' ',
            '',
            $valor
        );

        /*
         * Si tiene coma decimal:
         * 28,50 -> 28.50
         */
        if (
            str_contains($valor, ',')
            && !str_contains($valor, '.')
        ) {
            $valor = str_replace(
                ',',
                '.',
                $valor
            );
        }

        /*
         * Si contiene caracteres que no corresponden
         * a un número decimal.
         */
        if (!preg_match(
            '/^-?\d+(?:\.\d+)?$/',
            $valor
        )) {
            return null;
        }

        return round(
            (float) $valor,
            2
        );
    }


    private function formatearAfiliado(
        Afiliado $afiliado
    ): array {

        return [
            'id' => $afiliado->id,
            'nombre' =>
                trim(
                    $afiliado->apellidos
                    . ' '
                    . $afiliado->nombres
                ),
            'ci' => $afiliado->ci,
            'matricula_profesional' =>
                $afiliado->matricula_profesional,
            'colegio_id' => $afiliado->colegio_id,
            'fecha_afiliacion' =>
                $afiliado->fecha_afiliacion,
        ];
    }

    private function formatearAfiliados(
        array $afiliados
    ): array {

        return array_map(
            fn (Afiliado $afiliado) =>
                $this->formatearAfiliado(
                    $afiliado
                ),
            $afiliados
        );
    }
}