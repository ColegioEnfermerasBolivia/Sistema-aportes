<?php

namespace App\Http\Controllers;

use App\Models\Afiliado;
use App\Models\Aporte;
use App\Models\Colegio;
use App\Models\Saldo;
use Illuminate\Http\Request;

class AportesController extends Controller
{
    
    public function index(Request $request)
    {
        $usuario = auth()->user();

        /* Validación de filtros */

        $datos = $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'gestion' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'colegio_id' => ['nullable', 'integer', 'exists:colegios,id'],
        ]);

        $buscar = trim($datos['buscar'] ?? '');

        /*
        * Consulta base de afiliados.
        * Primero aplicamos los permisos y filtros, sin limitar todavía
        * por gestión, para determinar qué años corresponden a la búsqueda.
        */

        $query = Afiliado::query();

        /* Restricción para usuarios de colegio */

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

        /* Búsqueda por CI, nombres o apellidos */

        if ($buscar !== '') {

            $query->buscarPorTerminos($buscar);
        }

        /* Filtro por colegio para administradores */

        if (
            in_array($usuario->rol?->nombre, ['SUPER ADMIN', 'ADMIN'])
            && !empty($datos['colegio_id'])
        ) {
            $query->where('colegio_id', $datos['colegio_id']);
        }

        /*
        * Gestiones disponibles según las afiliadas que coinciden
        * con los filtros actuales.
        *
        * Solo se consideran aportes verificados.
        */

        $gestiones = Aporte::query()
            ->where('estado', 'VERIFICADO')
            ->whereIn('afiliado_id', (clone $query)->select('id'))
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($year) => (int) $year);

        /*
        * Mantener la gestión solicitada si corresponde a la búsqueda.
        * Si no corresponde, seleccionar la gestión más reciente disponible.
        * En la primera visita, se usa la gestión más reciente con aportes.
        */

        $gestionSolicitada = $datos['gestion'] ?? null;

        if (
            $gestionSolicitada !== null
            && $gestiones->contains((int) $gestionSolicitada)
        ) {
            $gestion = (int) $gestionSolicitada;
        } else {
            $gestion = $gestiones->first() ?? now()->year;
        }

        /*
        * Consulta final:
        * mostrar únicamente afiliadas con aportes verificados
        * en la gestión seleccionada.
        */

        $afiliados = (clone $query)
            ->where(function ($q) use ($gestion) {
                $q->whereHas('aportes', function ($aportes) use ($gestion) {
                    $aportes->where('year', $gestion)
                        ->where('estado', 'VERIFICADO');
                })->orWhereHas('saldosActivos');
            })
            ->with([
                'aportes' => function ($q) use ($gestion) {
                    $q->where('year', $gestion)
                        ->where('estado', 'VERIFICADO')
                        ->orderBy('mes');
                },
            ])
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate(15)
            ->withQueryString();

        /*
        * Último aporte histórico de cada afiliada de la página actual.
        */

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

        $saldoDisponiblePorAfiliado = Saldo::query()
            ->whereIn('afiliado_id', $idsAfiliados)
            ->where('estado', 'ACTIVO')
            ->where('monto_disponible', '>', 0)
            ->selectRaw(
                'afiliado_id, SUM(monto_disponible) as saldo_disponible'
            )
            ->groupBy('afiliado_id')
            ->pluck('saldo_disponible', 'afiliado_id');

        /* Colegios para el filtro */

        $colegios = collect();

        if (in_array($usuario->rol?->nombre, ['SUPER ADMIN', 'ADMIN'])) {

            $colegios = Colegio::where('estado', 'ACTIVO')
                ->orderBy('nombre')
                ->get();
        }

        return view('aportes.index', compact(
            'afiliados',
            'colegios',
            'gestiones',
            'gestion',
            'ultimoAportePorAfiliado',
            'saldoDisponiblePorAfiliado'
        ));
    }

}