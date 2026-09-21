@extends('layouts.app')
@section('title', 'Historial de lotes')
@section('content')

<div class="px-6 py-8 lg:px-10">
    {{-- ENCABEZADO --}}
    <div class="mb-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-gray-900">
                    Historial de lotes
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Consulta los lotes verificados y cerrados.
                </p>
            </div>
        </div>
    </div>

    {{-- FILTROS --}}
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <form
            method="GET"
            action="{{ route('lotes.historial') }}"
            class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3"
        >
            {{-- BÚSQUEDA POR CÓDIGO --}}
            <div class="lg:col-span-1">
                <label
                    for="buscar"
                    class="mb-2 block text-sm font-medium text-gray-700"
                >
                    Buscar lote
                </label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg
                            class="h-5 w-5 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-4.35-4.35m1.35-5.65a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                    </div>

                    <input
                        type="text"
                        name="buscar"
                        id="buscar"
                        value="{{ request('buscar') }}"
                        placeholder="Código del lote..."
                        class="w-full rounded-lg border border-gray-300 py-2.5 pl-10 pr-3 text-sm text-gray-900 focus:border-[#1a34c9] focus:outline-none focus:ring-2 focus:ring-[#1a34c9]/20"
                    >
                </div>
            </div>

            {{-- COLEGIO --}}
            @if (
                in_array(
                    auth()->user()->rol?->nombre,
                    ['SUPER ADMIN', 'ADMIN']
                )
            )
                <div>
                    <label
                        for="colegio_id"
                        class="mb-2 block text-sm font-medium text-gray-700"
                    >
                        Colegio
                    </label>
                    <select
                        name="colegio_id"
                        id="colegio_id"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-[#1a34c9] focus:outline-none focus:ring-2 focus:ring-[#1a34c9]/20"
                    >
                        <option value="">
                            Todos los colegios
                        </option>
                        @foreach ($colegios as $colegio)
                            <option
                                value="{{ $colegio->id }}"
                                @selected(
                                    (string) request('colegio_id') ===
                                    (string) $colegio->id
                                )
                            >
                                {{ $colegio->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- BOTONES --}}
            <div class="flex items-end gap-3">
                <button
                    type="submit"
                    class="rounded-lg bg-[#1a34c9] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#1428a0] inline-flex items-center gap-1.5 hover:cursor-pointer"
                >
                    <x-icon name="search" class="text-white" size="25"/>
                    Buscar
                </button>
                @if (request()->hasAny(['buscar', 'colegio_id']))
                    <a
                        href="{{ route('lotes.historial') }}"
                        class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-100"
                    >
                        Limpiar
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- TABLA --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-[1050px] w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        {{-- COLEGIO --}}
                        @if (
                            in_array(
                                auth()->user()->rol?->nombre,
                                ['SUPER ADMIN', 'ADMIN']
                            )
                        )
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Colegio
                            </th>
                        @endif
                        <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Código
                        </th>
                        <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Período
                        </th>
                        <th class="px-5 py-4 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Pagos
                        </th>
                        <th class="px-5 py-4 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Monto total
                        </th>
                        <th class="px-5 py-4 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Estado
                        </th>
                        <th class="px-5 py-4 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Ver
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 bg-white">
                @foreach ($lotes as $lote)
                    <tr class="transition hover:bg-gray-200">
                        @if (in_array(auth()->user()->rol?->nombre, ['ADMIN', 'SUPER ADMIN']))
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $lote->colegio->nombre }}
                            </td>
                        @endif

                        <td class="px-6 py-4 text-left text-sm font-medium text-gray-900">
                            {{ $lote->codigo_lote }}
                        </td>

                        <td class="px-6 py-4 text-left text-sm text-gray-700">
                            {{ str_pad($lote->mes, 2, '0', STR_PAD_LEFT) }}/{{ $lote->year }}
                        </td>

                        <td class="px-6 py-4 text-center text-sm text-gray-700">
                            {{ $lote->pagos->count() }}
                        </td>

                        <td class="px-6 py-4 text-center text-sm text-gray-700">
                            Bs {{ number_format($lote->monto_total, 2) }}
                        </td>

                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-center text-xs font-semibold text-green-700">
                                {{ $lote->estado }}
                            </span>
                        </td>

                        <td class="px-6 py-4 text-center"> 
                            <x-modal titulo="Detalle del lote" ancho="max-w-6xl" > 
                                <x-slot:trigger> 
                                    <button type="button" class="rounded-lg bg-[#1a34c9] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#1428a0] inline-flex gap-1.5 items-center hover:cursor-pointer" > 
                                        <x-icon name="see" class="text-white" size="20"/>
                                        Ver pagos 
                                    </button> 
                                </x-slot:trigger> 
                                @include('aportes.lotes.partials.lote-detalle', [ 'lote' => $lote ]) 
                            </x-modal> </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        {{-- PAGINACIÓN --}}
        @if ($lotes->hasPages())
            <div class="border-t border-gray-200 px-5 py-4">
                {{ $lotes->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

