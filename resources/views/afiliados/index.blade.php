<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Afiliados</title>
    <script
        defer
        src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js">
    </script>
    <style>

        [x-cloak] {
            display: none !important;
        }

        .filtros {
            margin-bottom: 20px;
        }

        .filtros input,
        .filtros select {
            margin-right: 10px;
            padding: 6px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            padding: 8px;
            border: 1px solid #ccc;
        }

        th {
            background-color: #f2f2f2;
        }

        .acciones {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .badge {
            padding: 3px 7px;
            border-radius: 4px;
        }

        .badge-activo {
            background-color: #d4edda;
            color: #155724;
        }

        .badge-inactivo {
            background-color: #f8d7da;
            color: #721c24;
        }

        .mensaje {
            padding: 10px;
            margin-bottom: 15px;
            background-color: #d4edda;
            color: #155724;
        }
    </style>
</head>
    <body>
        <h1>Afiliados</h1>
        {{-- ========================================== --}}
        {{-- MENSAJE DE ÉXITO --}}
        {{-- ========================================== --}}
        @if (session('success'))
            <div class="mensaje">{{ session('success') }}</div>
        @endif

        {{-- ========================================== --}}
        {{-- ERRORES DE VALIDACIÓN --}}
        {{-- ========================================== --}}

        @if ($errors->any())
            <div>
                <strong>Se encontraron los siguientes errores:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ========================================== --}}
        {{-- AGREGAR AFILIADO --}}
        {{-- ========================================== --}}

        @if (in_array(auth()->user()->rol?->nombre, ['ADMIN', 'USUARIO']))
            <x-modal titulo="Registrar afiliado">
                <x-slot:trigger>
                    <button type="button">+ Agregar afiliado</button>
                </x-slot:trigger>

                @include('afiliados.partials.form', [
                    'modo' => 'crear',
                    'afiliado' => null
                ])
            </x-modal>
        @endif
        <br><br>

        {{-- ========================================== --}}
        {{-- FILTROS --}}
        {{-- ========================================== --}}
        <form
            method="GET"
            action="{{ route('afiliados.index') }}"
            class="filtros"
        >
            <label for="buscar">Buscar:</label>
            <input
                type="text"
                id="buscar"
                name="buscar"
                value="{{ request('buscar') }}"
                placeholder="CI, nombre o apellido"
            >

            {{-- FILTRO DE COLEGIO --}}
            @if (in_array(auth()->user()->rol?->nombre, ['ADMIN', 'SUPER ADMIN']))

                <label for="colegio_id">Colegio:</label>
                <select
                    id="colegio_id"
                    name="colegio_id"
                >
                    <option value="">Todos los colegios</option>
                    @foreach ($colegios as $colegio)
                        <option
                            value="{{ $colegio->id }}"
                            @selected(request('colegio_id') == $colegio->id)
                        >
                            {{ $colegio->nombre }}
                        </option>
                    @endforeach
                </select>
            @endif

            <button type="submit">Buscar</button>
            <a href="{{ route('afiliados.index') }}">Limpiar</a>
        </form>

        {{-- ========================================== --}}
        {{-- TABLA --}}
        {{-- ========================================== --}}
        <table>
            <thead>
                <tr>
                    <th>Registro departamental</th>
                    <th>Matrícula profesional</th>
                    <th>CI</th>
                    <th>Nombres</th>
                    <th>Apellidos</th>
                    <th>Colegio</th>
                    <th>Celular</th>
                    <th>Estado</th>
                    <th>Fecha afiliación</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($afiliados as $afiliado)
                    <tr>
                        <td>{{ $afiliado->registro_departamental }}</td>
                        <td>{{ $afiliado->matricula_profesional }}</td>
                        <td>{{ $afiliado->ci }}</td>
                        <td>{{ $afiliado->nombres }}</td>
                        <td>{{ $afiliado->apellidos }}</td>
                        <td>{{ $afiliado->colegio?->nombre ?? 'Sin colegio' }}</td>
                        <td>{{ $afiliado->celular }}</td>
                        <td>@if ($afiliado->estado === 'ACTIVO')
                                <span class="badge badge-activo">ACTIVO</span>
                            @else
                                <span class="badge badge-inactivo">INACTIVO</span>
                            @endif
                        </td>
                        <td>{{ $afiliado->fecha_afiliacion?->format('d/m/Y') }}</td>

                        {{-- ========================================== --}}
                        {{-- ACCIONES --}}
                        {{-- ========================================== --}}
                        <td>
                            <div class="acciones">
                                {{-- ============================== --}}
                                {{-- VER INFORMACIÓN --}}
                                {{-- ============================== --}}
                                <x-modal titulo="Información del afiliado">
                                    <x-slot:trigger>
                                        <button type="button">Ver</button>
                                    </x-slot:trigger>

                                    @include('afiliados.partials.info', [
                                        'afiliado' => $afiliado
                                    ])
                                </x-modal>

                                {{-- ============================== --}}
                                {{-- EDITAR --}}
                                {{-- ============================== --}}

                                @if (in_array(auth()->user()->rol?->nombre, [
                                    'SUPER ADMIN',
                                    'ADMIN',
                                    'USUARIO'
                                ]))
                                    <x-modal titulo="Editar afiliado">
                                        <x-slot:trigger>
                                            <button type="button">Editar</button>
                                        </x-slot:trigger>

                                        @include('afiliados.partials.form', [
                                            'modo' => 'editar',
                                            'afiliado' => $afiliado
                                        ])
                                    </x-modal>
                                @endif

                                {{-- ============================== --}}
                                {{-- ACTIVAR / DESACTIVAR --}}
                                {{-- ============================== --}}

                                @if (auth()->user()->rol?->nombre === 'ADMIN')
                                    <form
                                        method="POST"
                                        action="{{ route('afiliados.estado', $afiliado) }}"
                                        style="display: inline;"
                                        onsubmit="return confirm(
                                            '¿Está seguro de cambiar el estado de este afiliado?'
                                        );"
                                    >
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit">
                                            {{ $afiliado->estado === 'ACTIVO'
                                                ? 'Desactivar'
                                                : 'Activar'
                                            }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10">No se encontraron afiliados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </body>
</html>