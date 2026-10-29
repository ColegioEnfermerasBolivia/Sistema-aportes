<div class="space-y-8">

    {{-- INFORMACIÓN DEL LOTE --}}
    <section>
        <div class="mb-5">
            <h3 class="text-lg font-semibold text-gray-900">
                Información del lote
            </h3>

            <p class="mt-1 text-sm text-gray-500">
                Información general y estado actual del lote.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">

            {{-- Código --}}
            <div>
                <p class="text-sm text-gray-500">
                    Código del lote
                </p>

                <p class="mt-1 font-medium text-gray-900">
                    {{ $lote->codigo_lote }}
                </p>
            </div>

            {{-- Colegio --}}
            <div>
                <p class="text-sm text-gray-500">
                    Colegio
                </p>

                <p class="mt-1 font-medium text-gray-900">
                    {{ $lote->colegio->nombre }}
                </p>
            </div>

            {{-- Período --}}
            <div>
                <p class="text-sm text-gray-500">
                    Período
                </p>

                <p class="mt-1 font-medium text-gray-900">
                    {{ str_pad($lote->mes, 2, '0', STR_PAD_LEFT) }}/{{ $lote->year }}
                </p>
            </div>

            {{-- Estado --}}
            <div>
                <p class="text-sm text-gray-500">
                    Estado
                </p>
                <div class="mt-1">
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
                            Enviado
                        </span>

                    @elseif ($lote->estado === 'EN_VERIFICACION')
                        <span
                            class="inline-flex items-center rounded-full border border-orange-200 bg-orange-50 px-3 py-1 text-xs font-medium text-orange-600"
                        >
                            En verificación
                        </span>

                    @elseif ($lote->estado === 'VERIFICADO')
                        <span
                            class="inline-flex items-center rounded-full border border-green-200 bg-green-50 px-3 py-1 text-xs font-medium text-green-700"
                        >
                            Verificado
                        </span>
                    @else
                        <span
                            class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-medium text-gray-700"
                        >
                            {{ $lote->estado }}
                        </span>
                    @endif
                </div>
            </div>

            {{-- Cantidad de aportes --}}
            <div>
                <p class="text-sm text-gray-500">
                    Cantidad de aportes
                </p>

                <p class="mt-1 font-medium text-gray-900">
                    {{ $lote->cantidad_aportes }}
                </p>
            </div>

            {{-- Monto total --}}
            <div>
                <p class="text-sm text-gray-500">
                    Monto total
                </p>

                <p class="mt-1 font-medium text-gray-900">
                    Bs. {{ number_format($lote->monto_total, 2) }}
                </p>
            </div>
        </div>
    </section>

    {{-- INFORMACIÓN DEL ENVÍO / VERIFICACIÓN --}}
    @if (
        $lote->fecha_cierre_departamental ||
        $lote->fecha_recepcion_col_nacional ||
        $lote->fecha_verificacion ||
        $lote->enviadoPor ||
        $lote->verificadoPor
    )
        <section>
            <div class="mb-5">
                <h3 class="text-lg font-semibold text-gray-900">
                    Seguimiento del lote
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Fechas y usuarios relacionados con el proceso del lote.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">

                {{-- Fecha de cierre --}}
                @if ($lote->fecha_cierre_departamental)
                    <div>
                        <p class="text-sm text-gray-500">
                            Fecha de envío
                        </p>
                        <p class="mt-1 font-medium text-gray-900">
                            {{ $lote->fecha_cierre_departamental }}
                        </p>
                    </div>
                @endif

                {{-- Usuario que envió --}}
                @if ($lote->enviadoPor)
                    <div>
                        <p class="text-sm text-gray-500">
                            Enviado por
                        </p>
                        <p class="mt-1 font-medium text-gray-900">
                            {{ $lote->enviadoPor->nombres }}
                            {{ $lote->enviadoPor->apellidos }}
                        </p>
                    </div>
                @endif

                {{-- Fecha de recepción --}}
                @if ($lote->fecha_recepcion_col_nacional)
                   <div>
                        <p class="text-sm text-gray-500">
                            Recepción en Colegio Nacional
                        </p>
                        <p class="mt-1 font-medium text-gray-900">
                            {{ $lote->fecha_recepcion_col_nacional }}
                        </p>
                    </div>
                @endif

                {{-- Fecha de verificación --}}
                @if ($lote->fecha_verificacion)
                    <div>
                        <p class="text-sm text-gray-500">
                            Fecha de verificación
                        </p>
                        <p class="mt-1 font-medium text-gray-900">
                            {{ $lote->fecha_verificacion }}
                        </p>
                    </div>
                @endif

                {{-- Usuario que verificó --}}
                @if ($lote->verificadoPor)
                    <div>
                        <p class="text-sm text-gray-500">
                            Verificado por
                        </p>
                        <p class="mt-1 font-medium text-gray-900">
                            {{ $lote->verificadoPor->nombres }}
                            {{ $lote->verificadoPor->apellidos }}
                        </p>
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- PAGOS DEL LOTE --}}
    <section>
        <div class="mb-5">
            <h3 class="text-lg font-semibold text-gray-900">
                Pagos del lote
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                Detalle de los pagos registrados en este lote.
            </p>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200">
            <table class="min-w-[900px] w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Afiliado
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            CI
                        </th>                       
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Monto mensual
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Monto total
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Períodos
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Estado
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Saldo
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Observaciones
                        </th>
                        @if ( auth()->user()->rol?->nombre === 'USUARIO' && $lote->estado === 'ABIERTO' ) <th class="px-4 py-3 text-center"> Acciones </th> @endif
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($lote->pagos as $pago)
                        <tr class="transition hover:bg-gray-50"> {{-- AFILIADO --}} 
                            <td class="px-4 py-4 text-sm text-gray-900"> 
                                <div class="font-medium"> {{ $pago->afiliado->nombres }} {{ $pago->afiliado->apellidos }} 
                                </div> 
                            </td> 
                            {{-- CI --}} 
                            <td class="px-4 py-4 text-sm text-gray-600"> {{ $pago->afiliado->ci }} 
                            </td>                             
                            {{-- MONTO MENSUAL --}}
                            <td class="px-4 py-4 text-sm text-gray-900">
                                @if ($pago->monto_mensual !== null && $pago->monto_mensual > 0)
                                    Bs. {{ number_format($pago->monto_mensual, 2) }}
                                @else
                                    <span class="text-gray-400">No registrado</span>
                                @endif
                            </td>
                            
                            
                            {{-- MONTO TOTAL --}}
                            <td class="px-4 py-4 text-sm font-medium text-gray-900">
                                Bs. {{ number_format($pago->monto_total, 2) }}
                            </td>
                            
                            {{-- PERÍODOS --}} 
                            <td class="px-4 py-4 text-sm text-gray-900"> {{ $pago->cantidad_periodos }} 
                                </td> 
                                {{-- ESTADO --}} 
                                <td class="px-4 py-4"> 
                                    @if ($pago->estado === 'EN_LOTE') 
                                    <span class="inline-flex rounded-full bg-yellow-100 px-2.5 py-1 text-xs font-semibold text-yellow-800" > EN LOTE 
                                        </span> 
                                        @elseif ($pago->estado === 'OBSERVADO') 
                                        <span class="inline-flex rounded-full bg-orange-100 px-2.5 py-1 text-xs font-semibold text-orange-800" > OBSERVADO 
                                            </span> 
                                            @elseif ($pago->estado === 'VERIFICADO') 
                                            <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700" > VERIFICADO 
                                                </span> @else 
                                                <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700" > 
                                                    {{ $pago->estado }} 
                                                </span> @endif 
                                            </td>
                                            {{-- SALDO ESTIMADO DEL PAGO --}}
                                            <td class="px-4 py-4 text-sm">
                                                @if ($pago->monto_mensual !== null && $pago->monto_mensual > 0)
                                                    @php
                                                        $mensualCentavos = (int) round(
                                                            (float) $pago->monto_mensual * 100
                                                        );
                    
                                                        $totalCentavos = (int) round(
                                                            (float) $pago->monto_total * 100
                                                        );
                    
                                                        if ($pago->estado === 'EN_LOTE') {
                                                            $saldoAnteriorCentavos = $pago->afiliado
                                                                ->saldosActivos
                                                                ->sum(fn ($saldo) => (int) round(
                                                                    (float) $saldo->monto_disponible * 100
                                                                ));
                                                            $saldoEstimadoCentavos =
                                                                ($saldoAnteriorCentavos + $totalCentavos)
                                                                % $mensualCentavos;
                                                        } else {
                                                            $saldoEstimadoCentavos = (int) round(
                                                                (float) $pago->saldos->sum('monto') * 100
                                                            );
                                                        }
                                                    @endphp
                    
                                                    @if ($saldoEstimadoCentavos > 0)
                                                        <span class="font-semibold text-amber-700">
                                                            Bs. {{ number_format($saldoEstimadoCentavos / 100, 2) }}
                                                        </span>
                                                        @if ($pago->estado === 'EN_LOTE')
                                                            <p class="mt-1 text-xs text-gray-400">
                                                                Estimado
                                                            </p>
                                                        @endif
                                                    @else
                                                        <span class="text-gray-500">Bs. 0,00</span>
                                                    @endif
                                                @else
                                                    <span class="text-gray-400">No disponible</span>
                                                @endif
                                            </td>
                            {{-- OBSERVACIONES --}}
                            <td class="px-4 py-4 text-sm">
                                @if ($pago->observaciones)
                                    <div class="max-w-xs rounded-lg bg-orange-50 px-3 py-2 text-orange-800">
                                        {{ $pago->observaciones }}
                                    </div>
                                @else
                                    <span class="text-gray-400">
                                        Sin observaciones
                                    </span>
                                @endif
                            </td>
                            {{-- ACCIONES --}} 
                            @if ( auth()->user()->rol?->nombre === 'USUARIO' 
                                && $lote->estado === 'ABIERTO' ) 
                                <td class="px-4 py-4 text-center"> 
                                    <button type="button" @click="$dispatch('abrir-editar-pago', { pagoId: {{ $pago->id }} })" class="rounded-lg bg-[#1a34c9] px-3 py-2 text-sm font-medium text-white transition hover:bg-[#152ba8]" > Editar 
                                    </button> 
                                </td> 
                            @endif 
                            </tr>
                            @if (
                                auth()->user()->rol?->nombre === 'USUARIO'
                                && $lote->estado === 'ABIERTO'
                            )

                                <div
                                    x-data="{ modalEditarPago: false }"
                                    @abrir-editar-pago.window="
                                        if ($event.detail.pagoId === {{ $pago->id }}) {
                                            modalEditarPago = true;
                                        }"
                                >
                                    <x-modal
                                        titulo="Editar pago"
                                        ancho="max-w-2xl"
                                        x-model="modalEditarPago"
                                    >
                                        @include('aportes.lotes.partials.pago-edit', [
                                            'pago' => $pago
                                        ])
                                    </x-modal>
                                </div>
                            @endif
                         @empty 
                            <tr> 
                                <td colspan="{{ auth()->user()->rol?->nombre === 'USUARIO' && $lote->estado === 'ABIERTO' ? 8 : 7 }}" class="px-4 py-10 text-center text-sm text-gray-500" > Este lote no tiene pagos registrados. 
                                </td> 
                            </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
