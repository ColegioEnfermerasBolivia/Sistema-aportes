<?php

namespace App\Http\Controllers;

use App\Models\Afiliado;
use App\Models\Aporte;
use App\Models\Colegio;
use Illuminate\Http\Request;

class AportesController extends Controller
{
    public function index(Request $request)
    {
        $usuario = auth()->user();

        /*Validación de filtros*/

        $datos = $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'gestion' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'colegio_id' => ['nullable', 'integer', 'exists:colegios,id'],
        ]);

        $gestion = $datos['gestion'] ?? now()->year;

        /*Consulta principal*/

        $query = Afiliado::with([
            'aportes' => function ($query) use ($gestion) {
                $query
                    ->where('year', $gestion)
                    ->where('estado', 'VERIFICADO')
                    ->orderBy('mes');
            },
        ]);

        /*Restricción para usuarios de colegio*/

        if ($usuario->rol?->nombre === 'USUARIO') {

            $colegio = $usuario->colegio;

            if (!$colegio) {
                abort(403);
            }

            if ($colegio->tipo === 'DEPARTAMENTAL') {

                $colegiosPermitidos = $colegio->hijos
                    ->pluck('id')
                    ->push($colegio->id);

                $query->whereIn('colegio_id', $colegiosPermitidos);

            } else {
                $query->where('colegio_id', $colegio->id);
            }
        }

        /*Búsqueda*/

        if (!empty($datos['buscar'])) {

            $buscar = trim($datos['buscar']);

            $query->where(function ($q) use ($buscar) {
                $q->where('ci', 'like', "%{$buscar}%")
                    ->orWhere('nombres', 'like', "%{$buscar}%")
                    ->orWhere('apellidos', 'like', "%{$buscar}%");
            });
        }

        /*Filtro por colegio*/

        if (
            in_array($usuario->rol?->nombre, ['SUPER ADMIN', 'ADMIN'])
            && !empty($datos['colegio_id'])
        ) {
            $query->where('colegio_id', $datos['colegio_id']);
        }

        /*Afiliados paginados*/

        $afiliados = $query
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate(15)
            ->withQueryString();

        /*Ultimo aporte historico*/
        $idsAfiliados = $afiliados->pluck('id'); 
        $ultimoAportePorAfiliado = Aporte::query() 
            ->whereIn('afiliado_id', $idsAfiliados) 
            ->where('estado', 'VERIFICADO') 
            ->orderByDesc('year') 
            ->orderByDesc('mes') ->get() 
            ->groupBy('afiliado_id') 
            ->map(function ($aportes) { 
                return $aportes->first(); 
            });
        
        /*Colegios para el filtro */

        $colegios = collect();

        if (in_array($usuario->rol?->nombre, ['SUPER ADMIN', 'ADMIN'])) {

            $colegios = Colegio::where('estado', 'ACTIVO')
                ->orderBy('nombre')
                ->get();
        }

        /*Gestiones disponibles*/

        $gestiones = Aporte::query()
            ->where('estado', 'VERIFICADO')
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        if (!$gestiones->contains((int) $gestion)) {
            $gestiones->prepend((int) $gestion);
        }

        $idsAfiliados = $afiliados->pluck('id');

        $ultimoAportePorAfiliado = Aporte::query()
            ->whereIn('afiliado_id', $idsAfiliados)
            ->where('estado', 'VERIFICADO')
            ->orderByDesc('year')
            ->orderByDesc('mes')
            ->get()
            ->groupBy('afiliado_id')
            ->map(function ($aportes) {
                return $aportes->first();
            });

        return view('aportes.index', compact(
            'afiliados',
            'colegios',
            'gestiones',
            'gestion',
            'ultimoAportePorAfiliado'
        ));
    }
}