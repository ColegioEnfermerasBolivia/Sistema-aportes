<?php

namespace App\Http\Controllers;

use App\Exports\ReportePagosLoteExport;
use App\Models\LoteVerificacion;
use App\Services\LotePagoReporteService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportePagosLoteController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', LoteVerificacion::class);

        $datos = $request->validate([
            'codigo_lote' => ['nullable', 'string', 'max:100'],
            'gestion' => ['nullable', 'integer', 'digits:4'],
        ]);

        $consultaBase = $this->lotesAccesibles();
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
            compact('lotes', 'gestiones')
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
            abort_unless($usuario->colegio_id, 403);
            $consulta->where('colegio_id', $usuario->colegio_id);
        }

        return $consulta;
    }
}
