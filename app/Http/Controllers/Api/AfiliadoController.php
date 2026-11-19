<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Afiliado;
use App\Models\Colegio;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AfiliadoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filtros = $request->validate([
            'nombres' => ['required', 'string', 'min:3', 'max:100'],
            'apellidos' => ['required', 'string', 'min:3', 'max:100'],
            'departamento' => ['nullable', 'string', 'min:3', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:30'],
        ], [
            'nombres.required' => 'Indica los nombres.',
            'apellidos.required' => 'Indica los apellidos.',
        ]);

        $nombres = trim($filtros['nombres']);
        $apellidos = trim($filtros['apellidos']);
        $departamento = trim((string) ($filtros['departamento'] ?? ''));
        $porPagina = (int) ($filtros['per_page'] ?? 30);

        $afiliados = Afiliado::query()
            ->select(['id', 'nombres', 'apellidos', 'colegio_id'])
            ->where('estado', 'ACTIVO')
            ->with([
                'colegio:id,nombre,tipo,colegio_padre_id',
                'colegio.padre:id,nombre',
                // with() entrega la RELACIÓN (Relation), no un Builder.
                'afiliadosPostgrados' => static function (Relation $relacion): void {
                    $relacion
                        ->whereHas('gradoAcademico', static function (Builder $grado): void {
                            $grado->where('nombre', 'ESPECIALIDAD')
                                ->where('estado', 'ACTIVO');
                        })
                        ->whereHas('postgrado', static function (Builder $postgrado): void {
                            $postgrado->where('estado', 'ACTIVO');
                        })
                        ->with('postgrado:id,nombre');
                },
            ])
            ->tap(static fn (Builder $query) => self::coincidePalabras($query, 'nombres', $nombres))
            ->tap(static fn (Builder $query) => self::coincidePalabras($query, 'apellidos', $apellidos))
            ->when($departamento !== '', static function (Builder $query) use ($departamento): void {
                self::filtrarPorDepartamento($query, $departamento);
            })
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->orderBy('id') // desempate: mantiene estable la paginación
            ->simplePaginate($porPagina)
            ->through(static fn (Afiliado $afiliado): array => [
                'nombres' => $afiliado->nombres,
                'apellidos' => $afiliado->apellidos,
                'especialidad' => $afiliado->afiliadosPostgrados
                    ->pluck('postgrado.nombre')
                    ->filter()
                    ->unique()
                    ->values(),
                'departamento' => self::departamento($afiliado->colegio),
            ]);

        return response()->json(['data' => $afiliados->items()]);
    }

    /**
     * Cada palabra del texto debe aparecer en la columna indicada.
     * La columna la define el código (nunca el usuario).
     */
    private static function coincidePalabras(Builder $query, string $columna, string $texto): void
    {
        foreach (preg_split('/\s+/u', $texto, -1, PREG_SPLIT_NO_EMPTY) as $palabra) {
            // addcslashes escapa % y _ para que no actúen como comodines
            $query->where($columna, 'like', '%'.addcslashes($palabra, '%_\\').'%');
        }
    }

    /**
     * El departamento es el nombre del colegio, o el del colegio padre
     * cuando el colegio es REGIONAL (igual que en departamento()).
     */
    private static function filtrarPorDepartamento(Builder $query, string $texto): void
    {
        $patron = '%'.addcslashes($texto, '%_\\').'%';

        $query->whereHas('colegio', static function (Builder $colegio) use ($patron): void {
            $colegio->where(static function (Builder $q) use ($patron): void {
                $q->where(static function (Builder $propio) use ($patron): void {
                    $propio->where('tipo', '!=', 'REGIONAL')
                        ->where('nombre', 'like', $patron);
                })->orWhere(static function (Builder $regional) use ($patron): void {
                    $regional->where('tipo', 'REGIONAL')
                        ->whereHas('padre', static function (Builder $padre) use ($patron): void {
                            $padre->where('nombre', 'like', $patron);
                        });
                });
            });
        });
    }

    private static function departamento(?Colegio $colegio): ?string
    {
        $nombre = $colegio?->tipo === 'REGIONAL'
            ? $colegio->padre?->nombre
            : $colegio?->nombre;

        if ($nombre === null) {
            return null;
        }

        return preg_replace('/^Colegio Departamental de\s+/iu', '', $nombre) ?? $nombre;
    }
}