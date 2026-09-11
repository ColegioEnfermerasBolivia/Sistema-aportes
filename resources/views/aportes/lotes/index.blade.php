@extends('layouts.app')
@section('title', 'Lotes de Aportes')
@section('content')

<div
    x-data="revisionLotes()"
    class="p-6"
>

    <div class="min-h-screen bg-#f7f7f9 px-6 py-8 lg:px-12">

    {{-- ENCABEZADO --}}
        <div class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="m-0 text-3xl font-semibold tracking-tight text-gray-900">Lista de lotes</h1>
                <p class="mt-2 text-base text-gray-600">Administre los lotes de aportes enviados por los colegios</p>
            </div>
        </div>

    {{-- TABLA --}}
        <div class="overflow-x-auto">
            <table class="min-w-[1200px] w-full">
                <thead class="border-b border-gray-200 bg-[#f1f3f8]">
                    <tr>
                        <th class="px-5 py-4 text-left text-sm font-semibold text-gray-800">Código</th>
                        <th class="px-5 py-4 text-left text-sm font-semibold text-gray-800">Colegio</th>
                        <th class="px-5 py-4 text-left text-sm font-semibold text-gray-800">Período</th>
                        <th class="px-5 py-4 text-left text-sm font-semibold text-gray-800">Cant. Aportes</th>
                        <th class="px-5 py-4 text-left text-sm font-semibold text-gray-800">Monto total</th>
                        <th class="px-5 py-4 text-left text-sm font-semibold text-gray-800">Estado</th>
                        <th class="px-5 py-4 text-left text-sm font-semibold text-gray-800">Acciones</th>
                    </tr>
                </thead>
        
                <tbody class="divide-y divide-gray-100">
        
                    @forelse ($lotes as $lote)
        
                        <tr class="transition hover:bg-gray-50">

                            <td class="whitespace-nowrap px-5 py-5 text-sm text-gray-700">{{ $lote->codigo_lote }}</td>
        
                            <td class="whitespace-nowrap px-5 py-5 text-sm text-gray-700">
                                {{ $lote->colegio->nombre }}
                            </td>
        
                            <td class="whitespace-nowrap px-5 py-5 text-sm text-gray-700">
                                {{ str_pad($lote->mes, 2, '0', STR_PAD_LEFT) }}/{{ $lote->year }}
                            </td>
        
                            <td class="whitespace-nowrap px-5 py-5 text-sm text-gray-700">
                                {{ $lote->cantidad_aportes }}
                            </td>
        
                            <td class="whitespace-nowrap px-5 py-5 text-sm text-gray-700">
                                {{ $lote->monto_total }}
                            </td>
        
                            <td class="px-5 py-5">
                                @if ($lote->estado === 'ENVIADO')
                                <span
                                    class="inline-flex items-center
                                        rounded-full
                                        border border-[#b8d8e2]
                                        bg-[#edf7fa]
                                        px-3 py-1
                                        text-xs font-medium
                                        text-[#176078]"
                                >
                                    Recibido
                                </span>

                            @elseif ($lote->estado === 'EN_VERIFICACION')

                                <span
                                    class="inline-flex items-center
                                        rounded-full
                                        border border-red-200
                                        bg-red-50
                                        px-3 py-1
                                        text-xs font-medium
                                        text-orange-600"
                                >
                                    En verificación
                                </span>

                            @elseif ($lote->estado === 'VERIFICADO')

                                <span
                                    class="inline-flex items-center
                                        rounded-full
                                        border border-green-200
                                        bg-green-50
                                        px-3 py-1
                                        text-xs font-medium
                                        text-green-700"
                                >
                                    Verificado
                                </span>

                            @endif
                            </td>
        
                            <td class="px-5 py-5">

                            {{-- Lote enviado: iniciar revisión --}}
                            @if ($lote->estado === 'ENVIADO')

                                <form
                                    method="POST"
                                    action="{{ route('lotes.revisar', $lote) }}"
                                >
                                    @csrf

                                    <button
                                        type="submit"
                                        class="rounded-lg bg-[#1a34c9] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#1428a0]"
                                    >
                                        Revisar
                                    </button>
                                </form>

                            {{-- Lote en proceso de revisión --}}
                            @elseif ($lote->estado === 'EN_VERIFICACION')

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

                            {{-- Lote terminado --}}
                            @elseif ($lote->estado === 'VERIFICADO')

                                <button
                                    type="button"
                                    disabled
                                    class="cursor-not-allowed rounded-lg bg-gray-300 px-4 py-2 text-sm font-semibold text-gray-500"
                                    title="Este lote ya terminó su proceso de verificación"
                                >
                                    Revisión finalizada
                                </button>

                            @endif

                        </td>
                        </tr>
        
                    @empty
        
                        <tr>
                            <td colspan="7">
                                No existen lotes registrados.
                            </td>
                        </tr>
        
                    @endforelse
        
                </tbody>
            </table>
        </div>

        {{ $lotes->links() }}

    </div>
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

            async abrirPrevisualizacion() {

                if (this.seleccionados.length === 0) {
                    return;
                }

                if (!this.loteSeleccionadoId) {
                    alert('No se pudo determinar el lote que se está revisando.');
                    return;
                }

                this.cargandoPreview = true;

                try {

                    const response = await fetch(
                        '{{ route('lotes.preview-aportes', '__LOTE__') }}'
                            .replace('__LOTE__', this.loteSeleccionadoId),
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
                            data.message ?? 'No se pudo generar la previsualización.'
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

            cerrarPrevisualizacion() {
                this.mostrarPreview = false;
                this.aportesPreview = [];
            },

            async confirmarPrevisualizacion() {
                if (this.seleccionados.length === 0) {
                    return;
                }

                if (!this.loteSeleccionadoId) {
                    alert('No se pudo determinar el lote que se está revisando.');
                    return;
                }

                const pagosObservados = this.pagosObservados();

                const mensaje = pagosObservados.length > 0
                    ? `Se procesarán ${this.pagosProcesables().length} pago(s) y se observarán ${pagosObservados.length} pago(s). ¿Deseas continuar?`
                    : `Se procesarán ${this.pagosProcesables().length} pago(s). ¿Deseas continuar?`;

                if (!confirm(mensaje)) {
                    return;
                }

                try {

                    const response = await fetch(
                        '{{ route('lotes.confirmar-aportes', '__LOTE__') }}'
                            .replace('__LOTE__', this.loteSeleccionadoId),
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
                            }),
                        }
                    );

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(
                            data.message ?? 'No se pudo confirmar la revisión.'
                        );
                    }

                    alert(data.message);

                    // Cerramos la previsualización
                    this.mostrarPreview = false;

                    // Limpiamos la información temporal
                    this.aportesPreview = [];
                    this.seleccionados = [];

                    // Recargamos la página para actualizar
                    // estado y totales del lote.
                    window.location.reload();

                } catch (error) {
                    console.error(error);
                    alert(error.message);
                }
            },

        }));
    });
</script>