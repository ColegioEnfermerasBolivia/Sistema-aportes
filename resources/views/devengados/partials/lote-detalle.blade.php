@php
    $pagosLote = $lote->pagos;
    $estadoLote = [
        'ABIERTO' => ['Abierto', 'border-blue-200 bg-blue-50 text-blue-700'],
        'ENVIADO' => ['Enviado', 'border-[#b8d8e2] bg-[#edf7fa] text-[#176078]'],
        'EN_VERIFICACION' => ['En verificación', 'border-orange-200 bg-orange-50 text-orange-600'],
        'VERIFICADO' => ['Verificado', 'border-green-200 bg-green-50 text-green-700'],
    ][$lote->estado] ?? [str_replace('_', ' ', $lote->estado), 'border-gray-200 bg-gray-50 text-gray-700'];
@endphp

<div class="space-y-8">
    <section>
        <div class="mb-5">
            <h3 class="text-lg font-semibold text-gray-900">Información del lote</h3>
            <p class="mt-1 text-sm text-gray-500">Información general y estado actual del lote devengado.</p>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <p class="text-sm text-gray-500">Código del lote</p>
                <p class="mt-1 font-medium text-gray-900">{{ $lote->codigo_lote }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Colegio</p>
                <p class="mt-1 font-medium text-gray-900">{{ $lote->colegio->nombre }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Período del lote</p>
                <p class="mt-1 font-medium text-gray-900">
                    @if ($lote->mes && $lote->year)
                        {{ str_pad((string) $lote->mes, 2, '0', STR_PAD_LEFT) }}/{{ $lote->year }}
                    @else
                        Lote anterior sin período asignado
                    @endif
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Estado</p>
                <span class="mt-1 inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium {{ $estadoLote[1] }}">
                    {{ $estadoLote[0] }}
                </span>
            </div>
            <div>
                <p class="text-sm text-gray-500">Fecha de creación</p>
                <p class="mt-1 font-medium text-gray-900">{{ $lote->creado_en?->format('d/m/Y H:i') ?? '—' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Creado por</p>
                <p class="mt-1 font-medium text-gray-900">
                    {{ trim(($lote->creadoPor?->nombres ?? '').' '.($lote->creadoPor?->apellidos ?? '')) ?: '—' }}
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Cantidad de pagos</p>
                <p class="mt-1 font-medium text-gray-900">{{ $pagosLote->count() }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Importe pagado</p>
                <p class="mt-1 font-medium text-gray-900">Bs {{ number_format((float) $pagosLote->sum('monto_total'), 2, ',', '.') }}</p>
            </div>
        </div>

        @if ($lote->observaciones)
            <div class="mt-5 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">
                <span class="font-semibold">Observaciones:</span> {{ $lote->observaciones }}
            </div>
        @endif
    </section>

    @if ($lote->fecha_envio || $lote->fecha_revision || $lote->enviadoPor || $lote->revisadoPor)
        <section>
            <div class="mb-5">
                <h3 class="text-lg font-semibold text-gray-900">Seguimiento del lote</h3>
                <p class="mt-1 text-sm text-gray-500">Fechas y usuarios relacionados con el proceso del lote.</p>
            </div>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @if ($lote->fecha_envio)
                    <div>
                        <p class="text-sm text-gray-500">Fecha de envío</p>
                        <p class="mt-1 font-medium text-gray-900">{{ $lote->fecha_envio->format('d/m/Y H:i') }}</p>
                    </div>
                @endif
                @if ($lote->enviadoPor)
                    <div>
                        <p class="text-sm text-gray-500">Enviado por</p>
                        <p class="mt-1 font-medium text-gray-900">{{ $lote->enviadoPor->nombres }} {{ $lote->enviadoPor->apellidos }}</p>
                    </div>
                @endif
                @if ($lote->fecha_revision)
                    <div>
                        <p class="text-sm text-gray-500">Fecha de revisión</p>
                        <p class="mt-1 font-medium text-gray-900">{{ $lote->fecha_revision->format('d/m/Y H:i') }}</p>
                    </div>
                @endif
                @if ($lote->revisadoPor)
                    <div>
                        <p class="text-sm text-gray-500">Revisado por</p>
                        <p class="mt-1 font-medium text-gray-900">{{ $lote->revisadoPor->nombres }} {{ $lote->revisadoPor->apellidos }}</p>
                    </div>
                @endif
            </div>
        </section>
    @endif

    <section>
        <div class="mb-5">
            <h3 class="text-lg font-semibold text-gray-900">Pagos del lote</h3>
            <p class="mt-1 text-sm text-gray-500">Detalle de los pagos y períodos anteriores incluidos.</p>
        </div>
        <div class="max-h-[55vh] overflow-auto rounded-xl border border-gray-200">
            <table class="w-full min-w-[1100px] text-left text-sm">
                <thead class="sticky top-0 z-10 bg-gray-50 text-xs uppercase tracking-wide text-gray-600 shadow-[inset_0_-1px_0_#e5e7eb]">
                    <tr>
                        <th class="px-4 py-3">Afiliada</th>
                        <th class="px-4 py-3">CI</th>
                        <th class="px-4 py-3">Aporte mensual</th>
                        <th class="px-4 py-3">Importe pagado</th>
                        <th class="px-4 py-3">Períodos cubiertos</th>
                        <th class="px-4 py-3">Saldo generado</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Observaciones</th>
                        @if (auth()->user()->rol?->nombre === 'USUARIO')
                            <th class="px-4 py-3">Acciones</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($pagosLote as $pago)
                        <tr class="transition hover:bg-gray-50">
                            <td class="px-4 py-4 font-medium text-gray-900">{{ $pago->afiliado->apellidos }}, {{ $pago->afiliado->nombres }}</td>
                            <td class="px-4 py-4 text-gray-700">{{ $pago->afiliado->ci }}</td>
                            <td class="whitespace-nowrap px-4 py-4">
                                @if ($pago->monto_mensual !== null && $pago->monto_mensual > 0)
                                    Bs {{ number_format((float) $pago->monto_mensual, 2, ',', '.') }}
                                @else
                                    <span class="text-gray-400">No registrado</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">Bs {{ number_format((float) $pago->monto_total, 2, ',', '.') }}</td>
                            <td class="px-4 py-4">
                                @foreach ($pago->detalles as $detalle)
                                    <span class="mr-1 inline-block rounded bg-gray-100 px-2 py-1 text-xs">
                                        {{ str_pad((string) $detalle->periodo->mes, 2, '0', STR_PAD_LEFT) }}/{{ $detalle->periodo->year }}
                                    </span>
                                @endforeach
                                @if ($pago->detalles->isEmpty())
                                    <span class="text-xs text-gray-500">Saldo sin períodos</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">Bs {{ number_format((float) $pago->saldo_generado, 2, ',', '.') }}</td>
                            <td class="px-4 py-4">
                                @php
                                    $estadoPago = [
                                        'EN_LOTE' => ['En lote', 'bg-yellow-100 text-yellow-800'],
                                        'OBSERVADO' => ['Observado', 'bg-orange-100 text-orange-800'],
                                        'VERIFICADO' => ['Verificado', 'bg-green-100 text-green-700'],
                                    ][$pago->estado] ?? [str_replace('_', ' ', $pago->estado), 'bg-gray-100 text-gray-700'];
                                @endphp
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $estadoPago[1] }}">{{ $estadoPago[0] }}</span>
                            </td>
                            <td class="px-4 py-4 text-sm">
                                @if ($pago->observaciones)
                                    <div class="max-w-xs rounded-lg bg-orange-50 px-3 py-2 text-orange-800">{{ $pago->observaciones }}</div>
                                @else
                                    <span class="text-gray-400">Sin observaciones</span>
                                @endif
                            </td>
                            @if (auth()->user()->rol?->nombre === 'USUARIO')
                                <td class="whitespace-nowrap px-4 py-4">
                                    @can('update', $pago)
                                        <a href="{{ route('devengados.pagos.edit', $pago) }}"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-[#1a34c9] px-3 py-2 text-sm font-semibold text-[#1a34c9] transition hover:bg-blue-50">
                                            <x-icon name="edit" class="text-[#1a34c9]" size="18"/>
                                            Editar
                                        </a>
                                    @endcan
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->rol?->nombre === 'USUARIO' ? 9 : 8 }}" class="px-4 py-10 text-center text-sm text-gray-500">Este lote todavía no tiene pagos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

</div>
