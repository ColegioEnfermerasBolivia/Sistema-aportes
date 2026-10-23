<?php

namespace App\Services;

use App\Models\Afiliado;
use App\Models\Colegio;
use App\Models\TransferenciaAfiliado;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ImportadorTransferenciasHistoricas
{
    /**
     * Normaliza el CI sin convertirlo a número.
     * Conserva letras, números y extensiones.
     */
    private function normalizarCi(string $ci): string
    {
        return mb_strtoupper(
            preg_replace('/\s+/u', '', trim($ci)),
            'UTF-8'
        );
    }

    /**
     * Normaliza nombres de colegios para comparar.
     */
    private function normalizarTexto(string $texto): string
    {
        $texto = mb_strtoupper(trim($texto), 'UTF-8');

        $texto = strtr($texto, [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
        ]);

        return preg_replace('/\s+/u', ' ', $texto);
    }

    /**
     * Convierte una fecha d/m/Y a Y-m-d.
     */
    private function normalizarFecha(string $fecha): ?string
    {
        $fecha = trim($fecha);

        foreach (['d/m/Y', 'j/n/Y'] as $formato) {
            try {
                $fechaNormalizada = Carbon::createFromFormat(
                    '!' . $formato,
                    $fecha
                );

                $errores = Carbon::getLastErrors();

                if (
                    $fechaNormalizada !== null &&
                    ($errores === false ||
                        ($errores['warning_count'] === 0 &&
                        $errores['error_count'] === 0))
                ) {
                    return $fechaNormalizada->format('Y-m-d');
                }
            } catch (\Exception $e) {
                // Continuar probando.
            }
        }

        return null;
    }

    /**
     * Analiza el CSV y devuelve un informe.
     * No inserta ni actualiza registros.
     */
    public function analizar(string $ruta): array
    {
        if (!is_readable($ruta)) {
            throw new RuntimeException(
                'No se puede leer el archivo CSV.'
            );
        }

        $archivo = fopen($ruta, 'rb');

        if ($archivo === false) {
            throw new RuntimeException(
                'No se pudo abrir el archivo CSV.'
            );
        }

        try {
            $primeraLinea = fgets($archivo);

            if ($primeraLinea === false) {
                throw new RuntimeException(
                    'El archivo CSV está vacío.'
                );
            }

            // Detectar separador: coma o punto y coma.
            $comas = substr_count($primeraLinea, ',');
            $puntoComas = substr_count($primeraLinea, ';');

            $separador = $puntoComas > $comas ? ';' : ',';

            rewind($archivo);

            $encabezados = fgetcsv(
                $archivo,
                0,
                $separador,
                '"',
                ''
            );

            if (!$encabezados) {
                throw new RuntimeException(
                    'No se pudieron leer los encabezados.'
                );
            }

            // Quitar BOM UTF-8 del primer encabezado.
            $encabezados[0] = preg_replace(
                '/^\xEF\xBB\xBF/',
                '',
                (string) $encabezados[0]
            );

            $encabezados = array_map(
                fn ($valor) => mb_strtolower(
                    trim((string) $valor),
                    'UTF-8'
                ),
                $encabezados
            );

            $requeridos = [
                'ci',
                'colegio_origen',
                'colegio_destino',
                'fecha_transferencia',
            ];

            $faltantes = array_diff(
                $requeridos,
                $encabezados
            );

            if ($faltantes) {
                throw new RuntimeException(
                    'Faltan columnas requeridas: '
                    . implode(', ', $faltantes)
                );
            }

            $indice = array_flip($encabezados);

            /*
             * Cargamos catálogos una sola vez para evitar
             * consultar la base de datos por cada fila.
             */
            $afiliadosPorCi = Afiliado::query()
                ->get(['id', 'ci', 'colegio_id'])
                ->groupBy(
                    fn ($afiliado) =>
                        $this->normalizarCi($afiliado->ci)
                );

            $colegiosPorNombre = Colegio::query()
                ->get(['id', 'nombre'])
                ->groupBy(
                    fn ($colegio) =>
                        $this->normalizarTexto($colegio->nombre)
                );

            $existentes = TransferenciaAfiliado::query()
                ->get([
                    'afiliado_id',
                    'colegio_id_origen',
                    'colegio_id_destino',
                    'fecha_transferencia',
                ])
                ->mapWithKeys(function ($transferencia) {
                    $clave = implode('|', [
                        $transferencia->afiliado_id,
                        $transferencia->colegio_id_origen,
                        $transferencia->colegio_id_destino,
                        Carbon::parse(
                            $transferencia->fecha_transferencia
                        )->format('Y-m-d'),
                    ]);

                    return [$clave => true];
                })
                ->all();

            $filas = [];
            $clavesCsv = [];
            $numeroFila = 1;

            while (
                ($columnas = fgetcsv(
                    $archivo,
                    0,
                    $separador,
                    '"',
                    ''
                )) !== false
            ) {
                $numeroFila++;

                if (
                    count($columnas) === 1
                    && trim((string) $columnas[0]) === ''
                ) {
                    continue;
                }

                $obtener = function (string $columna) use (
                    $columnas,
                    $indice
                ): string {
                    return trim(
                        (string) ($columnas[$indice[$columna]] ?? '')
                    );
                };

                $ciOriginal = $obtener('ci');
                $ci = $this->normalizarCi($ciOriginal);

                $origenTexto = $obtener('colegio_origen');
                $destinoTexto = $obtener('colegio_destino');
                $fechaTexto = $obtener('fecha_transferencia');

                $fila = [
                    'fila' => $numeroFila,
                    'ci' => $ciOriginal,
                    'colegio_origen' => $origenTexto,
                    'colegio_destino' => $destinoTexto,
                    'fecha_original' => $fechaTexto,
                    'estado' => 'LISTO',
                    'errores' => [],
                    'afiliado_id' => null,
                    'colegio_id_origen' => null,
                    'colegio_id_destino' => null,
                    'fecha_transferencia' => null,
                ];

                if ($ci === '') {
                    $fila['errores'][] = 'CI vacío.';
                } else {
                    $coincidencias = $afiliadosPorCi->get($ci);

                    if (!$coincidencias || $coincidencias->isEmpty()) {
                        $fila['errores'][] =
                            'No existe una afiliada con ese CI.';
                    } elseif ($coincidencias->count() > 1) {
                        $fila['errores'][] =
                            'El CI corresponde a varias afiliadas.';
                    } else {
                        $fila['afiliado_id'] =
                            $coincidencias->first()->id;
                    }
                }

                foreach ([
                    'origen' => $origenTexto,
                    'destino' => $destinoTexto,
                ] as $tipo => $nombre) {
                    if ($nombre === '') {
                        $fila['errores'][] =
                            "El colegio de {$tipo} está vacío.";
                        continue;
                    }

                    $coincidencias = $colegiosPorNombre->get(
                        $this->normalizarTexto($nombre)
                    );

                    if (!$coincidencias || $coincidencias->isEmpty()) {
                        $fila['errores'][] =
                            "No se encontró el colegio de {$tipo}.";
                    } elseif ($coincidencias->count() > 1) {
                        $fila['errores'][] =
                            "El colegio de {$tipo} es ambiguo.";
                    } else {
                        $fila["colegio_id_{$tipo}"] =
                            $coincidencias->first()->id;
                    }
                }

                $fecha = $this->normalizarFecha($fechaTexto);

                if ($fecha === null) {
                    $fila['errores'][] =
                        'Fecha inválida; se espera día/mes/año.';
                } else {
                    $fila['fecha_transferencia'] = $fecha;
                }

                if (
                    $fila['colegio_id_origen'] !== null
                    && $fila['colegio_id_destino'] !== null
                    && $fila['colegio_id_origen']
                        === $fila['colegio_id_destino']
                ) {
                    $fila['errores'][] =
                        'El colegio de origen y destino son iguales.';
                }

                if (
                    $fila['afiliado_id'] !== null
                    && $fila['colegio_id_origen'] !== null
                    && $fila['colegio_id_destino'] !== null
                    && $fecha !== null
                ) {
                    $clave = implode('|', [
                        $fila['afiliado_id'],
                        $fila['colegio_id_origen'],
                        $fila['colegio_id_destino'],
                        $fecha,
                    ]);

                    if (isset($existentes[$clave])) {
                        $fila['errores'][] =
                            'Esta transferencia ya existe en el sistema.';
                    } elseif (isset($clavesCsv[$clave])) {
                        $fila['errores'][] =
                            'Esta transferencia está repetida en el CSV.';
                    } else {
                        $clavesCsv[$clave] = true;
                    }
                }

                if ($fila['errores']) {
                    $fila['estado'] = 'ERROR';
                }

                $filas[] = $fila;
            }

            return [
                'total' => count($filas),
                'listas' => count(array_filter(
                    $filas,
                    fn ($fila) => $fila['estado'] === 'LISTO'
                )),
                'errores' => count(array_filter(
                    $filas,
                    fn ($fila) => $fila['estado'] === 'ERROR'
                )),
                'filas' => $filas,
            ];
        } finally {
            fclose($archivo);
        }
    }
}
