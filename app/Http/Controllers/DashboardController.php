<?php

namespace App\Http\Controllers;

use App\Models\Aporte;
use App\Models\Afiliado;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Afiliado::class);

        $usuario = auth()->user();
        $esAdministrador = in_array($usuario->rol->nombre, [
            'ADMIN',
            'SUPER ADMIN',
        ]);

        $colegiosPermitidos = null;

        if ($usuario->rol?->nombre === 'USUARIO') {

            $colegio = $usuario->colegio;

            if (!$colegio) {
                abort(403);
            }

            if ($colegio->tipo === 'DEPARTAMENTAL') {

                $colegiosPermitidos = $colegio->hijos
                    ->pluck('id')
                    ->push($colegio->id);

            } else {

                $colegiosPermitidos = collect([
                    $colegio->id,
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Afiliadas activas
        |--------------------------------------------------------------------------
        */

        $afiliadasActivasQuery = Afiliado::query()
            ->where('estado', 'ACTIVO');

        if ($colegiosPermitidos) {
            $afiliadasActivasQuery->whereIn(
                'colegio_id',
                $colegiosPermitidos
            );
        }

        $afiliadasActivas = $afiliadasActivasQuery->count();

        /*
        |--------------------------------------------------------------------------
        | Afiliadas con especialidad
        |--------------------------------------------------------------------------
        */

        $afiliadasConEspecialidadQuery = Afiliado::query()
            ->whereHas('afiliadosPostgrados');

        if ($colegiosPermitidos) {
            $afiliadasConEspecialidadQuery->whereIn(
                'colegio_id',
                $colegiosPermitidos
            );
        }

        $afiliadasConEspecialidad =
            $afiliadasConEspecialidadQuery->count();

        /* Afiliadas por Colegio */
        $afiliadasPorColegioQuery = Afiliado::query()
            ->selectRaw('colegio_id, COUNT(*) as total')
            ->with('colegio')
            ->groupBy('colegio_id');
        
        if ($colegiosPermitidos) {
            $afiliadasPorColegioQuery->whereIn(
                'colegio_id',
                $colegiosPermitidos
            );
        }
        $afiliadasPorColegio = $afiliadasPorColegioQuery
            ->get()
            ->sortByDesc('total')
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Gestión actual
        |--------------------------------------------------------------------------
        */

        $year = now()->year;

        /*
        |--------------------------------------------------------------------------
        | Aportes verificados por mes
        |--------------------------------------------------------------------------
        */

        $aportesPorMesQuery = Aporte::query()
            ->where('estado', 'VERIFICADO')
            ->where('year', $year);

        if ($colegiosPermitidos) {
            $aportesPorMesQuery->whereHas(
                'afiliado',
                function ($query) use ($colegiosPermitidos) {
                    $query->whereIn(
                        'colegio_id',
                        $colegiosPermitidos
                    );
                }
            );
        }

        $aportesPorMes = $aportesPorMesQuery
            ->selectRaw('mes, SUM(monto) as total')
            ->groupBy('mes')
            ->orderBy('mes')
            ->get()
            ->keyBy('mes');
        
        /* Aportes Totales por colegio */
        $aportesPorColegioQuery = Aporte::query()
            ->where('estado', 'VERIFICADO')
            ->where('year', $year)
            ->whereHas('afiliado')
            ->with('afiliado.colegio');
        
        if ($colegiosPermitidos) {
            $aportesPorColegioQuery->whereHas(
                'afiliado',
                function ($query) use ($colegiosPermitidos) {
                    $query->whereIn('colegio_id', $colegiosPermitidos);
                }
            );
        }

        $aportesPorColegio = $aportesPorColegioQuery
            ->get()
            ->groupBy(function ($aporte) {
                return $aporte->afiliado->colegio_id;
            })
            ->map(function ($aportes) {
                return [
                    'colegio' => $aportes->first()->afiliado->colegio?->nombre
                        ?? 'Sin Colegio',
                    'total' => $aportes->sum('monto')
                ];
            })
            ->sortByDesc('total')
            ->values();

        return view('dashboard', compact(
            'usuario',
            'esAdministrador',
            'afiliadasActivas',
            'afiliadasConEspecialidad',
            'aportesPorMes',
            'afiliadasPorColegio',
            'aportesPorColegio',
            'year'
        ));
    }
}