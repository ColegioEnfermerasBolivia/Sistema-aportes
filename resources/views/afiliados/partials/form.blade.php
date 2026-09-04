@php

    $esEdicion = $modo === 'editar';

@endphp


<form
    method="POST"
    action="{{ $esEdicion
        ? route('afiliados.update', $afiliado)
        : route('afiliados.store')
    }}"
>

    @csrf


    @if ($esEdicion)

        @method('PUT')

    @endif


    <div>

        {{-- ========================================== --}}
        {{-- REGISTRO DEPARTAMENTAL --}}
        {{-- ========================================== --}}

        <div>

            <label for="{{ $esEdicion ? 'editar_registro' : 'registro_departamental' }}">
                Registro departamental
            </label>

            <input
                type="text"
                id="{{ $esEdicion ? 'editar_registro' : 'registro_departamental' }}"
                name="registro_departamental"
                value="{{ old(
                    'registro_departamental',
                    $esEdicion ? $afiliado->registro_departamental : ''
                ) }}"
                maxlength="30"
                required
            >

        </div>


        {{-- ========================================== --}}
        {{-- MATRÍCULA PROFESIONAL --}}
        {{-- ========================================== --}}

        <div>

            <label for="{{ $esEdicion ? 'editar_matricula' : 'matricula_profesional' }}">
                Matrícula profesional
            </label>

            <input
                type="text"
                id="{{ $esEdicion ? 'editar_matricula' : 'matricula_profesional' }}"
                name="matricula_profesional"
                value="{{ old(
                    'matricula_profesional',
                    $esEdicion ? $afiliado->matricula_profesional : ''
                ) }}"
                maxlength="30"
                required
            >

        </div>


        {{-- ========================================== --}}
        {{-- CI --}}
        {{-- ========================================== --}}

        <div>

            <label for="{{ $esEdicion ? 'editar_ci' : 'ci' }}">
                CI
            </label>

            <input
                type="text"
                id="{{ $esEdicion ? 'editar_ci' : 'ci' }}"
                name="ci"
                value="{{ old(
                    'ci',
                    $esEdicion ? $afiliado->ci : ''
                ) }}"
                maxlength="20"
                required
            >

        </div>


        {{-- ========================================== --}}
        {{-- COLEGIO --}}
        {{-- ========================================== --}}

        @if (
            auth()->user()->rol?->nombre === 'SUPER ADMIN'
            ||
            (
                auth()->user()->rol?->nombre === 'ADMIN'
                && !$esEdicion
            )
        )

            <div>

                <label for="{{ $esEdicion ? 'editar_colegio' : 'colegio_id' }}">
                    Colegio
                </label>

                <select
                    id="{{ $esEdicion ? 'editar_colegio' : 'colegio_id' }}"
                    name="colegio_id"
                    required
                >

                    <option value="">
                        Seleccione un colegio
                    </option>

                    @foreach ($colegios as $colegio)

                        <option
                            value="{{ $colegio->id }}"
                            @selected(
                                old(
                                    'colegio_id',
                                    $esEdicion
                                        ? $afiliado->colegio_id
                                        : ''
                                ) == $colegio->id
                            )
                        >
                            {{ $colegio->nombre }}
                        </option>

                    @endforeach

                </select>

            </div>

        @endif


        {{-- ========================================== --}}
        {{-- NOMBRES --}}
        {{-- ========================================== --}}

        <div>

            <label for="{{ $esEdicion ? 'editar_nombres' : 'nombres' }}">
                Nombres
            </label>

            <input
                type="text"
                id="{{ $esEdicion ? 'editar_nombres' : 'nombres' }}"
                name="nombres"
                value="{{ old(
                    'nombres',
                    $esEdicion ? $afiliado->nombres : ''
                ) }}"
                maxlength="100"
                required
            >

        </div>


        {{-- ========================================== --}}
        {{-- APELLIDOS --}}
        {{-- ========================================== --}}

        <div>

            <label for="{{ $esEdicion ? 'editar_apellidos' : 'apellidos' }}">
                Apellidos
            </label>

            <input
                type="text"
                id="{{ $esEdicion ? 'editar_apellidos' : 'apellidos' }}"
                name="apellidos"
                value="{{ old(
                    'apellidos',
                    $esEdicion ? $afiliado->apellidos : ''
                ) }}"
                maxlength="100"
                required
            >

        </div>


        {{-- ========================================== --}}
        {{-- CELULAR --}}
        {{-- ========================================== --}}

        <div>

            <label for="{{ $esEdicion ? 'editar_celular' : 'celular' }}">
                Celular
            </label>

            <input
                type="text"
                id="{{ $esEdicion ? 'editar_celular' : 'celular' }}"
                name="celular"
                value="{{ old(
                    'celular',
                    $esEdicion ? $afiliado->celular : ''
                ) }}"
                maxlength="20"
                required
            >

        </div>


        {{-- ========================================== --}}
        {{-- FECHA DE AFILIACIÓN --}}
        {{-- ========================================== --}}

        <div>

            <label for="{{ $esEdicion ? 'editar_fecha' : 'fecha_afiliacion' }}">
                Fecha de afiliación
            </label>

            <input
                type="date"
                id="{{ $esEdicion ? 'editar_fecha' : 'fecha_afiliacion' }}"
                name="fecha_afiliacion"
                value="{{ old(
                    'fecha_afiliacion',
                    $esEdicion
                        ? $afiliado->fecha_afiliacion?->format('Y-m-d')
                        : ''
                ) }}"
                required
            >

        </div>

    </div>


    <br>


    {{-- ========================================== --}}
    {{-- BOTONES --}}
    {{-- ========================================== --}}

    <div>

        <button type="submit">

            {{ $esEdicion
                ? 'Guardar cambios'
                : 'Registrar afiliado'
            }}

        </button>

    </div>

</form>