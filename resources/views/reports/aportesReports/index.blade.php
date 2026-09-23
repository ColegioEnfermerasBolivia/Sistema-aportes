@extends('layouts.app')

@section('title', 'Informes de Aportes')

@section('content')

<div class="space-y-6">
    {{-- ENCABEZADO --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-800">
            Informes de Aportes
        </h1>
        <p class="mt-1 text-sm text-gray-500">
            Consulta y generación de informes de aportes verificados.
        </p>
    </div>

    {{-- FILTROS --}}
    <div class="rounded-xl bg-white p-5 shadow-sm">
        <form
            method="GET"
            action="{{ route('aportesReports.index') }}"
            class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4"
        >
            {{-- CI --}}
            <div>
                <label
                    for="buscar"
                    class="mb-1 block text-sm font-medium text-gray-700"
                >
                    CI de la afiliada
                </label>
                <input
                    type="text"
                    id="buscar"
                    name="buscar"
                    value="{{ request('buscar') }}"
                    placeholder="Buscar por CI"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm
                           focus:border-[#1a34c9] focus:outline-none focus:ring-1
                           focus:ring-[#1a34c9]"
                >
            </div>

            {{-- GESTIÓN --}}
            <div>
                <label
                    for="year"
                    class="mb-1 block text-sm font-medium text-gray-700"
                >
                    Gestión
                </label>

                <select
                    id="year"
                    name="year"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm
                           focus:border-[#1a34c9] focus:outline-none focus:ring-1
                           focus:ring-[#1a34c9]"
                >
                    <option value="">
                        Todos los años
                    </option>

                    @foreach ($gestiones as $gestion)
                        <option
                            value="{{ $gestion }}"
                            @selected((string) $year === (string) $gestion)
                        >
                            {{ $gestion }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- COLEGIO --}}
            @if (in_array(auth()->user()->rol?->nombre, ['ADMIN', 'SUPER ADMIN']))
                <div>
                    <label
                        for="colegio_id"
                        class="mb-1 block text-sm font-medium text-gray-700"
                    >
                        Colegio
                    </label>
                    <select
                        id="colegio_id"
                        name="colegio_id"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm
                               focus:border-[#1a34c9] focus:outline-none focus:ring-1
                               focus:ring-[#1a34c9]"
                    >
                        <option value="">
                            Todos los colegios
                        </option>
                        @foreach ($colegios as $colegio)
                            <option
                                value="{{ $colegio->id }}"
                                @selected((string) request('colegio_id') === (string) $colegio->id)
                            >
                                {{ $colegio->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- BOTONES --}}
            <div class="flex items-end gap-2">
                <button
                    type="submit"
                    class="rounded-lg bg-[#1a34c9] px-4 py-2 text-sm font-medium inline-flex gap-1.5
                           text-white transition hover:bg-blue-800 hover:cursor-pointer"
                >   
                    <x-icon name="search" size="20"/>
                    Buscar
                </button>
                <a
                    href="{{ route('aportesReports.index') }}"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 inline-flex gap-1.5
                           text-sm font-medium text-gray-700 transition
                           hover:bg-gray-50 hover:cursor-pointer"
                >
                    <x-icon name="eraser" size="20"/>
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    {{-- EXPORTACIONES --}}
    <div class="flex flex-wrap justify-end gap-2 px-4 py-2">
        <a
            href="{{ route('aportesReports.excel', request()->query()) }}"
            class="inline-flex items-center rounded-lg bg-green-600 px-4 py-2
                   text-sm font-medium text-white transition hover:bg-green-700"
        >
            <x-icon name="download" class="text-white" size="20"/>
            Excel
        </a>
        <a
            href="{{ route('aportesReports.pdf', request()->query()) }}"
            class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2
                   text-sm font-medium text-white transition hover:bg-red-700"
        >
            <x-icon name="download" class="text-white" size="20"/>
            PDF
        </a>
    </div>

    {{-- RESULTADOS --}}
    <div class="rounded-xl bg-white p-5 shadow-sm">
        {{-- SIN RESULTADOS --}}
        @if ($afiliados->isEmpty())
            <div class="py-10 text-center">
                <p class="text-base font-medium text-gray-700">
                    No se encontraron resultados.
                </p>
                @if (request()->filled('buscar'))
                    <p class="mt-1 text-sm text-gray-500">
                        No existe una afiliada con el CI
                        <span class="font-semibold">
                            {{ request('buscar') }}
                        </span>
                        dentro de los datos permitidos.
                    </p>
                @else
                    <p class="mt-1 text-sm text-gray-500">
                        No existen afiliadas que coincidan con los filtros seleccionados.
                    </p>
                @endif
            </div>

        {{-- UNA AFILIADA BUSCADA POR CI --}}
        @elseif (request()->filled('buscar') && $afiliados->count() === 1)
            @php
                $afiliada = $afiliados->first();
            @endphp

            {{-- INFORMACIÓN DE LA AFILIADA --}}
            <div class="mb-6 rounded-xl border border-gray-200 bg-gray-50 p-5">
                <div class="mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">
                        Datos de la afiliada
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Información correspondiente al CI consultado.
                    </p>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Nombre completo
                        </p>
                        <p class="mt-1 text-sm font-semibold text-gray-800">
                            {{ $afiliada->nombres }} {{ $afiliada->apellidos }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            CI
                        </p>
                        <p class="mt-1 text-sm font-semibold text-gray-800">
                            {{ $afiliada->ci }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Colegio
                        </p>
                        <p class="mt-1 text-sm font-semibold text-gray-800">
                            {{ $afiliada->colegio?->nombre ?? 'Sin colegio registrado' }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- CI + GESTIÓN ESPECÍFICA --}}
            @if ($year)
                <div class="mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">
                        APORTES DEL AÑO: {{ $year }}
                    </h2>
                </div>

                @php
                    $aportesGestion = $afiliada->aportesPorGestion
                        ->get((int) $year, collect());
                @endphp

                @if ($aportesGestion->isEmpty())
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-6 text-center">
                        <p class="text-sm font-medium text-gray-700">
                            No registra aportes verificados.
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            No existen aportes verificados para la gestión {{ $year }}.
                        </p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse text-sm">
                            <thead>
                                <tr class="bg-blue-700">
                                    @foreach ($meses as $mes)
                                        <th
                                            class="border border-gray-300 px-4 py-3 text-center
                                                   text-xs font-semibold text-white"
                                        >
                                            {{ substr($mes, 0, 3) }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    @foreach ($meses as $numeroMes => $mes)
                                        @php
                                            $aporte = $aportesGestion->get($numeroMes);
                                        @endphp
                                        <td
                                            class="border border-gray-300 px-4 py-3 text-center
                                                   text-gray-700"
                                        >
                                            {{ $aporte ? number_format((float) $aporte->monto, 2) : '—' }}
                                        </td>
                                    @endforeach
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endif

            {{-- CI + TODOS LOS AÑOS --}}
            @else
                @php
                    $gestionesAfiliada = $afiliada->aportesPorGestion
                        ->keys()
                        ->sortDesc();
                @endphp
                @if ($gestionesAfiliada->isEmpty())

                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-6 text-center">
                        <p class="text-sm font-medium text-gray-700">
                            No registra aportes verificados.
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            La afiliada no tiene aportes verificados registrados.
                        </p>
                    </div>
                @else
                    <div class="space-y-8">
                        @foreach ($gestionesAfiliada as $gestionAfiliada)
                            @php
                                $aportesGestion = $afiliada->aportesPorGestion
                                    ->get($gestionAfiliada, collect());
                            @endphp
                            <div>
                                <div class="mb-4">
                                    <h2 class="text-lg font-semibold text-gray-800">
                                        APORTES DEL AÑO: {{ $gestionAfiliada }}
                                    </h2>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full border-collapse text-sm">
                                        <thead>
                                            <tr class="bg-blue-700">
                                                @foreach ($meses as $mes)
                                                    <th
                                                        class="border border-gray-300 px-4 py-3
                                                               text-center text-xs font-semibold
                                                               text-white"
                                                    >
                                                        {{ substr($mes, 0, 3) }}
                                                    </th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                @foreach ($meses as $numeroMes => $mes)
                                                    @php
                                                        $aporte = $aportesGestion->get($numeroMes);
                                                    @endphp
                                                    <td
                                                        class="border border-gray-300 px-4 py-3
                                                               text-center text-gray-700"
                                                    >
                                                        {{ $aporte ? number_format((float) $aporte->monto, 2) : '—' }}
                                                    </td>
                                                @endforeach
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif

        {{-- LISTADO GENERAL --}}
        @else
            @php
                $gestionTabla = $year ?? $gestionVisual;
            @endphp
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-gray-800">
                    APORTES DEL AÑO: {{ $gestionTabla }}
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Afiliadas y aportes verificados correspondientes a la gestión seleccionada.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse text-sm">
                    <thead>
                        <tr class="bg-blue-700">
                            <th
                                class="border border-gray-300 px-3 py-3 text-center
                                       text-xs font-semibold text-white"
                            >
                                N.º
                            </th>
                            <th
                                class="border border-gray-300 px-4 py-3 text-left
                                       text-xs font-semibold text-white"
                            >
                                Afiliada
                            </th>
                            <th
                                class="border border-gray-300 px-4 py-3 text-left
                                       text-xs font-semibold text-white"
                            >
                                CI
                            </th>
                            @foreach ($meses as $mes)
                                <th
                                    class="border border-gray-300 px-4 py-3 text-center
                                           text-xs font-semibold text-white"
                                >
                                    {{ substr($mes, 0, 3) }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($afiliados as $index => $afiliada)
                            @php
                                $aportesGestion = $afiliada->aportesPorGestion
                                    ->get((int) $gestionTabla, collect());
                            @endphp
                            <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }}">
                                <td
                                    class="border border-gray-300 px-3 py-3 text-center text-gray-700"
                                >
                                    {{ $index + 1 }}
                                </td>
                                <td
                                    class="border border-gray-300 px-4 py-3 font-medium text-gray-800"
                                >
                                    {{ $afiliada->apellidos }}, {{ $afiliada->nombres }}
                                </td>
                                <td
                                    class="border border-gray-300 px-4 py-3 text-gray-700"
                                >
                                    {{ $afiliada->ci }}
                                </td>

                                @foreach ($meses as $numeroMes => $mes)
                                    @php
                                        $aporte = $aportesGestion->get($numeroMes);
                                    @endphp
                                    <td
                                        class="border border-gray-300 px-4 py-3 text-center
                                               text-gray-700"
                                    >
                                        {{ $aporte ? number_format((float) $aporte->monto, 2) : '—' }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection