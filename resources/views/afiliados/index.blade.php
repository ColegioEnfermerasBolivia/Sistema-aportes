@extends('layouts.app')
@section('title', 'Afiliados')
@section('content')

<div class="min-h-screen bg-[#f7f7f9] px-6 py-8 lg:px-12">

    {{-- ENCABEZADO --}}
    <div class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="m-0 text-3xl font-semibold tracking-tight text-gray-900">
                Gestión de Afiliados
            </h1>
            <p class="mt-2 text-base text-gray-600">
                Administre la información de los afiliados del Colegio de Enfermeras.
            </p>
        </div>


        {{-- AGREGAR AFILIADO --}}
        @if (in_array(auth()->user()->rol?->nombre, ['ADMIN', 'USUARIO']))
            <x-modal titulo="Registrar afiliado">
                <x-slot:trigger>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2
                               rounded-full
                               bg-[#1a34c9]
                               px-7 py-3
                               text-sm font-semibold text-white
                               shadow-[0_5px_14px_rgba(26,52,201,0.25)]
                               transition
                               hover:bg-[#1429a3]
                               focus:outline-none
                               focus:ring-2
                               focus:ring-[#1a34c9]
                               focus:ring-offset-2"
                    >
                        <span class="text-xl leading-none">
                            +
                        </span>
                        Agregar afiliado
                    </button>
                </x-slot:trigger>
                @include('afiliados.partials.form', [
                    'modo' => 'crear',
                    'afiliado' => null
                ])
            </x-modal>
        @endif
    </div>

    {{-- MENSAJE DE ÉXITO --}}
    @if (session('success'))
        <div
            class="mb-6 rounded-xl border border-green-200
                   bg-green-50 px-5 py-4
                   text-sm font-medium text-green-700"
        >
            {{ session('success') }}
        </div>
    @endif

    {{-- ERRORES DE VALIDACIÓN --}}

    @if ($errors->any())
        <div
            class="mb-6 rounded-xl border border-red-200
                   bg-red-50 px-5 py-4 text-sm text-red-700"
        >
            <p class="font-semibold">
                Se encontraron los siguientes errores:
            </p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- CONTENEDOR PRINCIPAL --}}
    <div
        class="overflow-hidden rounded-2xl
               border border-gray-200
               bg-white
               shadow-sm"
    >

        {{-- FILTROS --}}
        <div
            class="border-b border-gray-200
                   bg-white
                   px-6 py-5"
        >
            <form
                method="GET"
                action="{{ route('afiliados.index') }}"
                class="flex flex-col gap-4 lg:flex-row lg:items-end"
            >
                {{-- BUSCAR --}}
                <div class="flex-1">
                    <label
                        for="buscar"
                        class="mb-2 block text-sm font-medium text-gray-800"
                    >
                        Buscar afiliado
                    </label>
                    <div class="relative">
                        <span
                            class="pointer-events-none absolute inset-y-0 left-4
                                   flex items-center
                                   text-gray-400"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m21 21-4.35-4.35m1.35-5.4a6.75 6.75 0 1 1-13.5 0 6.75 6.75 0 0 1 13.5 0Z"
                                />
                            </svg>
                        </span>

                        <input
                            type="text"
                            id="buscar"
                            name="buscar"
                            value="{{ request('buscar') }}"
                            placeholder="CI, nombre o apellido"
                            class="w-full rounded-lg
                                   border border-gray-300
                                   bg-white
                                   py-3 pl-11 pr-4
                                   text-sm text-gray-900
                                   outline-none
                                   transition
                                   placeholder:text-gray-400
                                   focus:border-[#1a34c9]
                                   focus:ring-2
                                   focus:ring-[#1a34c9]/15"
                        >

                    </div>
                </div>

                {{-- FILTRO COLEGIO --}}
                @if (in_array(auth()->user()->rol?->nombre, ['ADMIN', 'SUPER ADMIN']))
                    <div class="w-full lg:w-64">
                        <label
                            for="colegio_id"
                            class="mb-2 block text-sm font-medium text-gray-800"
                        >
                            Colegio
                        </label>
                        <select
                            id="colegio_id"
                            name="colegio_id"
                            class="w-full rounded-lg
                                   border border-gray-300
                                   bg-white
                                   px-4 py-3
                                   text-sm text-gray-900
                                   outline-none
                                   transition
                                   focus:border-[#1a34c9]
                                   focus:ring-2
                                   focus:ring-[#1a34c9]/15"
                        >
                            <option value="">
                                Todos los colegios
                            </option>
                            @foreach ($colegios as $colegio)
                                <option
                                    value="{{ $colegio->id }}"
                                    @selected(request('colegio_id') == $colegio->id)
                                >
                                    {{ $colegio->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- BUSCAR --}}
                <button
                    type="submit"
                    class="rounded-lg
                           bg-[#1a34c9]
                           px-6 py-3
                           text-sm font-semibold text-white
                           transition
                           hover:bg-[#1429a3]
                           focus:outline-none
                           focus:ring-2
                           focus:ring-[#1a34c9]/30"
                >
                    Buscar
                </button>

                {{-- LIMPIAR --}}
                <a
                    href="{{ route('afiliados.index') }}"
                    class="rounded-lg
                           border border-gray-300
                           bg-white
                           px-6 py-3
                           text-center
                           text-sm font-semibold text-gray-700
                           transition
                           hover:bg-gray-50"
                >
                    Limpiar
                </a>
            </form>
        </div>

        {{-- TABLA --}}
        <div class="overflow-x-auto">
            <table class="min-w-[1200px] w-full">
                <thead class="border-b border-gray-200 bg-[#f1f3f8]">
                    <tr>
                        <th class="px-5 py-4 text-left text-sm font-semibold text-gray-800">
                            Registro departamental
                        </th>
                        <th class="px-5 py-4 text-left text-sm font-semibold text-gray-800">
                            Matrícula profesional
                        </th>
                        <th class="px-5 py-4 text-left text-sm font-semibold text-gray-800">
                            CI
                        </th>
                        <th class="px-5 py-4 text-left text-sm font-semibold text-gray-800">
                            Afiliado
                        </th>
                        <th class="px-5 py-4 text-left text-sm font-semibold text-gray-800">
                            Colegio
                        </th>
                        <th class="px-5 py-4 text-left text-sm font-semibold text-gray-800">
                            Celular
                        </th>
                        <th class="px-5 py-4 text-left text-sm font-semibold text-gray-800">
                            Estado
                        </th>
                        <th class="px-5 py-4 text-left text-sm font-semibold text-gray-800">
                            Fecha afiliación
                        </th>
                        <th class="px-5 py-4 text-left text-sm font-semibold text-gray-800">
                            Acciones
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse ($afiliados as $afiliado)
                        <tr class="transition hover:bg-gray-50">

                            {{-- REGISTRO --}}
                            <td class="whitespace-nowrap px-5 py-5 text-sm text-gray-700">
                                {{ $afiliado->registro_departamental }}
                            </td>

                            {{-- MATRÍCULA --}}
                            <td class="whitespace-nowrap px-5 py-5 text-sm text-gray-700">
                                {{ $afiliado->matricula_profesional }}
                            </td>

                            {{-- CI --}}
                            <td class="whitespace-nowrap px-5 py-5 text-sm text-gray-700">
                                {{ $afiliado->ci }}
                            </td>

                            {{-- AFILIADO --}}
                            <td class="px-5 py-5">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-11 w-11 shrink-0
                                               items-center justify-center
                                               rounded-full
                                               bg-[#e8ecff]
                                               text-sm font-bold
                                               text-[#1a34c9]"
                                    >
                                        {{ mb_strtoupper(
                                            mb_substr($afiliado->nombres, 0, 1, 'UTF-8') .
                                            mb_substr($afiliado->apellidos, 0, 1, 'UTF-8'),
                                            'UTF-8'
                                        ) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="whitespace-nowrap text-sm font-semibold text-gray-900">
                                            {{ $afiliado->nombres }}
                                            {{ $afiliado->apellidos }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            {{-- COLEGIO --}}
                            <td class="px-5 py-5 text-sm text-gray-700">
                                {{ $afiliado->colegio?->nombre ?? 'Sin colegio' }}
                            </td>

                            {{-- CELULAR --}}
                            <td class="whitespace-nowrap px-5 py-5 text-sm text-gray-700">
                                {{ $afiliado->celular }}
                            </td>

                            {{-- ESTADO --}}
                            <td class="px-5 py-5">
                                @if ($afiliado->estado === 'ACTIVO')
                                    <span
                                        class="inline-flex items-center
                                               rounded-full
                                               border border-[#b8d8e2]
                                               bg-[#edf7fa]
                                               px-3 py-1
                                               text-xs font-medium
                                               text-[#176078]"
                                    >
                                        Activo
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center
                                               rounded-full
                                               border border-red-200
                                               bg-red-50
                                               px-3 py-1
                                               text-xs font-medium
                                               text-red-600"
                                    >
                                        Inactivo
                                    </span>
                                @endif
                            </td>

                            {{-- FECHA --}}
                            <td class="whitespace-nowrap px-5 py-5 text-sm text-gray-600">
                                {{ $afiliado->fecha_afiliacion?->format('d/m/Y') }}
                            </td>

                            {{-- ACCIONES --}}
                            <td class="px-5 py-5">
                                <div class="flex flex-wrap items-center gap-2">

                                    {{-- VER INFORMACIÓN --}}
                                    <x-modal titulo="Información del afiliado" ancho="max-w-5xl">
                                        <x-slot:trigger>
                                            <button
                                                type="button"
                                                class="inline-flex items-center
                                                       rounded-lg
                                                       border border-gray-300
                                                       bg-white
                                                       px-3 py-2
                                                       text-xs font-semibold
                                                       text-gray-700
                                                       transition
                                                       hover:bg-gray-50
                                                       hover:text-[#1a34c9]"
                                            >
                                                Ver
                                            </button>
                                        </x-slot:trigger>
                                        @include('afiliados.partials.info', [
                                            'afiliado' => $afiliado
                                        ])
                                    </x-modal>

                                    {{-- EDITAR --}}
                                    @if (in_array(auth()->user()->rol?->nombre, [
                                        'SUPER ADMIN',
                                        'ADMIN',
                                        'USUARIO'
                                    ]))

                                        <x-modal titulo="Editar afiliado">
                                            <x-slot:trigger>
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center
                                                           rounded-lg
                                                           border border-gray-300
                                                           bg-white
                                                           px-3 py-2
                                                           text-xs font-semibold
                                                           text-gray-700
                                                           transition
                                                           hover:bg-gray-50
                                                           hover:text-[#1a34c9]"
                                                >
                                                    Editar
                                                </button>
                                            </x-slot:trigger>
                                            @include('afiliados.partials.form', [
                                                'modo' => 'editar',
                                                'afiliado' => $afiliado
                                            ])
                                        </x-modal>
                                    @endif

                                    {{-- ACTIVAR / DESACTIVAR --}}
                                    @if (auth()->user()->rol?->nombre === 'ADMIN')
                                        <form
                                            method="POST"
                                            action="{{ route('afiliados.estado', $afiliado) }}"
                                            class="inline"
                                            onsubmit="return confirm(
                                                '¿Está seguro de cambiar el estado de este afiliado?'
                                            );"
                                        >
                                            @csrf
                                            @method('PATCH')
                                            <button
                                                type="submit"
                                                class="inline-flex items-center
                                                       rounded-lg
                                                       border border-gray-300
                                                       bg-white
                                                       px-3 py-2
                                                       text-xs font-semibold
                                                       text-gray-700
                                                       transition
                                                       hover:bg-gray-50"
                                            >
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
                            <td
                                colspan="9"
                                class="px-6 py-14 text-center"
                            >
                                <p class="text-sm font-medium text-gray-600">
                                    No se encontraron afiliados.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINACIÓN - SOLO VISUAL POR AHORA --}}
        <div
            class="flex flex-col gap-4
                   border-t border-gray-200
                   bg-white
                   px-6 py-4
                   sm:flex-row
                   sm:items-center
                   sm:justify-between"
        >
            <p class="text-sm text-gray-600">
                Mostrando
                <span class="font-semibold text-gray-800">
                    1
                </span>
                a
                <span class="font-semibold text-gray-800">
                    {{ $afiliados->count() }}
                </span>
                afiliados
            </p>

            <div class="flex items-center gap-2">

                {{-- ANTERIOR --}}
                <button
                    type="button"
                    disabled
                    class="flex h-10 w-10 items-center justify-center
                           rounded-lg
                           border border-gray-200
                           bg-gray-50
                           text-gray-300"
                >
                    <span class="text-lg">
                        ‹
                    </span>
                </button>

                {{-- PÁGINA ACTUAL --}}
                <button
                    type="button"
                    class="flex h-10 w-10 items-center justify-center
                           rounded-lg
                           bg-[#1a34c9]
                           text-sm font-semibold text-white"
                >
                    1
                </button>

                {{-- SIGUIENTE --}}
                <button
                    type="button"
                    class="flex h-10 w-10 items-center justify-center
                           rounded-lg
                           border border-gray-300
                           bg-white
                           text-gray-700
                           transition
                           hover:bg-gray-50"
                >
                    <span class="text-lg">
                        ›
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection