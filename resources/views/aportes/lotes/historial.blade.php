@extends('layouts.app')
@section('title', 'Historial de lotes')
@section('content')

<div class="min-h-screen bg-[#f7f7f9] px-6 py-8 lg:px-12">
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

    <div class="mb-4">
        <h2 class="text-xl font-semibold text-gray-900">Lotes ordinarios verificados</h2>
    </div>

    {{-- TABLA --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="max-h-[70vh] overflow-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="text-xs uppercase text-[#1a34c9]">
                    <tr>
                        {{-- COLEGIO --}}
                        @if (
                            in_array(
                                auth()->user()->rol?->nombre,
                                ['SUPER ADMIN', 'ADMIN']
                            )
                        )
                            <th class="sticky top-0 z-10 bg-[#edeef3] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 shadow-[inset_0_-2px_0_#1a34c9]">
                                Colegio
                            </th>
                        @endif
                        <th class="sticky top-0 z-10 bg-[#edeef3] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 shadow-[inset_0_-2px_0_#1a34c9]">
                            Código
                        </th>
                        <th class="sticky top-0 z-10 bg-[#edeef3] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 shadow-[inset_0_-2px_0_#1a34c9]">
                            Período
                        </th>
                        <th class="sticky top-0 z-10 bg-[#edeef3] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 shadow-[inset_0_-2px_0_#1a34c9]">
                            Pagos
                        </th>
                        <th class="sticky top-0 z-10 bg-[#edeef3] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 shadow-[inset_0_-2px_0_#1a34c9]">
                            Monto total
                        </th>
                        <th class="sticky top-0 z-10 bg-[#edeef3] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 shadow-[inset_0_-2px_0_#1a34c9]">
                            Estado
                        </th>
                        <th class="sticky top-0 z-10 bg-[#edeef3] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 shadow-[inset_0_-2px_0_#1a34c9]">
                            Ver
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 bg-white">
                @foreach ($lotes as $lote)
                    <tr class="transition hover:bg-blue-100">
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

    <div class="mt-8 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-6 py-5">
            <h2 class="text-xl font-semibold text-gray-900">Lotes de deudas verificados</h2>
        </div>
        <div class="max-h-[70vh] overflow-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="sticky top-0 z-10 bg-gray-50 text-xs uppercase text-gray-600 shadow-[inset_0_-1px_0_#e5e7eb]">
                    <tr>
                        @if (in_array(auth()->user()->rol?->nombre, ['SUPER ADMIN', 'ADMIN'], true))
                            <th class="px-5 py-3 text-left">Colegio</th>
                        @endif
                        <th class="px-5 py-3 text-left">Código</th>
                        <th class="px-5 py-3 text-left">Período</th>
                        <th class="px-5 py-3 text-left">Pagos</th>
                        <th class="px-5 py-3 text-left">Importe pagado</th>
                        <th class="px-5 py-3 text-left">Estado</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($devengadoLotes as $lote)
                        <tr class="hover:bg-blue-50">
                            @if (in_array(auth()->user()->rol?->nombre, ['SUPER ADMIN', 'ADMIN'], true))
                                <td class="px-5 py-4">{{ $lote->colegio->nombre }}</td>
                            @endif
                            <td class="px-5 py-4 font-semibold">{{ $lote->codigo_lote }}</td>
                            <td class="px-5 py-4">
                                @if ($lote->mes && $lote->year)
                                    {{ str_pad((string) $lote->mes, 2, '0', STR_PAD_LEFT) }}/{{ $lote->year }}
                                @else
                                    Lote anterior sin período asignado
                                @endif
                            </td>
                            <td class="px-5 py-4">{{ $lote->pagos_count }}</td>
                            <td class="px-5 py-4">Bs {{ number_format((float) ($lote->monto_total ?? 0), 2, ',', '.') }}</td>
                            <td class="px-5 py-4">
                                @if ($lote->estado === 'ABIERTO')
                                    <span class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">Abierto</span>
                                @elseif ($lote->estado === 'ENVIADO')
                                    <span class="inline-flex items-center rounded-full border border-[#b8d8e2] bg-[#edf7fa] px-3 py-1 text-xs font-medium text-[#176078]">Enviado</span>
                                @elseif ($lote->estado === 'EN_VERIFICACION')
                                    <span class="inline-flex items-center rounded-full border border-orange-200 bg-orange-50 px-3 py-1 text-xs font-medium text-orange-600">En verificación</span>
                                @elseif ($lote->estado === 'VERIFICADO')
                                    <span class="inline-flex items-center rounded-full border border-green-200 bg-green-50 px-3 py-1 text-xs font-medium text-green-700">Verificado</span>
                                @else
                                    <span class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-medium text-gray-700">{{ str_replace('_', ' ', $lote->estado) }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex flex-wrap justify-end gap-3">
                                    <x-modal titulo="Detalle del lote {{ $lote->codigo_lote }}" ancho="max-w-6xl">
                                        <x-slot:trigger>
                                            <button type="button"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-sky-500 bg-white px-4 py-2 text-sm font-semibold text-sky-500 transition hover:bg-gray-50 hover:cursor-pointer">
                                                <x-icon name="see" class="text-sky-500" size="20"/>
                                                Ver pagos
                                            </button>
                                        </x-slot:trigger>
                                        @include('devengados.partials.lote-detalle', ['lote' => $lote])
                                    </x-modal>
                                    <a href="{{ route('devengados.lotes.excel', $lote) }}"
                                    class="inline-flex items-center rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">
                                        Descargar Excel
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ in_array(auth()->user()->rol?->nombre, ['SUPER ADMIN', 'ADMIN'], true) ? 7 : 6 }}" class="px-5 py-8 text-center text-gray-500">No hay lotes devengados verificados con estos filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($devengadoLotes->hasPages())
            <div class="border-t border-gray-200 px-5 py-4">{{ $devengadoLotes->appends(request()->except('devengados_page'))->links() }}</div>
        @endif
    </div>
</div>
@endsection

