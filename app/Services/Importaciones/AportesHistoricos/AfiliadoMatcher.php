<?php

namespace App\Services\Importaciones\AportesHistoricos;

use App\Models\Afiliado;
use Illuminate\Support\Str;

class AfiliadoMatcher
{
    /**
     * Índice de afiliadas por nombre completo normalizado.
     *
     * [
     *     'QUISPE USMAYO LIDIA FIDELA' => [
     *         Afiliado,
     *     ],
     * ]
     */
    private array $indiceAfiliados = [];

    /**
     * Índice por posición y palabra exacta.
     *
     * [
     *     0 => [
     *         'QUISPE' => [
     *             1809 => Afiliado,
     *         ],
     *     ],
     * ]
     */
    private array $indicePalabrasPorPosicion = [];

    /**
     * Índice por posición e inicial.
     *
     * [
     *     0 => [
     *         'Q' => [
     *             1809 => Afiliado,
     *         ],
     *     ],
     * ]
     */
    private array $indiceInicialesPorPosicion = [];

    /**
     * Partes normalizadas del nombre de cada afiliada.
     *
     * [
     *     1809 => [
     *         'QUISPE',
     *         'USMAYO',
     *         'LIDIA',
     *         'FIDELA',
     *     ],
     * ]
     */
    private array $partesNombrePorAfiliado = [];

    public function __construct()
    {
        $this->cargarIndiceAfiliados();
    }

    /**
     * Busca una afiliada a partir del nombre proveniente del CSV.
     */
    public function buscar(string $nombreCsv): array
    {
        $nombreNormalizado = $this->normalizar($nombreCsv);

        /*
         * El nombre es obligatorio.
         */
        if ($nombreNormalizado === '') {
            return [
                'estado' => 'DATOS_INCOMPLETOS',
                'afiliado' => null,
                'afiliados' => [],
                'nombre_normalizado' => '',
            ];
        }

        /*
         * ---------------------------------------------------------
         * 1. COINCIDENCIA EXACTA
         * ---------------------------------------------------------
         */

        $afiliadosEncontrados =
            $this->indiceAfiliados[$nombreNormalizado] ?? [];

        /*
         * Más de una afiliada con exactamente el mismo nombre.
         */
        if (count($afiliadosEncontrados) > 1) {
            return [
                'estado' => 'AMBIGUA',
                'afiliado' => null,
                'afiliados' => $afiliadosEncontrados,
                'nombre_normalizado' => $nombreNormalizado,
            ];
        }

        /*
         * Encontramos exactamente una afiliada.
         */
        if (count($afiliadosEncontrados) === 1) {
            return [
                'estado' => 'ENCONTRADA',
                'afiliado' => $afiliadosEncontrados[0],
                'afiliados' => $afiliadosEncontrados,
                'nombre_normalizado' => $nombreNormalizado,
            ];
        }

        /*
         * ---------------------------------------------------------
         * 2. COINCIDENCIA POR ABREVIACIÓN
         * ---------------------------------------------------------
         */

        $afiliadosAbreviados =
            $this->buscarCoincidenciasPorAbreviacion(
                $nombreNormalizado
            );

        /*
         * No encontramos ninguna coincidencia.
         */
        if (count($afiliadosAbreviados) === 0) {
            return [
                'estado' => 'NO_ENCONTRADA',
                'afiliado' => null,
                'afiliados' => [],
                'nombre_normalizado' => $nombreNormalizado,
            ];
        }

        /*
         * Más de una posible afiliada.
         */
        if (count($afiliadosAbreviados) > 1) {
            return [
                'estado' => 'AMBIGUA',
                'afiliado' => null,
                'afiliados' => $afiliadosAbreviados,
                'nombre_normalizado' => $nombreNormalizado,
            ];
        }

        /*
         * Encontramos exactamente una por abreviación.
         */
        return [
            'estado' => 'ENCONTRADA_POR_ABREVIACION',
            'afiliado' => $afiliadosAbreviados[0],
            'afiliados' => $afiliadosAbreviados,
            'nombre_normalizado' => $nombreNormalizado,
        ];
    }

    /**
     * Busca coincidencias por abreviación utilizando
     * los índices previamente construidos.
     *
     * La diferencia importante respecto a la versión anterior
     * es que aquí INTERSECTAMOS los candidatos de cada posición.
     *
     * Ejemplo:
     *
     * CSV:
     * QUISPE USMAYO LIDIA F
     *
     * Se buscan candidatos que cumplan simultáneamente:
     *
     * posición 0 -> QUISPE
     * posición 1 -> USMAYO
     * posición 2 -> LIDIA
     * posición 3 -> F*
     *
     * En lugar de recorrer todas las afiliadas.
     */
    private function buscarCoincidenciasPorAbreviacion(
        string $nombreCsv
    ): array {

        $partesCsv = $this->obtenerPartesNombre($nombreCsv);

        if (count($partesCsv) === 0) {
            return [];
        }

        /*
         * Aquí guardaremos los candidatos que cumplen
         * TODAS las posiciones.
         */
        $candidatos = null;

        foreach ($partesCsv as $posicion => $parteCsv) {

            /*
             * Una inicial solamente puede coincidir
             * con la inicial de la palabra correspondiente.
             */
            if (mb_strlen($parteCsv, 'UTF-8') === 1) {

                $coincidenciasPosicion =
                    $this->indiceInicialesPorPosicion[$posicion][$parteCsv]
                    ?? [];

            } else {

                /*
                 * Una palabra completa debe coincidir
                 * exactamente con la palabra de esa posición.
                 */
                $coincidenciasPosicion =
                    $this->indicePalabrasPorPosicion[$posicion][$parteCsv]
                    ?? [];
            }

            /*
             * Si una sola posición no tiene candidatos,
             * no existe ninguna coincidencia posible.
             */
            if (count($coincidenciasPosicion) === 0) {
                return [];
            }

            /*
             * Primera posición:
             * copiamos todos sus candidatos.
             */
            if ($candidatos === null) {

                $candidatos = $coincidenciasPosicion;

                continue;
            }

            /*
             * INTERSECCIÓN REAL.
             *
             * Conservamos únicamente los IDs que también
             * aparecen en esta posición.
             */
            $candidatos = array_intersect_key(
                $candidatos,
                $coincidenciasPosicion
            );

            /*
             * Si ya no queda ningún candidato,
             * podemos terminar inmediatamente.
             */
            if (count($candidatos) === 0) {
                return [];
            }
        }

        if ($candidatos === null || count($candidatos) === 0) {
            return [];
        }

        /*
         * Verificación final.
         *
         * Esto mantiene la regla original:
         *
         * - palabra exacta = válida
         * - una sola letra al inicio = abreviación válida
         */
        $coincidencias = [];

        foreach ($candidatos as $afiliadoId => $afiliado) {

            $partesBd =
                $this->partesNombrePorAfiliado[$afiliadoId]
                ?? [];

            if (
                $this->esNombreCompatiblePorAbreviacion(
                    $partesCsv,
                    $partesBd
                )
            ) {
                $coincidencias[$afiliadoId] = $afiliado;
            }
        }

        return array_values($coincidencias);
    }

    /**
     * Determina si un nombre del CSV puede corresponder
     * al nombre completo registrado en BD mediante abreviaciones.
     *
     * Ejemplo válido:
     *
     * CSV:
     * QUISPE USMAYO LIDIA F
     *
     * BD:
     * QUISPE USMAYO LIDIA FIDELA
     */
    private function esNombreCompatiblePorAbreviacion(
        array $partesCsv,
        array $partesBd
    ): bool {

        if (
            count($partesCsv) === 0
            || count($partesBd) === 0
        ) {
            return false;
        }

        /*
         * El CSV no puede tener más palabras que la BD.
         */
        if (count($partesCsv) > count($partesBd)) {
            return false;
        }

        /*
         * Comparamos posición por posición.
         */
        foreach ($partesCsv as $i => $parteCsv) {

            $parteBd = $partesBd[$i] ?? null;

            if ($parteBd === null) {
                return false;
            }

            /*
             * Coincidencia exacta.
             */
            if ($parteCsv === $parteBd) {
                continue;
            }

            /*
             * Una inicial puede representar
             * el comienzo de una palabra completa.
             *
             * F -> FIDELA
             */
            if (
                mb_strlen($parteCsv, 'UTF-8') === 1
                && str_starts_with(
                    $parteBd,
                    $parteCsv
                )
            ) {
                continue;
            }

            /*
             * No son compatibles.
             */
            return false;
        }

        return true;
    }

    /**
     * Divide un nombre normalizado en palabras.
     */
    private function obtenerPartesNombre(string $nombre): array
    {
        $nombre = $this->normalizar($nombre);

        if ($nombre === '') {
            return [];
        }

        return preg_split(
            '/\s+/',
            $nombre
        ) ?: [];
    }

    /**
     * Carga todas las afiliadas de la BD.
     *
     * IMPORTANTE:
     * No se filtra por colegio_id.
     */
    private function cargarIndiceAfiliados(): void
    {
        $afiliados = Afiliado::query()
            ->select([
                'id',
                'colegio_id',
                'nombres',
                'apellidos',
                'ci',
                'matricula_profesional',
                'fecha_afiliacion',
                'estado',
            ])
            ->get();

        foreach ($afiliados as $afiliado) {

            $nombreCompleto = trim(
                $afiliado->apellidos
                . ' '
                . $afiliado->nombres
            );

            $nombreNormalizado =
                $this->normalizar($nombreCompleto);

            if ($nombreNormalizado === '') {
                continue;
            }

            /*
             * Guardamos las partes ya normalizadas.
             *
             * Así no volvemos a ejecutar normalizar()
             * durante cada búsqueda.
             */
            $partesNombre =
                $this->obtenerPartesNombre(
                    $nombreNormalizado
                );

            $this->partesNombrePorAfiliado[$afiliado->id] =
                $partesNombre;

            /*
             * Índice exacto por nombre completo.
             */
            $this->indiceAfiliados[
                $nombreNormalizado
            ][] = $afiliado;
        }

        /*
         * Construimos los índices secundarios.
         */
        foreach ($this->indiceAfiliados as $afiliados) {

            foreach ($afiliados as $afiliado) {

                $afiliadoId = $afiliado->id;

                $partesNombre =
                    $this->partesNombrePorAfiliado[$afiliadoId]
                    ?? [];

                foreach (
                    $partesNombre as $posicion => $parteNombre
                ) {

                    /*
                     * Índice de palabra completa.
                     */
                    $this->indicePalabrasPorPosicion
                        [$posicion]
                        [$parteNombre]
                        [$afiliadoId] = $afiliado;

                    /*
                     * Índice de inicial.
                     */
                    $inicial = mb_substr(
                        $parteNombre,
                        0,
                        1,
                        'UTF-8'
                    );

                    $this->indiceInicialesPorPosicion
                        [$posicion]
                        [$inicial]
                        [$afiliadoId] = $afiliado;
                }
            }
        }
    }

    /**
     * Normaliza un nombre.
     */
    private function normalizar(?string $valor): string
    {
        if ($valor === null) {
            return '';
        }

        $valor = trim($valor);

        if ($valor === '') {
            return '';
        }

        /*
         * Elimina tildes y otros diacríticos.
         */
        $valor = Str::ascii($valor);

        /*
         * Convierte todo a mayúsculas.
         */
        $valor = mb_strtoupper(
            $valor,
            'UTF-8'
        );

        /*
         * Elimina puntos utilizados en iniciales.
         *
         * LIDIA F. -> LIDIA F
         */
        $valor = str_replace(
            '.',
            '',
            $valor
        );

        /*
         * Convierte múltiples espacios
         * en un solo espacio.
         */
        $valor = preg_replace(
            '/\s+/',
            ' ',
            $valor
        );

        return trim($valor);
    }
}