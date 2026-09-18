@extends('layouts.app')
@section('title', 'Lotes de Aportes')
@section('content')

<div
    x-data="revisionLotes()"
    class="p-6"
>
    <div class="min-h-screen bg-[#f7f7f9] px-6 py-8 lg:px-12">

        {{-- USUARIO --}}
        @if (auth()->user()->rol?->nombre === 'USUARIO')

            <div class="mb-8">
                <h1 class="m-0 text-3xl font-semibold tracking-tight text-gray-900">
                    Mi lote actual
                </h1>

                <p class="mt-2 text-base text-gray-600">
                    Administre los pagos correspondientes al lote de su colegio.
                </p>
            </div>


            @if ($loteActual)

                {{-- TARJETA DEL LOTE ACTUAL --}}
                <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">

                    {{-- ENCABEZADO --}}
                    <div class="flex flex-col gap-4 border-b border-gray-200 px-7 py-6 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">
                                Lote actual
                            </p>

                            <h2 class="mt-1 text-2xl font-semibold text-gray-900">
                                {{ $loteActual->codigo_lote }}
                            </h2>

                            <p class="mt-1 text-sm text-gray-500">
                                Período:
                                {{ str_pad($loteActual->mes, 2, '0', STR_PAD_LEFT) }}/{{ $loteActual->year }}
                            </p>
                        </div>

                        {{-- ESTADO --}}
                        <div>
                            @if ($loteActual->estado === 'ABIERTO')
                                <span
                                    class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700"
                                >
                                    Abierto
                                </span>

                            @elseif ($loteActual->estado === 'ENVIADO')
                                <span
                                    class="inline-flex items-center rounded-full border border-[#b8d8e2] bg-[#edf7fa] px-3 py-1.5 text-xs font-medium text-[#176078]"
                                >
                                    Enviado
                                </span>

                            @elseif ($loteActual->estado === 'EN_VERIFICACION')
                                <span
                                    class="inline-flex items-center rounded-full border border-orange-200 bg-orange-50 px-3 py-1.5 text-xs font-medium text-orange-600"
                                >
                                    En verificación
                                </span>

                            @endif
                        </div>
                    </div>

                    {{-- INFORMACIÓN --}}
                    <div class="grid grid-cols-1 gap-5 px-7 py-7 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <p class="text-sm text-gray-500">
                                Colegio
                            </p>
                            <p class="mt-1 font-semibold text-gray-900">
                                {{ $loteActual->colegio->nombre }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-500">
                                Cantidad de aportes
                            </p>
                            <p class="mt-1 font-semibold text-gray-900">
                                {{ $loteActual->cantidad_aportes }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-500">
                                Monto total
                            </p>
                            <p class="mt-1 font-semibold text-gray-900">
                                {{ number_format($loteActual->monto_total, 2) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-500">
                                Pagos registrados
                            </p>
                            <p class="mt-1 font-semibold text-gray-900">
                                {{ $loteActual->pagos->count() }}
                            </p>
                        </div>
                    </div>

                    {{-- ACCIONES --}}
                    <div class="flex flex-col gap-3 border-t border-gray-200 px-7 py-5 sm:flex-row sm:justify-end">

                        {{-- Ver pagos --}}
                        <x-modal
                            titulo="Pagos del lote {{ $loteActual->codigo_lote }}"
                            ancho="max-w-6xl"
                        >
                            <x-slot:trigger>
                                <button
                                    type="button"
                                    class="rounded-lg border border-sky-500 bg-white px-4 py-2 text-sm font-semibold text-sky-500 transition hover:bg-gray-50 inline-flex items-center gap-1.5"
                                >
                                    <x-icon name="see" class="text-sky-500" size="20"/>
                                    Ver pagos
                                </button>
                            </x-slot:trigger>

                            @include('aportes.lotes.partials.lote-detalle', [
                                'lote' => $loteActual
                            ])
                        </x-modal>

                        {{-- Cerrar lote --}}
                        @if ($loteActual->estado === 'ABIERTO')

                            <form
                                method="POST"
                                action="{{ route('lotes.cerrar', $loteActual) }}"
                            >
                                @csrf
                                @method('PATCH')
                                <button
                                    type="submit"
                                    onclick="return confirm('¿Deseas enviar este lote para revisión? Una vez enviado no podrás modificar sus pagos.')"
                                    class="rounded-lg bg-[#1a34c9] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#1428a0] hover:cursor-pointer inline-flex items-center gap-1.5"
                                >
                                    <x-icon name="send" class="text-white" size="20"/>
                                    Enviar lote
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @else
                {{-- SIN LOTE ACTUAL --}}
                <div class="flex min-h-[420px] items-center justify-center rounded-2xl border border-dashed border-gray-300 bg-white">
                    <div class="max-w-md px-6 py-12 text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-blue-50">
                            <svg
                                class="h-8 w-8 text-[#1a34c9]"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.7"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                        </div>

                        <h2 class="mt-5 text-xl font-semibold text-gray-900">
                            No tienes un lote activo
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-gray-500">
                            Puedes crear un nuevo lote para registrar los pagos
                            correspondientes a tu colegio.
                        </p>
                        <x-modal titulo="Crear lote de aportes" ancho="max-w-xl">
                            <x-slot:trigger>
                                <button
                                    type="button"
                                    class="rounded-lg bg-[#1a34c9] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#1428a0] hover:cursor-pointer inline-flex items-center gap-1.5"
                                >
                                    <x-icon name="register" class="text-white" size="20"/>
                                    Crear lote
                                </button>
                            </x-slot:trigger>
                            @include('aportes.lotes.partials.lote-crear')
                        </x-modal>
                    </div>
                </div>
            @endif

        {{-- ADMIN / SUPER ADMIN --}}
        @else
            {{-- ENCABEZADO --}}
            <div class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="m-0 text-3xl font-semibold tracking-tight text-gray-900">
                        Lista de lotes
                    </h1>
                    <p class="mt-2 text-base text-gray-600">
                        Administre los lotes de aportes enviados por los colegios.
                    </p>
                </div>
            </div>

            {{-- TABLA --}}
            <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                <table class="min-w-[1200px] w-full">
                    <thead class="border-b border-gray-200 bg-[#f1f3f8]">
                        <tr>
                            <th class="px-5 py-4 text-left text-sm font-semibold text-gray-800">
                                Código
                            </th>
                            <th class="px-5 py-4 text-left text-sm font-semibold text-gray-800">
                                Colegio
                            </th>
                            <th class="px-5 py-4 text-left text-sm font-semibold text-gray-800">
                                Período
                            </th>
                            <th class="px-5 py-4 text-left text-sm font-semibold text-gray-800">
                                Cant. Aportes
                            </th>
                            <th class="px-5 py-4 text-left text-sm font-semibold text-gray-800">
                                Monto total
                            </th>
                            <th class="px-5 py-4 text-left text-sm font-semibold text-gray-800">
                                Estado
                            </th>
                            <th class="px-5 py-4 text-left text-sm font-semibold text-gray-800">
                                Acciones
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @forelse ($lotes as $lote)
                            <tr class="transition hover:bg-gray-50">
                                {{-- CÓDIGO --}}
                                <td class="whitespace-nowrap px-5 py-5 text-sm text-gray-700">
                                    {{ $lote->codigo_lote }}
                                </td>

                                {{-- COLEGIO --}}
                                <td class="whitespace-nowrap px-5 py-5 text-sm text-gray-700">
                                    {{ $lote->colegio->nombre }}
                                </td>

                                {{-- PERÍODO --}}
                                <td class="whitespace-nowrap px-5 py-5 text-sm text-gray-700">
                                    {{ str_pad($lote->mes, 2, '0', STR_PAD_LEFT) }}/{{ $lote->year }}
                                </td>

                                {{-- CANTIDAD --}}
                                <td class="whitespace-nowrap px-5 py-5 text-sm text-gray-700">
                                    {{ $lote->cantidad_aportes }}
                                </td>

                                {{-- MONTO --}}
                                <td class="whitespace-nowrap px-5 py-5 text-sm text-gray-700">
                                    {{ number_format($lote->monto_total, 2) }}
                                </td>

                                {{-- ESTADO --}}
                                <td class="px-5 py-5">
                                    @if ($lote->estado === 'ABIERTO')
                                        <span
                                            class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700"
                                        >
                                            Abierto
                                        </span>
                                    @elseif ($lote->estado === 'ENVIADO')
                                        <span
                                            class="inline-flex items-center rounded-full border border-[#b8d8e2] bg-[#edf7fa] px-3 py-1 text-xs font-medium text-[#176078]"
                                        >
                                            Recibido
                                        </span>
                                    @elseif ($lote->estado === 'EN_VERIFICACION')
                                        <span
                                            class="inline-flex items-center rounded-full border border-orange-200 bg-orange-50 px-3 py-1 text-xs font-medium text-orange-600"
                                        >
                                            En verificación
                                        </span>
                                    @endif
                                </td>

                                {{-- ACCIONES --}}
                                <td class="px-5 py-5">
                                    <div class="flex flex-wrap gap-2">
                                        {{-- VER LOTE --}}
                                        <x-modal
                                            titulo="Detalle del lote {{ $lote->codigo_lote }}"
                                            ancho="max-w-6xl"
                                        >
                                            <x-slot:trigger>
                                                <button
                                                    type="button"
                                                    class="rounded-lg border border-sky-500 bg-white px-4 py-2 text-sm font-semibold text-sky-500 transition hover:bg-gray-50 inline-flex items-center gap-1.5 hover:cursor-pointer"
                                                >
                                                    <x-icon name="see" class="text-sky-500" size="20"/>
                                                    Ver
                                                </button>
                                            </x-slot:trigger>

                                            @include('aportes.lotes.partials.lote-detalle', [
                                                'lote' => $lote
                                            ])
                                        </x-modal>

                                        {{-- ENVIADO --}}
                                        @if ($lote->estado === 'ENVIADO')
                                            @if (auth()->user()->rol?->nombre === 'ADMIN')
                                                <form
                                                    method="POST"
                                                    action="{{ route('lotes.revisar', $lote) }}"
                                                >
                                                    @csrf
                                                    <button
                                                        type="submit"
                                                        class="rounded-lg bg-[#1a34c9] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#1428a0] hover:cursor-pointer"
                                                    >
                                                        Revisar
                                                    </button>
                                                </form>
                                            @elseif (auth()->user()->rol?->nombre === 'SUPER ADMIN')
                                                <form
                                                    method="POST"
                                                    action="{{ route('lotes.reabrir', $lote) }}"
                                                >
                                                    @csrf
                                                    @method('PATCH')
                                                    <button
                                                        type="submit"
                                                        onclick="return confirm('¿Deseas reabrir este lote?')"
                                                        class="rounded-lg border border-orange-300 bg-orange-50 px-4 py-2 text-sm font-semibold text-orange-700 transition hover:bg-orange-100 hover:cursor-pointer inline-flex items-center gap-1.5"
                                                    >
                                                        <x-icon name="openFolder" class="text-orange-700" size="20"/>
                                                        Reabrir
                                                    </button>
                                                </form>
                                            @endif
                                        {{-- EN VERIFICACIÓN --}}
                                        @elseif ($lote->estado === 'EN_VERIFICACION')
                                            @if (auth()->user()->rol?->nombre === 'ADMIN')
                                                <x-modal
                                                    titulo="Revisión del lote {{ $lote->codigo_lote }}"
                                                    ancho="max-w-5xl"
                                                >
                                                    <x-slot:trigger>
                                                        <button
                                                            type="button"
                                                            @click="loteSeleccionadoId = {{ $lote->id }}"
                                                            class="rounded-lg bg-[#1a34c9] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#1428a0]"
                                                        >
                                                            Continuar revisión
                                                        </button>
                                                    </x-slot:trigger>

                                                    @include('aportes.lotes.partials.revisar', [
                                                        'lote' => $lote
                                                    ])
                                                </x-modal>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="7"
                                    class="px-5 py-12 text-center text-sm text-gray-500"
                                >
                                    No existen lotes activos registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINACIÓN --}}
            <div class="mt-6">
                {{ $lotes->links() }}
            </div>
        @endif
    </div>

    {{-- MODAL: PREVISUALIZACIÓN DE APORTES --}}
    <x-modal
        titulo="Previsualización de aportes"
        ancho="max-w-6xl"
        x-model="mostrarPreview"
    >
        @include('aportes.lotes.partials.confirmar')
    </x-modal>

</div>

@endsection

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('revisionLotes', () => ({
            seleccionados: [],
            loteSeleccionadoId: null,
            aportesPreview: [],
            cargandoPreview: false,
            mostrarPreview: false,
            observacionesPagos: {},

            // MONTO TOTAL DE LA PREVISUALIZACIÓN
            montoSeleccionado() {
                return this.aportesPreview
                    .filter(aporte => aporte.seleccionado)
                    .reduce(
                        (total, aporte) =>
                            total + Number(aporte.monto),
                        0
                    )
                    .toFixed(2);
            },

            //ABRIR PREVISUALIZACIÓN
            async abrirPrevisualizacion() {
                if (this.seleccionados.length === 0) {
                    return;
                }

                if (!this.loteSeleccionadoId) {

                    alert(
                        'No se pudo determinar el lote que se está revisando.'
                    );

                    return;
                }
                this.cargandoPreview = true;
                try {
                    const response = await fetch(
                        '{{ route('lotes.preview-aportes', '__LOTE__') }}'
                            .replace(
                                '__LOTE__',
                                this.loteSeleccionadoId
                            ),
                        {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                            body: JSON.stringify({
                                pago_ids: this.seleccionados,
                            }),
                        }
                    );

                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(
                            data.message ??
                            'No se pudo generar la previsualización.'
                        );
                    }
                    this.aportesPreview = data.aportes;
                    this.mostrarPreview = true;
                } catch (error) {
                    console.error(error);
                    alert(error.message);
                } finally {
                    this.cargandoPreview = false;
                }
            },
            //Datos de pago
            datosPago(pagoId) {
                const aportes = this.aportesPreview.filter(
                    aporte => Number(aporte.pago_id) === Number(pagoId)
                );

                if (aportes.length === 0) {
                    return {
                        nombre: '',
                        ci: '',
                        periodos: 0,
                        monto: '0.00',
                    };
                }

                const monto = aportes.reduce(
                    (total, aporte) => total + Number(aporte.monto),
                    0
                );

                return {
                    nombre: aportes[0].nombre,
                    ci: aportes[0].ci,
                    periodos: aportes.length,
                    monto: monto.toFixed(2),
                };
            },

            // PAGOS COMPLETAMENTE PROCESABLES
            pagosProcesables() {
               const pagos = {};
                this.aportesPreview.forEach(aporte => {
                    if (!pagos[aporte.pago_id]) {
                        pagos[aporte.pago_id] = {
                            total: 0,
                            seleccionados: 0,
                        };
                    }
                    pagos[aporte.pago_id].total++;
                    if (aporte.seleccionado) {
                        pagos[aporte.pago_id].seleccionados++;
                    }
                });
                return Object.entries(pagos)
                    .filter(([pagoId, datos]) =>
                        datos.total === datos.seleccionados
                    )
                    .map(([pagoId]) => Number(pagoId));
            },
            // PAGOS OBSERVADOS
            pagosObservados() {
                const pagos = {};
                this.aportesPreview.forEach(aporte => {
                    if (!pagos[aporte.pago_id]) {
                        pagos[aporte.pago_id] = {
                            total: 0,
                            seleccionados: 0,
                        };
                    }
                    pagos[aporte.pago_id].total++;
                    if (aporte.seleccionado) {
                        pagos[aporte.pago_id].seleccionados++;
                    }
                });
                return Object.entries(pagos)
                    .filter(([pagoId, datos]) =>
                        datos.seleccionados < datos.total
                    )
                    .map(([pagoId]) => Number(pagoId));
            },
            // CERRAR PREVISUALIZACIÓN
            cerrarPrevisualizacion() {
                this.mostrarPreview = false;
                this.aportesPreview = [];
                this.observacionesPagos = {};
            },
            // CONFIRMAR REVISIÓN
            async confirmarPrevisualizacion() {
               if (this.seleccionados.length === 0) {
                    return;
                }
                if (!this.loteSeleccionadoId) {
                    alert(
                        'No se pudo determinar el lote que se está revisando.'
                    );
                    return;
                }
                const pagosObservados = this.pagosObservados();
                const pagosSinObservacion = pagosObservados.filter(
                    pagoId => !this.observacionesPagos[pagoId]?.trim()
                );
                if (pagosSinObservacion.length > 0) {
                    alert('Debes ingresar una observacion para cada pago que sera observado');
                    return;
                }

                const mensaje = pagosObservados.length > 0
                    ? `Se procesarán ${this.pagosProcesables().length} pago(s) y se observarán ${pagosObservados.length} pago(s). ¿Deseas continuar?`
                    : `Se procesarán ${this.pagosProcesables().length} pago(s). ¿Deseas continuar?`;

                if (!confirm(mensaje)) {
                    return;
                }
                try {
                    const response = await fetch(
                        '{{ route('lotes.confirmar-aportes', '__LOTE__') }}'
                            .replace(
                                '__LOTE__',
                                this.loteSeleccionadoId
                            ),
                        {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                            body: JSON.stringify({
                                pago_ids: this.seleccionados,
                                pagos_observados: pagosObservados,
                                observaciones_pagos: this.observacionesPagos,
                            }),
                        }
                    );
                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(
                            data.message ??
                            'No se pudo confirmar la revisión.'
                        );
                    }
                    alert(data.message);
                    // Cerramos la previsualización.
                    this.mostrarPreview = false;

                    // Limpiamos la información temporal.
                    this.aportesPreview = [];
                    this.seleccionados = [];
                    this.observacionesPagos = {};
                    // Recargamos para actualizar el estado y los totales.
                    window.location.reload();
                } catch (error) {
                    console.error(error);
                    alert(error.message);
                }
            },
        }));
    });
</script>
