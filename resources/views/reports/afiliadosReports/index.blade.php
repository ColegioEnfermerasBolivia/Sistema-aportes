@extends('layouts.app')
@section('content')

<div class="space-y-6">

    {{-- ENCABEZADO --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-900">
            Informes de afiliadas
        </h1>

        <p class="mt-1 text-sm text-gray-500">
            Genera reportes de afiliadas aplicando los filtros que necesites.
        </p>
    </div>

    {{-- FILTROS --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-900">
                Filtros del informe
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Selecciona los criterios que deseas aplicar al listado.
            </p>
        </div>

        <form
            method="GET"
            action="{{ route('afiliadosReports.index') }}"
            class="space-y-5"
        >
            {{-- FILA DE SELECTS --}}
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-4">
                {{-- AÑO DE AFILIACIÓN --}}
                <div>
                    <label
                        for="year_afiliacion"
                        class="mb-1.5 block text-sm font-medium text-gray-700"
                    >
                        Año de afiliación
                    </label>

                    <select
                        id="year_afiliacion"
                        name="year_afiliacion"
                        class="w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-[#1a34c9] focus:ring-[#1a34c9]"
                    >
                        <option value="">
                            Todos los años
                        </option>
                       @foreach ($aniosAfiliacion as $anio)
                            <option
                                value="{{ $anio }}"
                                @selected(request('year_afiliacion') == $anio)
                            >
                                {{ $anio }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- ESPECIALIDAD --}}
                <div>
                    <label
                        for="especialidad_id"
                        class="mb-1.5 block text-sm font-medium text-gray-700"
                    >
                        Especialidad
                    </label>

                    <select
                        id="especialidad_id"
                        name="especialidad_id"
                        class="w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-[#1a34c9] focus:ring-[#1a34c9]"
                    >
                        <option value="">
                            Todas las especialidades
                        </option>

                        @foreach ($especialidades as $especialidad)
                            <option
                                value="{{ $especialidad->id }}"
                                {{ request('especialidad_id') == $especialidad->id ? 'selected' : '' }}
                            >
                                {{ $especialidad->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- SITUACIÓN LABORAL --}}
                <div>
                    <label
                        for="trabaja"
                        class="mb-1.5 block text-sm font-medium text-gray-700"
                    >
                        Situación laboral
                    </label>

                    <select
                        id="trabaja"
                        name="trabaja"
                        class="w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-[#1a34c9] focus:ring-[#1a34c9]"
                    >
                        <option value="">
                            Todas
                        </option>

                        <option
                            value="1"
                            @selected(request('trabaja') === '1')
                        >
                            Trabaja
                        </option>

                        <option
                            value="0"
                            @selected(request('trabaja') === '0')
                        >
                            No trabaja
                        </option>
                    </select>
                </div>

                {{-- COLEGIO --}}
                @if (in_array(auth()->user()->rol?->nombre,['ADMIN', 'SUPER ADMIN']))
                    <div>
                        <label
                            for="colegio_id"
                            class="mb-1.5 block text-sm font-medium text-gray-700"
                        >
                            Colegio
                        </label>

                        <select
                            id="colegio_id"
                            name="colegio_id"
                            class="w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-[#1a34c9] focus:ring-[#1a34c9]"
                        >
                            <option value="">
                                Todos los colegios
                            </option>
                            @foreach ($colegios as $colegio)
                                <option
                                    value="{{ $colegio->id }}"
                                    {{ request('colegio_id') == $colegio->id ? 'selected' : '' }}
                                >
                                    {{ $colegio->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            {{-- BÚSQUEDA --}}
            <div>
                <label
                    for="buscar"
                    class="mb-1.5 block text-sm font-medium text-gray-700"
                >
                    Buscar afiliada
                </label>
                <div class="relative">
                    <input
                        type="text"
                        id="buscar"
                        name="buscar"
                        value="{{ request('buscar') }}"
                        placeholder="Buscar por nombre, apellidos o CI..."
                        class="w-full rounded-lg border-gray-300 pr-10 text-sm shadow-sm focus:border-[#1a34c9] focus:ring-[#1a34c9]"
                    >
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                        <x-icon
                            name="search"
                            class="text-gray-400"
                            size="20"
                        />
                    </div>
                </div>
            </div>

            {{-- BOTONES --}}
            <div class="flex flex-col gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                <a
                    href="{{ route('afiliadosReports.index') }}"
                    class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:cursor-pointer hover:bg-gray-100"
                >
                    <x-icon
                        name="cancel"
                        class="text-gray-600"
                        size="20"
                    />
                    Limpiar filtros
                </a>
                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-[#1a34c9] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#152ba8] hover:cursor-pointer"
                >
                    <x-icon
                        name="search"
                        class="text-white"
                        size="20"
                    />
                    Aplicar filtros
                </button>
            </div>
        </form>
    </div>

    {{-- ÁREA DEL REPORTE --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">
                    Generar reporte
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Genera el informe de afiliadas utilizando los filtros seleccionados.
                </p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">

                {{-- EXCEL --}}
                <a
                    href="{{ route('afiliadosReports.excel', request()->query()) }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-green-700"
                >
                    <x-icon name="download" class="text-white" size="20"/>
                    Excel
                </a>

                {{-- PDF --}}
                <a
                    href="{{ route('afiliadosReports.pdf', request()->query()) }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700"
                >
                    <x-icon name="download" class="text-white" size="20"/>
                    PDF
                </a>
            </div>
        </div>
    </div>

    {{-- RESULTADOS --}}
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-6 py-5">
            <h2 class="text-lg font-semibold text-gray-900">
                Afiliados
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                {{ $afiliados->count() }}
                {{ $afiliados->count() === 1 ? 'resultado encontrado': 'resultados encontrados' }}
            </p>
        </div>
        @if ($afiliados->isEmpty())
            <div class="px-6 py-12 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gray-100">
                    <x-icon
                        name="document"
                        class="text-gray-400"
                        size="28"
                    />
                </div>
                <h3 class="mt-4 text-sm font-semibold text-gray-900">
                    No hay resultados para mostrar
                </h3>
                <p class="mx-auto mt-1 max-w-md text-sm text-gray-500">
                    No se encontraron afiliados que coincidan con los filtros seleccionados
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                CI
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Apellidos
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Nombres
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Fecha de afiliación
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Colegio
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach ($afiliados as $afiliado)
                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                    {{ $afiliado->ci }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                    {{ $afiliado->apellidos }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                    {{ $afiliado->nombres }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                    {{ $afiliado->fecha_afiliacion?->format('d/m/Y') }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                    {{ $afiliado->colegio?->nombre ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection