<?php

namespace App\Services\Importaciones;

use App\Models\Afiliado;
use Illuminate\Support\Collection;

class CiMatcher
{
    /**
     * Construye un índice de afiliados utilizando su CI.
     *
     * La clave del índice es el CI normalizado y el valor
     * es el afiliado correspondiente.
     */
    public function construirIndiceAfiliados(): Collection
    {
        /**
         * @var Collection<int, Afiliado> $afiliados
         */
        $afiliados = Afiliado::query()
            ->select([
                'id',
                'ci',
                'nombres',
                'apellidos',
                'estado',
            ])
            ->whereNotNull('ci')
            ->get();

        return $afiliados
            ->filter(function (Afiliado $afiliado) {
                return $this->normalizarCi($afiliado->ci) !== null;
            })
            ->keyBy(function (Afiliado $afiliado) {
                return $this->normalizarCi($afiliado->ci);
            });
    }

    /**
     * Busca un CI dentro del índice de afiliados.
     */
    public function buscar(
        string|int|null $ci,
        Collection $indiceAfiliados
    ): ?Afiliado {
        $ciNormalizado = $this->normalizarCi($ci);

        if ($ciNormalizado === null) {
            return null;
        }

        return $indiceAfiliados->get($ciNormalizado);
    }

    /**
     * Normaliza un CI para poder compararlo.
     *
     * Actualmente:
     * - convierte a string
     * - elimina espacios
     * - elimina guiones
     * - convierte a mayúsculas
     */
    public function normalizarCi(string|int|null $ci): ?string
    {
        if ($ci === null) {
            return null;
        }

        $ci = trim((string) $ci);

        if ($ci === '') {
            return null;
        }

        $ci = strtoupper($ci);

        $ci = str_replace(
            [' ', '-'],
            '',
            $ci
        );

        return $ci !== ''
            ? $ci
            : null;
    }

    /**
     * Compara un CI del archivo contra un CI de la base de datos.
     */
    public function coinciden(
        string|int|null $ciArchivo,
        string|int|null $ciBaseDatos
    ): bool {
        $ciArchivo = $this->normalizarCi($ciArchivo);
        $ciBaseDatos = $this->normalizarCi($ciBaseDatos);

        if (
            $ciArchivo === null ||
            $ciBaseDatos === null
        ) {
            return false;
        }

        return $ciArchivo === $ciBaseDatos;
    }
}