@extends('layouts.app')

@section('title', 'Aportes pendientes')

@section('content')
<div class="min-h-screen bg-[#f7f7f9] px-6 py-8 lg:px-12">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="m-0 text-3xl font-semibold tracking-tight text-gray-900">Aportes pendientes</h1>
            <p class="mt-2 text-base text-gray-600">Regularización y seguimiento de períodos anteriores.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            @if (auth()->user()->rol?->nombre === 'USUARIO')
                <x-modal
                    titulo="Crear lote de aportes pendientes"
                    ancho="max-w-xl"
                    :abierto-inicial="$errors->hasAny(['colegio_id', 'mes', 'observaciones', 'lote'])"
                >
                    <x-slot:trigger>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-[#1a34c9] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#1428a0] hover:cursor-pointer"
                        >
                            <x-icon name="register" class="text-white" size="20"/>
                            Crear lote de aportes pendientes
                        </button>
                    </x-slot:trigger>
                    @include('devengados.partials.lote-crear', [
                        'colegiosDisponibles' => $colegiosDisponibles,
                        'enModal' => true,
                    ])
                </x-modal>
                <x-modal titulo="Agregar pagos pendientes" ancho="max-w-6xl" @cerrar-modal-devengado.window="abierto = false">
                    <x-slot:trigger>
                        <button type="button"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-[#1a34c9] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#1428a0] hover:cursor-pointer">
                            <x-icon name="cashRegister" class="text-white" size="20"/>
                            Agregar pago pendiente
                        </button>
                    </x-slot:trigger>
                    @include('devengados.pagos.form', [
                        'modoEdicion' => false,
                        'gestiones' => [],
                        'seleccionados' => [],
                        'lotesDisponibles' => [],
                        'afiliada' => null,
                        'pago' => null,
                    ])
                </x-modal>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="mb-5 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="mb-8 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-6 py-5">
            <h2 class="text-xl font-semibold text-gray-900">Lotes de deudas pendientes activos</h2>
        </div>
        <div class="max-h-[70vh] overflow-auto">
            <table class="min-w-[1000px] w-full">
                <thead class="border-b-2 border-[#1a34c9] bg-[#4f588a]/10 text-xs uppercase tracking-wide text-[#1a34c9]">
                    <tr class="text-left text-sm font-semibold text-gray-800">
                        <th class="sticky top-0 z-10 px-5 py-4">Código</th>
                        <th class="sticky top-0 z-10 px-5 py-4">Colegio</th>
                        <th class="sticky top-0 z-10 px-5 py-4">Período</th>
                        <th class="sticky top-0 z-10 px-5 py-4">Pagos</th>
                        <th class="sticky top-0 z-10 px-5 py-4">Estado</th>
                        <th class="sticky top-0 z-10 px-5 py-4">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($lotes as $lote)
                        <tr class="transition hover:bg-blue-100">
                            <td class="whitespace-nowrap px-5 py-5 text-sm font-medium text-gray-900">{{ $lote->codigo_lote }}</td>
                            <td class="whitespace-nowrap px-5 py-5 text-sm text-gray-700">{{ $lote->colegio->nombre }}</td>
                            <td class="whitespace-nowrap px-5 py-5 text-sm text-gray-700">
                                @if ($lote->mes && $lote->year)
                                    {{ str_pad((string) $lote->mes, 2, '0', STR_PAD_LEFT) }}/{{ $lote->year }}
                                @else
                                    Lote anterior sin período asignado
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-5 text-sm text-gray-700">{{ $lote->pagos_count }}</td>
                            <td class="px-5 py-5">
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
                            <td class="px-5 py-5">
                                <div class="flex flex-wrap gap-2">
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
                                    @if (auth()->user()->rol?->nombre === 'ADMIN' && in_array($lote->estado, ['ENVIADO', 'EN_VERIFICACION'], true))
                                        <a href="{{ route('devengados.show', $lote) }}"
                                            class="inline-flex items-center rounded-lg bg-[#1a34c9] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#1428a0]">
                                            Abrir revisión
                                        </a>
                                    @endif
                                    @if (in_array($lote->estado, ['ENVIADO', 'EN_VERIFICACION', 'VERIFICADO'], true))
                                        <a href="{{ route('devengados.lotes.excel', $lote) }}"
                                            class="inline-flex items-center rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                            Descargar Excel
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-gray-500">No hay lotes devengados activos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
