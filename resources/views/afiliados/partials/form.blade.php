@php
    $esEdicion = $modo === 'editar';
    $usuario = auth()->user();
    $esUsuarioColegioPadre = $usuario->rol?->nombre === 'USUARIO'
        && $usuario->colegio?->tipo === 'DEPARTAMENTAL'
        && $colegios->count() > 1;
@endphp

<form
    method="POST"
    action="{{ $esEdicion
        ? route('afiliados.update', $afiliado)
        : route('afiliados.store')
    }}"
    class="space-y-6"
>
    @csrf
    @if ($esEdicion)
        @method('PUT')
    @endif

    {{--  DATOS DE IDENTIFICACIÓN --}}

    <div>
        <h3 class="mb-5 text-base font-semibold text-[#0d1b6e]">
            Datos de identificación
        </h3>
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

            {{-- REGISTRO DEPARTAMENTAL --}}
            <div>
                <label
                    for="{{ $esEdicion ? 'editar_registro' : 'registro_departamental' }}"
                    class="mb-2 block text-sm font-medium text-gray-700"
                >
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
                    placeholder="Ej. LP-000123"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-3 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-[#1a34c9] focus:ring-2 focus:ring-[#1a34c9]/10"
                >

                @error('registro_departamental')
                    <p class="mt-1.5 text-xs text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>


            {{-- MATRÍCULA PROFESIONAL --}}
            <div>
                <label
                    for="{{ $esEdicion ? 'editar_matricula' : 'matricula_profesional' }}"
                    class="mb-2 block text-sm font-medium text-gray-700"
                >
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
                    placeholder="Ej. MAT-0000"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-3 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-[#1a34c9] focus:ring-2 focus:ring-[#1a34c9]/10"
                >

                @error('matricula_profesional')
                    <p class="mt-1.5 text-xs text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- CI --}}
            <div>
                <label
                    for="{{ $esEdicion ? 'editar_ci' : 'ci' }}"
                    class="mb-2 block text-sm font-medium text-gray-700"
                >
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
                    placeholder="Número de CI"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-3 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-[#1a34c9] focus:ring-2 focus:ring-[#1a34c9]/10"
                >

                @error('ci')
                    <p class="mt-1.5 text-xs text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>


            {{-- CELULAR --}}
            <div>
                <label
                    for="{{ $esEdicion ? 'editar_celular' : 'celular' }}"
                    class="mb-2 block text-sm font-medium text-gray-700"
                >
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
                    placeholder="Ej. 70000000"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-3 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-[#1a34c9] focus:ring-2 focus:ring-[#1a34c9]/10"
                >

                @error('celular')
                    <p class="mt-1.5 text-xs text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>
        </div>
    </div>


    {{-- DATOS PERSONALES --}}
    <div>
        <h3 class="mb-5 text-base font-semibold text-[#0d1b6e]">
            Datos personales
        </h3>
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

            {{-- NOMBRES --}}
            <div>
                <label
                    for="{{ $esEdicion ? 'editar_nombres' : 'nombres' }}"
                    class="mb-2 block text-sm font-medium text-gray-700"
                >
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
                    placeholder="Nombres"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-3 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-[#1a34c9] focus:ring-2 focus:ring-[#1a34c9]/10"
                >
                @error('nombres')
                    <p class="mt-1.5 text-xs text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- APELLIDOS --}}
            <div>
                <label
                    for="{{ $esEdicion ? 'editar_apellidos' : 'apellidos' }}"
                    class="mb-2 block text-sm font-medium text-gray-700"
                >
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
                    placeholder="Apellidos"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-3 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-[#1a34c9] focus:ring-2 focus:ring-[#1a34c9]/10"
                >
                @error('apellidos')
                    <p class="mt-1.5 text-xs text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>
        </div>
    </div>

{{-- COLEGIO --}}
    @if (
        auth()->user()->rol?->nombre === 'SUPER ADMIN'
        ||
        (
            auth()->user()->rol?->nombre === 'ADMIN'
            && !$esEdicion
        )
        ||
        (
            $esUsuarioColegioPadre
            && !$esEdicion
        )
    )
        <div>
            <h3 class="mb-5 text-base font-semibold text-[#0d1b6e]">
                Colegio
            </h3>

            <label
                for="{{ $esEdicion ? 'editar_colegio' : 'colegio_id' }}"
                class="mb-2 block text-sm font-medium text-gray-700"
            >
                Colegio
            </label>

            <select
                id="{{ $esEdicion ? 'editar_colegio' : 'colegio_id' }}"
                name="colegio_id"
                data-colegio-original="{{ $esEdicion ? $afiliado->colegio_id : '' }}"
                required
                class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-3 text-sm text-gray-900 outline-none transition focus:border-[#1a34c9] focus:ring-2 focus:ring-[#1a34c9]/10"
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

            @error('colegio_id')
                <p class="mt-1.5 text-xs text-red-600">
                    {{ $message }}
                </p>
            @enderror

            {{-- FECHA DE TRANSFERENCIA --}}
            @if ($esEdicion && auth()->user()->rol?->nombre === 'SUPER ADMIN')
                <div
                    id="contenedor_fecha_transferencia"
                    class="mt-5 hidden"
                >
                    <label
                        for="fecha_transferencia"
                        class="mb-2 block text-sm font-medium text-gray-700"
                    >
                        Fecha de transferencia
                    </label>

                    <input
                        type="date"
                        id="fecha_transferencia"
                        name="fecha_transferencia"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-3 text-sm text-gray-900 outline-none transition focus:border-[#1a34c9] focus:ring-2 focus:ring-[#1a34c9]/10"
                    >

                    <p class="mt-1.5 text-xs text-gray-500">
                        Indique la fecha en que se realizó efectivamente
                        la transferencia de la afiliada al nuevo colegio.
                    </p>

                    @error('fecha_transferencia')
                        <p class="mt-1.5 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>
            @endif
        </div>
    @endif


    {{-- FECHA DE AFILIACIÓN --}}
    <div>
        <label
            for="{{ $esEdicion ? 'editar_fecha' : 'fecha_afiliacion' }}"
            class="mb-2 block text-sm font-medium text-gray-700"
        >
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
            class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-3 text-sm text-gray-900 outline-none transition focus:border-[#1a34c9] focus:ring-2 focus:ring-[#1a34c9]/10"
        >
        @error('fecha_afiliacion')
            <p class="mt-1.5 text-xs text-red-600">
                {{ $message }}
            </p>
        @enderror
    </div>

    {{-- OBSERVACIÓN --}}
    <div>
        <label
            for="{{ $esEdicion ? 'editar_observacion' : 'observacion' }}"
            class="mb-2 block text-sm font-medium text-gray-700"
        >
            Observación
        </label>
        <textarea
            id="{{ $esEdicion ? 'editar_observacion' : 'observacion' }}"
            name="observacion"
            rows="3"
            maxlength="5000"
            placeholder="Agregue una observación (opcional)"
            class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-3 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-[#1a34c9] focus:ring-2 focus:ring-[#1a34c9]/10"
        >{{ old('observacion') }}</textarea>
        <p class="mt-1.5 text-xs text-gray-500">
            En la edición, esta observación se agregará al historial sin eliminar las anteriores.
        </p>
        @error('observacion')
            <p class="mt-1.5 text-xs text-red-600">
                {{ $message }}
            </p>
        @enderror
    </div>
    
    {{-- BOTONES --}}
    <div class="flex justify-end gap-3 border-t border-gray-200 pt-5">
        <button
            type="submit"
            @class([
                'inline-flex items-center gap-1.5 rounded-lg px-6 py-3 text-sm font-semibold text-white shadow-sm transition focus:outline-none focus:ring-2 hover:cursor-pointer',
                'bg-green-600 hover:bg-green-700 focus:ring-green-500/20' => $esEdicion,
                'bg-[#2563EB] hover:bg-[#2563EB] focus:ring-[#1a34c9]/20' => !$esEdicion,
            ])
            >
            @if ($esEdicion === true)
                <x-icon name="save" size="25"/>
            @else
                <x-icon name="edit" size="25"/>
            @endif

            {{ $esEdicion ? 'Guardar cambios' : 'Registrar afiliado'}}
        </button>
    </div>
</form>