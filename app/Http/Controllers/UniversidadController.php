<?php

namespace App\Http\Controllers;

use App\Http\Requests\UniversidadRequest;
use App\Models\Universidad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UniversidadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Universidad::class);

        $universidades = Universidad::query()
            ->when(
                $request->filled('buscar'),
                fn ($query) => $query->where(
                    'nombre',
                    'like',
                    '%'.trim((string) $request->query('buscar')).'%'
                )
            )
            ->orderBy('nombre')
            ->paginate(50)
            ->withQueryString();

        return response()->json($universidades);
    }

    public function show(Universidad $universidad): JsonResponse
    {
        Gate::authorize('view', $universidad);

        return response()->json([
            'data' => $universidad->load(
                'formaciones.egresada',
                'formaciones.departamento'
            ),
        ]);
    }

    public function store(
        UniversidadRequest $request
    ): JsonResponse|RedirectResponse
    {
        Gate::authorize('create', Universidad::class);

        $universidad = Universidad::create($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['data' => $universidad], 201);
        }

        return redirect()
            ->route('egresadas.index')
            ->with('success', 'Universidad registrada correctamente.');
    }

    public function update(
        UniversidadRequest $request,
        Universidad $universidad
    ): JsonResponse|RedirectResponse {
        Gate::authorize('update', $universidad);

        $universidad->update($request->validated());

        $universidad->refresh();

        if ($request->expectsJson()) {
            return response()->json(['data' => $universidad]);
        }

        return redirect()
            ->route('egresadas.index')
            ->with('success', 'Universidad actualizada correctamente.');
    }
}
