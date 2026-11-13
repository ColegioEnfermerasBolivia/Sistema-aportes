<?php

namespace App\Http\Controllers;

use App\Exports\ReportePagosLoteExport;
use App\Models\Colegio;
use App\Models\LoteVerificacion;
use App\Services\LotePagoReporteService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportePagosLoteController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', LoteVerificacion::class);

        $colegios = $this->colegiosDisponibles();
        $datos = $request->validate([
            'codigo_lote' => ['nullable', 'string', 'max:100'],
            'gestion' => ['nullable', 'integer', 'digits:4'],
            'colegio_id' => [
                'nullable',
                'integer',
                Rule::in($colegios->pluck('id')->all()),
            ],
        ]);

        $consultaBase = $this->lotesAccesibles();
        if (!empty($datos['colegio_id'])) {
            $consultaBase->where('colegio_id', $datos['colegio_id']);
        }

        $gestiones = (clone $consultaBase)
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        $consulta = (clone $consultaBase)->with('colegio');
        $codigoLote = trim($datos['codigo_lote'] ?? '');

        if ($codigoLote !== '') {
            $consulta->where('codigo_lote', 'like', "%{$codigoLote}%");
        }

        if (! empty($datos['gestion'])) {
            $consulta->where('year', $datos['gestion']);
        }

        $lotes = $consulta
            ->orderByDesc('year')
            ->orderByDesc('mes')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view(
            'reports.lotesReports.index',
            compact('lotes', 'gestiones', 'colegios')
        );
    }

    public function preview(
        LoteVerificacion $lote,
        LotePagoReporteService $reporteService
    ): View {
        Gate::authorize('viewReport', $lote);

        return view(
            'reports.lotesReports.preview',
            ['reporte' => $reporteService->construir($lote, Auth::user())]
        );
    }

    public function excel(
        LoteVerificacion $lote,
        LotePagoReporteService $reporteService
    ): BinaryFileResponse {
        Gate::authorize('viewReport', $lote);

        return Excel::download(
            new ReportePagosLoteExport(
                $reporteService->construir($lote, Auth::user())
            ),
            "reporte-pagos-lote-{$lote->id}.xlsx"
        );
    }

    private function lotesAccesibles(): Builder
    {
        $consulta = LoteVerificacion::query();
        $usuario = Auth::user();

        if ($usuario->rol?->nombre === 'USUARIO') {
            $colegios = $this->colegiosDisponibles();
            $consulta->whereIn('colegio_id', $colegios->pluck('id'));
        }

        return $consulta;
    }

    /**
     * @return Collection<int, Colegio>
     */
    private function colegiosDisponibles(): Collection
    {
        $usuario = Auth::user();

        if (in_array($usuario->rol?->nombre, ['ADMIN', 'SUPER ADMIN'])) {
            return Colegio::where('estado', 'ACTIVO')
                ->orderBy('nombre')
                ->get();
        }

        $colegio = $usuario->colegio;
        abort_unless($colegio, 403);

        if ($colegio->tipo !== 'DEPARTAMENTAL') {
            return collect([$colegio]);
        }

        return Colegio::query()
            ->where('colegio_padre_id', $colegio->id)
            ->orderBy('nombre')
            ->get()
            ->prepend($colegio);
    }
}
