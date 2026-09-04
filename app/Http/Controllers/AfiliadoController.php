<?php

namespace App\Http\Controllers;

use App\Models\Afiliado;
use App\Models\Colegio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AfiliadoController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Afiliado::class);

        $usuario = auth()->user();

        $query = Afiliado::with('colegio');

        if ($usuario->rol?->nombre === 'USUARIO') {

            $colegio = $usuario->colegio;

            if (!$colegio) {
                abort(403);
            }

            if ($colegio->tipo === 'DEPARTAMENTAL') {

                $colegiosPermitidos = $colegio->hijos
                    ->pluck('id')
                    ->push($colegio->id);

                $query->whereIn(
                    'colegio_id',
                    $colegiosPermitidos
                );

            } else {

                $query->where(
                    'colegio_id',
                    $colegio->id
                );
            }
        }

        if ($request->filled('buscar')) {

            $buscar = trim(
                $request->input('buscar')
            );

            $query->where(function ($q) use ($buscar) {

                $q->where(
                    'ci',
                    'like',
                    "%{$buscar}%"
                )
                ->orWhere(
                    'nombres',
                    'like',
                    "%{$buscar}%"
                )
                ->orWhere(
                    'apellidos',
                    'like',
                    "%{$buscar}%"
                );
            });
        }

        if (
            in_array($usuario->rol?->nombre, [
                'SUPER ADMIN',
                'ADMIN',
            ])
            && $request->filled('colegio_id')
        ) {

            $query->where(
                'colegio_id',
                $request->input('colegio_id')
            );
        }

        $afiliados = $query
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        $colegios = collect();

        if (
            in_array($usuario->rol?->nombre, [
                'SUPER ADMIN',
                'ADMIN',
            ])
        ) {

            $colegios = Colegio::where(
                'estado',
                'ACTIVO'
            )
            ->orderBy('nombre')
            ->get();
        }

        return view(
            'afiliados.index',
            compact(
                'afiliados',
                'colegios'
            )
        );
    }

    public function store(Request $request)
    {
        Gate::authorize(
            'create',
            Afiliado::class
        );

        $usuario = auth()->user();
        $reglas = [

            'registro_departamental' => [
                'required',
                'string',
                'max:30',
                'unique:afiliados,registro_departamental',
            ],

            'matricula_profesional' => [
                'required',
                'string',
                'max:30',
                'unique:afiliados,matricula_profesional',
            ],

            'ci' => [
                'required',
                'string',
                'max:20',
                'unique:afiliados,ci',
            ],

            'nombres' => [
                'required',
                'string',
                'max:100',
            ],

            'apellidos' => [
                'required',
                'string',
                'max:100',
            ],

            'celular' => [
                'required',
                'string',
                'max:20',
            ],

            'fecha_afiliacion' => [
                'required',
                'date',
            ],
        ];

        if ($usuario->rol?->nombre === 'ADMIN') {

            $reglas['colegio_id'] = [
                'required',
                Rule::exists('colegios', 'id')
                    ->where(function ($query) {
                        $query->where(
                            'estado',
                            'ACTIVO'
                        );
                    }),
            ];
        }

        $datos = $request->validate($reglas);

        if ($usuario->rol?->nombre === 'USUARIO') {

            if (!$usuario->colegio_id) {
                abort(403);
            }

            $datos['colegio_id'] = $usuario->colegio_id;
        }

        Gate::authorize(
            'createInColegio',
            [
                Afiliado::class,
                $datos['colegio_id']
            ]
        );

        $datos['estado'] = 'ACTIVO';

        Afiliado::create($datos);

        return redirect()
            ->route('afiliados.index')
            ->with(
                'success',
                'Afiliado registrado correctamente.'
            );
    }

    public function update(
        Request $request,
        Afiliado $afiliado
    ) {
        Gate::authorize(
            'update',
            $afiliado
        );

        $usuario = auth()->user();
        $datos = $request->validate([

            'registro_departamental' => [
                'required',
                'string',
                'max:30',
                Rule::unique(
                    'afiliados',
                    'registro_departamental'
                )->ignore($afiliado->id),
            ],

            'matricula_profesional' => [
                'required',
                'string',
                'max:30',
                Rule::unique(
                    'afiliados',
                    'matricula_profesional'
                )->ignore($afiliado->id),
            ],

            'ci' => [
                'required',
                'string',
                'max:20',
                Rule::unique(
                    'afiliados',
                    'ci'
                )->ignore($afiliado->id),
            ],

            'nombres' => [
                'required',
                'string',
                'max:100',
            ],

            'apellidos' => [
                'required',
                'string',
                'max:100',
            ],

            'celular' => [
                'required',
                'string',
                'max:20',
            ],

            'fecha_afiliacion' => [
                'required',
                'date',
            ],
        ]);

        if ($usuario->rol?->nombre === 'SUPER ADMIN') {
                $request->validate([
                    'colegio_id' => [
                        'required',
                        Rule::exists('colegios', 'id')
                            ->where(function ($query) {
                                $query->where(
                                    'estado',
                                    'ACTIVO'
                                );
                            }),
                    ],
                ]);

                $datos['colegio_id'] = $request->input(
                    'colegio_id'
                );

            } else {
                $datos['colegio_id'] = $afiliado->colegio_id;
            }

        $datos['estado'] = $afiliado->estado;
        $afiliado->update($datos);

        return redirect()
            ->route('afiliados.index')
            ->with(
                'success',
                'Afiliado actualizado correctamente.'
            );
    }

    public function cambiarEstado(
        Afiliado $afiliado
    ) {
        Gate::authorize(
            'changeEstado',
            $afiliado
        );

        $afiliado->estado = $afiliado->estado === 'ACTIVO'
            ? 'INACTIVO'
            : 'ACTIVO';

        $afiliado->save();

        return redirect()
            ->route('afiliados.index')
            ->with(
                'success',
                $afiliado->estado === 'ACTIVO'
                    ? 'Afiliado activado correctamente.'
                    : 'Afiliado desactivado correctamente.'
            );
    }
}