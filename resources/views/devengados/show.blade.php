@extends('layouts.app')

@section('title', 'Lote devengado')

@section('content')
@if (auth()->user()->rol?->nombre === 'ADMIN')
<div class="fixed inset-0 z-[9998] flex items-center justify-center overflow-y-auto bg-gray-900/60 p-3 sm:p-6">
    <div role="dialog" aria-modal="true" class="my-auto max-h-[calc(100vh-1.5rem)] w-full max-w-6xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
@endif
<div class="mx-auto max-w-7xl px-6 py-8 lg:px-10">
    <div class="mb-6">
        <div class="flex items-center justify-between gap-4">
            <a href="{{ route('lotes.index') }}" class="text-sm font-semibold text-[#1a34c9] hover:underline">← Volver a lotes</a>
            @if (auth()->user()->rol?->nombre === 'ADMIN')
                <a href="{{ route('lotes.index') }}" aria-label="Cerrar revisión" class="rounded-full px-3 py-1 text-2xl leading-none text-gray-500 hover:bg-gray-100 hover:text-gray-900">×</a>
            @endif
        </div>
        <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-[#0d1b6e]">{{ $lote->codigo_lote }}</h1>
                <p class="mt-1 text-sm text-gray-600">
                    {{ $lote->colegio->nombre }} · Creado por {{ $lote->creadoPor->nombres }} {{ $lote->creadoPor->apellidos }}
                    @if ($lote->mes && $lote->year)
                        · {{ str_pad((string) $lote->mes, 2, '0', STR_PAD_LEFT) }}/{{ $lote->year }}
                    @else
                        · Lote anterior sin período asignado
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <span class="rounded-full bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-800">{{ str_replace('_', ' ', $lote->estado) }}</span>
                @if (in_array($lote->estado, ['ENVIADO', 'VERIFICADO'], true))
                    <a href="{{ route('devengados.lotes.excel', $lote) }}" class="rounded-lg border border-emerald-700 px-3 py-1.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-50 hover:cursor-pointer">
                        Descargar Excel
                    </a>
                @endif
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @if ($lote->observaciones)
        <div class="mb-5 rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-700">
            <span class="font-semibold">Observaciones del lote:</span> {{ $lote->observaciones }}
        </div>
    @endif

    @if (auth()->user()->rol?->nombre === 'USUARIO' && $lote->estado === 'ABIERTO')
        <form method="POST" action="{{ route('devengados.submit', $lote) }}" class="mb-5"
            data-confirm-title="¿Enviar lote devengado a central?"
            data-confirm-text="Al enviar el lote, los pagos dejarán de estar disponibles para edición."
            data-confirm-button="Sí, enviar">
            @csrf
            @method('PATCH')
            <button type="submit" class="rounded-lg bg-[#1a34c9] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#1429a5]">Enviar a central</button>
        </form>
    @endif

    @if (auth()->user()->rol?->nombre === 'ADMIN' && $lote->estado === 'EN_VERIFICACION')
        <div x-data="revisionDevengados(@js($pagosParaRevision))" class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-4">
            <p class="text-sm text-blue-900">Selecciona los pagos confirmados. Al registrar, cada período se validará antes de crear sus aportes.</p>
            <form method="POST" action="{{ route('devengados.confirmar-pagos', $lote) }}" class="mt-4" @submit="confirmando = true">
                @csrf
                <template x-for="id in seleccionados" :key="id">
                    <input type="hidden" name="pago_ids[]" :value="id">
                </template>
                <div class="max-h-[30vh] overflow-auto rounded-xl border border-gray-200 bg-white">
                    <table class="min-w-[900px] w-full text-left text-sm">
                        <thead class="sticky top-0 z-10 bg-gray-50 text-xs uppercase tracking-wide text-gray-600 shadow-[inset_0_-1px_0_#e5e7eb]">
                            <tr>
                                <th class="px-4 py-3">Confirmar</th>
                                <th class="px-4 py-3">Afiliada</th>
                                <th class="px-4 py-3">CI</th>
                                <th class="px-4 py-3">Monto del pago</th>
                                <th class="px-4 py-3">Aporte mensual</th>
                                <th class="px-4 py-3">Períodos</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($lote->pagos as $pago)
                                <tr>
                                    <td class="px-4 py-4">
                                        @if ($pago->estado === 'EN_LOTE')
                                            <input type="checkbox" :checked="seleccionados.includes({{ $pago->id }})"
                                                @change="seleccionar({{ $pago->id }}, $event.target.checked)"
                                                aria-label="Confirmar pago de {{ $pago->afiliado->nombres }} {{ $pago->afiliado->apellidos }}"
                                                class="rounded border-gray-300 text-[#1a34c9] focus:ring-[#1a34c9]">
                                        @else
                                            <span class="text-xs font-semibold text-green-700">Verificado</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 font-semibold">{{ $pago->afiliado->apellidos }}, {{ $pago->afiliado->nombres }}</td>
                                    <td class="px-4 py-4">{{ $pago->afiliado->ci }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap">Bs {{ number_format((float) $pago->monto_total, 2, ',', '.') }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap">Bs {{ number_format((float) $pago->monto_mensual, 2, ',', '.') }}</td>
                                    <td class="px-4 py-4">{{ $pago->cantidad_periodos }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if (count($pagosParaRevision) > 0)
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                        <p x-show="seleccionados.length === 0" class="text-sm text-gray-600">
                            Selecciona al menos un pago para habilitar el registro.
                        </p>
                        <button type="submit"
                            :disabled="seleccionados.length === 0 || confirmando"
                            class="rounded-lg bg-[#1a34c9] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#1429a5] disabled:cursor-not-allowed disabled:opacity-50">
                            <span x-text="confirmando ? 'Registrando pagos...' : 'Registrar pagos seleccionados'"></span>
                        </button>
                    </div>
                    <div x-show="seleccionados.length > 0" x-cloak class="mt-5">
                        <div class="mb-3">
                            <h3 class="font-semibold text-gray-900">Desglose de aportes y saldos</h3>
                            <p class="mt-1 text-xs text-gray-600">Revisa los períodos que se registrarán. Los saldos no se contabilizan como aportes.</p>
                        </div>
                        <div class="max-h-[35vh] overflow-auto rounded-xl border border-gray-200 bg-white">
                            <table class="min-w-[900px] w-full text-left text-sm">
                                <thead class="sticky top-0 z-10 bg-gray-50 text-xs uppercase tracking-wide text-gray-600 shadow-[inset_0_-1px_0_#e5e7eb]">
                                    <tr>
                                        <th class="px-4 py-3">Afiliada</th>
                                        <th class="px-4 py-3">CI</th>
                                        <th class="px-4 py-3">Gestión</th>
                                        <th class="px-4 py-3">Meses</th>
                                        <th class="px-4 py-3">Aporte mensual</th>
                                        <th class="px-4 py-3">Saldo generado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-for="(fila, indice) in filasPrevisualizacion()" :key="`${fila.id}-${fila.year}-${indice}`">
                                        <tr>
                                            <td class="px-4 py-3" x-text="fila.nombre"></td>
                                            <td class="px-4 py-3" x-text="fila.ci"></td>
                                            <td class="px-4 py-3" x-text="fila.year"></td>
                                            <td class="px-4 py-3" x-text="fila.meses"></td>
                                            <td class="px-4 py-3 whitespace-nowrap" x-text="fila.montoMensual === null ? '—' : `Bs ${Number(fila.montoMensual).toFixed(2)}`"></td>
                                            <td class="px-4 py-3 whitespace-nowrap" x-text="fila.saldoGenerado === null ? '—' : `Bs ${Number(fila.saldoGenerado).toFixed(2)}`"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <p class="mt-4 text-sm text-gray-600">No hay pagos pendientes para registrar en este lote.</p>
                @endif
            </form>
        </div>
    @endif

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="font-semibold text-gray-900">Pagos del lote</h2>
        </div>
        <div class="max-h-[45vh] overflow-auto">
            <table class="min-w-[900px] w-full text-left text-sm">
                <thead class="sticky top-0 z-10 bg-gray-50 text-xs uppercase tracking-wide text-gray-600 shadow-[inset_0_-1px_0_#e5e7eb]">
                    <tr>
                        <th class="px-4 py-3">Afiliada</th>
                        <th class="px-4 py-3">CI</th>
                        <th class="px-4 py-3">Pago</th>
                        <th class="px-4 py-3">Aporte mensual</th>
                        <th class="px-4 py-3">Períodos cubiertos</th>
                        <th class="px-4 py-3">Saldo</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($lote->pagos as $pago)
                        <tr>
                            <td class="px-4 py-4 font-semibold">{{ $pago->afiliado->apellidos }}, {{ $pago->afiliado->nombres }}</td>
                            <td class="px-4 py-4">{{ $pago->afiliado->ci }}</td>
                            <td class="px-4 py-4 whitespace-nowrap">Bs {{ number_format((float) $pago->monto_total, 2, ',', '.') }}</td>
                            <td class="px-4 py-4 whitespace-nowrap">Bs {{ number_format((float) $pago->monto_mensual, 2, ',', '.') }}</td>
                            <td class="px-4 py-4">
                                @foreach ($pago->detalles as $detalle)
                                    <span class="mr-1 inline-block rounded bg-gray-100 px-2 py-1 text-xs">{{ $detalle->periodo->mes }}/{{ $detalle->periodo->year }}</span>
                                @endforeach
                                @if ($pago->detalles->isEmpty())
                                    <span class="text-xs text-gray-500">Sin períodos; se registrará como saldo.</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">Bs {{ number_format((float) $pago->saldo_generado, 2, ',', '.') }}</td>
                            <td class="px-4 py-4"><span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold">{{ str_replace('_', ' ', $pago->estado) }}</span></td>
                            <td class="px-4 py-4">
                                @if (auth()->user()->rol?->nombre === 'USUARIO' && $lote->estado === 'ABIERTO' && $pago->estado === 'EN_LOTE')
                                    <x-modal
                                        titulo="Editar pago devengado"
                                        ancho="max-w-5xl"
                                        @cerrar-modal-devengado.window="abierto = false"
                                    >
                                        <x-slot:trigger>
                                            <button type="button" class="font-semibold text-[#1a34c9] hover:underline">
                                                Editar
                                            </button>
                                        </x-slot:trigger>
                                        @include('devengados.pagos.form', [
                                            'modoEdicion' => true,
                                            'gestiones' => $pago->gestiones_edicion,
                                            'seleccionados' => $pago->seleccionados_edicion,
                                            'lotesDisponibles' => collect([[
                                                'id' => $lote->id,
                                                'codigo_lote' => $lote->codigo_lote,
                                                'mes' => $lote->mes,
                                                'year' => $lote->year,
                                            ]]),
                                            'afiliada' => $pago->afiliado,
                                            'pago' => $pago,
                                        ])
                                    </x-modal>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">Aún no se registraron pagos en este lote.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($lote->periodos->isNotEmpty())
        <details class="group mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <summary class="cursor-pointer list-none border-b border-gray-100 px-5 py-4 font-semibold text-gray-900">
                Períodos y exoneraciones
                <span class="ml-2 text-sm font-normal text-gray-500">({{ $lote->periodos->count() }})</span>
                <span class="float-right text-sm font-medium text-[#1a34c9] group-open:hidden">Mostrar detalle</span>
                <span class="float-right hidden text-sm font-medium text-[#1a34c9] group-open:inline">Ocultar detalle</span>
            </summary>
            @if (! $lote->mes || ! $lote->year)
                <p class="px-5 pt-4 text-xs text-gray-500">Este lote anterior se conserva sin asignarle un período mensual.</p>
            @endif
            <div class="max-h-[55vh] overflow-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="sticky top-0 z-10 bg-gray-50 text-xs uppercase text-gray-600 shadow-[inset_0_-1px_0_#e5e7eb]">
                        <tr>
                            <th class="px-4 py-3">Afiliada</th>
                            <th class="px-4 py-3">Período</th>
                            <th class="px-4 py-3">Importe</th>
                            <th class="px-4 py-3">Pagado / exonerado</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($lote->periodos as $periodo)
                            @php
                                $pagado = $devengadoService->montoPagado($periodo);
                                $exonerado = $devengadoService->montoExonerado($periodo);
                                $pendiente = $devengadoService->saldoPendiente($periodo);
                            @endphp
                            <tr>
                                <td class="px-4 py-3">{{ $periodo->afiliado->apellidos }}, {{ $periodo->afiliado->nombres }}</td>
                                <td class="px-4 py-3">{{ str_pad((string) $periodo->mes, 2, '0', STR_PAD_LEFT) }}/{{ $periodo->year }}</td>
                                <td class="px-4 py-3">Bs {{ number_format((float) $periodo->monto, 2, ',', '.') }}</td>
                                <td class="px-4 py-3">
                                    <div>Pagado: Bs {{ number_format($pagado / 100, 2, ',', '.') }}</div>
                                    <div>Exonerado: Bs {{ number_format($exonerado / 100, 2, ',', '.') }}</div>
                                    <div class="text-xs text-gray-500">Pendiente: Bs {{ number_format($pendiente / 100, 2, ',', '.') }}</div>
                                    @foreach ($periodo->exoneraciones as $exoneracion)
                                        <div class="mt-2 border-t border-gray-100 pt-2 text-xs text-gray-500">
                                            Autorizó: {{ $exoneracion->presidente_nombre }} · Ref. {{ $exoneracion->autorizacion_referencia }}
                                            <br>{{ $exoneracion->motivo }} · {{ $exoneracion->fecha_exoneracion?->format('d/m/Y') }}
                                            · Registró: {{ $exoneracion->registradoPor?->nombres }} {{ $exoneracion->registradoPor?->apellidos }}
                                        </div>
                                    @endforeach
                                </td>
                                <td class="px-4 py-3">{{ str_replace('_', ' ', $periodo->estado) }}</td>
                                <td class="px-4 py-3">
                                    @if (auth()->user()->rol?->nombre === 'ADMIN' && $periodo->estado === 'REGISTRADO' && $pendiente > 0)
                                        <details>
                                            <summary class="cursor-pointer text-xs font-semibold text-red-700 hover:underline">Exonerar con autorización</summary>
                                            <form method="POST" action="{{ route('devengados.exoneraciones.store', $periodo) }}" class="mt-3 min-w-64 space-y-2 rounded-lg border border-red-100 bg-red-50 p-3">
                                                @csrf
                                                <input name="monto" type="number" min="0.01" max="{{ number_format($pendiente / 100, 2, '.', '') }}" step="0.01" required placeholder="Importe Bs" class="w-full rounded border-gray-300 text-xs">
                                                <input name="presidente_nombre" required maxlength="200" placeholder="Nombre del presidente" class="w-full rounded border-gray-300 text-xs">
                                                <input name="autorizacion_referencia" required maxlength="200" placeholder="Referencia de autorización" class="w-full rounded border-gray-300 text-xs">
                                                <textarea name="motivo" required maxlength="2000" rows="2" placeholder="Motivo" class="w-full rounded border-gray-300 text-xs"></textarea>
                                                <button class="rounded bg-red-700 px-3 py-2 text-xs font-semibold text-white">Guardar exoneración</button>
                                            </form>
                                        </details>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    @endif
</div>
@if (auth()->user()->rol?->nombre === 'ADMIN')
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('revisionDevengados', pagos => ({
            pagos,
            seleccionados: [],
            confirmando: false,

            seleccionar(id, checked) {
                this.seleccionados = checked
                    ? [...new Set([...this.seleccionados, Number(id)])]
                    : this.seleccionados.filter(item => item !== Number(id));
            },

            filasPrevisualizacion() {
                return this.pagos
                    .filter(pago => this.seleccionados.includes(Number(pago.id)))
                    .flatMap(pago => {
                        const porGestion = {};
                        pago.periodos.forEach(periodo => {
                            porGestion[periodo.year] ??= [];
                            porGestion[periodo.year].push(Number(periodo.mes));
                        });

                        const gestiones = Object.keys(porGestion).sort();
                        const filas = gestiones.map((year, indice) => ({
                            id: pago.id,
                            nombre: pago.nombre,
                            ci: pago.ci,
                            year,
                            meses: porGestion[year]
                                .sort((a, b) => a - b)
                                .map(mes => String(mes).padStart(2, '0'))
                                .join(', '),
                            montoMensual: Number(pago.monto_mensual),
                            saldoGenerado: indice === 0
                                ? Number(pago.saldo_generado)
                                : null
                        }));

                        return filas.length > 0
                            ? filas
                            : [{
                                id: pago.id,
                                nombre: pago.nombre,
                                ci: pago.ci,
                                year: '—',
                                meses: 'Sin períodos',
                                montoMensual: null,
                                saldoGenerado: Number(pago.saldo_generado)
                            }];
                    });
            }
        }));
    });
</script>
@endpush
