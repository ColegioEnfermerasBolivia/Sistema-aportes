<?php

namespace App\Services\Importaciones\AportesHistoricos;

use App\Models\Afiliado;
use Illuminate\Support\Str;

class AfiliadoMatcher
{
    private array $indicePalabras = [];

    private array $indiceIniciales = [];

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

        $afiliadosEncontrados = $this->buscarCoincidenciasPorAbreviacion(
            $nombreNormalizado
        );

        /*
         * Más de una afiliada es una coincidencia ambigua, sin importar
         * si las palabras llegaron en orden distinto o con iniciales.
         */
        if (count($afiliadosEncontrados) > 1) {
            return [
                'estado' => 'AMBIGUA',
                'afiliado' => null,
                'afiliados' => $afiliadosEncontrados,
                'nombre_normalizado' => $nombreNormalizado,
            ];
        }

        if (count($afiliadosEncontrados) === 1) {
            $afiliado = $afiliadosEncontrados[0];
            $partesAfiliado =
                $this->partesNombrePorAfiliado[$afiliado->id] ?? [];
            $partesCsv = $this->obtenerPartesNombre($nombreNormalizado);

            return [
                'estado' => count($partesCsv) === count($partesAfiliado)
                    && $this->coincidenPartesExactas($partesCsv, $partesAfiliado)
                        ? 'ENCONTRADA'
                        : 'ENCONTRADA_POR_ABREVIACION',
                'afiliado' => $afiliado,
                'afiliados' => $afiliadosEncontrados,
                'nombre_normalizado' => $nombreNormalizado,
            ];
        }

        return [
            'estado' => 'NO_ENCONTRADA',
            'afiliado' => null,
            'afiliados' => [],
            'nombre_normalizado' => $nombreNormalizado,
        ];
    }

    /**
     * Busca coincidencias por abreviación utilizando
     * los índices previamente construidos.
     *
     * Cada término debe coincidir con una palabra distinta del nombre,
     * pero no depende del orden en el que aparezca en el archivo.
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
         * TODOS los términos.
         */
        $candidatos = null;

        foreach ($partesCsv as $parteCsv) {

            /*
             * Una inicial puede coincidir con la inicial de cualquier palabra.
             */
            if (mb_strlen($parteCsv, 'UTF-8') === 1) {

                $coincidenciasTermino =
                    $this->indiceIniciales[$parteCsv]
                    ?? [];

            } else {

                /*
                 * Una palabra completa debe coincidir exactamente.
                 */
                $coincidenciasTermino =
                    $this->indicePalabras[$parteCsv]
                    ?? [];
            }

            /*
             * Si un solo término no tiene candidatos,
             * no existe ninguna coincidencia posible.
             */
            if (count($coincidenciasTermino) === 0) {
                return [];
            }

            /*
             * Primer término:
             * copiamos todos sus candidatos.
             */
            if ($candidatos === null) {

                $candidatos = $coincidenciasTermino;

                continue;
            }

            /*
             * INTERSECCIÓN REAL.
             *
             * Conservamos únicamente los IDs que también
             * aparecen con este término.
             */
            $candidatos = array_intersect_key(
                $candidatos,
                $coincidenciasTermino
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
     * Determina si todas las palabras del CSV pueden asignarse a palabras
     * distintas del nombre registrado, aceptando iniciales.
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

        if (count($partesCsv) > count($partesBd)) {
            return false;
        }

        return $this->asignarPartesNombre(
            $partesCsv,
            $partesBd,
            0,
            []
        );
    }

    private function asignarPartesNombre(
        array $partesCsv,
        array $partesBd,
        int $indice,
        array $usadas
    ): bool {
        if ($indice === count($partesCsv)) {
            return true;
        }

        $parteCsv = $partesCsv[$indice];

        foreach ($partesBd as $indiceBd => $parteBd) {
            if (isset($usadas[$indiceBd])) {
                continue;
            }

            $coincide = $parteCsv === $parteBd
                || (
                    mb_strlen($parteCsv, 'UTF-8') === 1
                    && str_starts_with($parteBd, $parteCsv)
                );

            if (!$coincide) {
                continue;
            }

            $usadas[$indiceBd] = true;

            if ($this->asignarPartesNombre(
                $partesCsv,
                $partesBd,
                $indice + 1,
                $usadas
            )) {
                return true;
            }

            unset($usadas[$indiceBd]);
        }

        return false;
    }

    private function coincidenPartesExactas(
        array $partesCsv,
        array $partesAfiliado
    ): bool {
        sort($partesCsv);
        sort($partesAfiliado);

        return $partesCsv === $partesAfiliado;
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

            foreach ($partesNombre as $parteNombre) {
                $this->indicePalabras[$parteNombre][$afiliado->id] = $afiliado;

                $inicial = mb_substr($parteNombre, 0, 1, 'UTF-8');
                $this->indiceIniciales[$inicial][$afiliado->id] = $afiliado;
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