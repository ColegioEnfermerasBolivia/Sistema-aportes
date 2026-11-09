<div class="space-y-8">
    {{-- INFORMACIÓN DEL LOTE --}}
    <section>
        <h3 class="mb-4 text-lg font-semibold text-gray-900">
            Información del lote
        </h3>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <p class="text-sm text-gray-500">Código del lote</p>
                <p class="font-medium text-gray-900">
                    {{ $lote->codigo_lote }}
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Colegio</p>
                <p class="font-medium text-gray-900">
                    {{ $lote->colegio->nombre }}
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Período</p>
                <p class="font-medium text-gray-900">
                    {{ str_pad($lote->mes, 2, '0', STR_PAD_LEFT) }}/{{ $lote->year }}
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Estado</p>
                <p class="font-medium text-gray-900">
                    {{ $lote->estado }}
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Cantidad de aportes</p>
                <p class="font-medium text-gray-900">
                    {{ $lote->cantidad_aportes }}
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Monto total</p>
                <p class="font-medium text-gray-900">
                    Bs. {{ number_format($lote->monto_total, 2) }}
                </p>
            </div>
        </div>
    </section>

    {{-- PAGOS DEL LOTE --}}
    <section
        @click="loteSeleccionadoId = {{ $lote->id }}"
    >
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-gray-900">
                Pagos del lote
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                Selecciona los pagos que serán procesados para generar sus aportes.
            </p>
        </div>

        <div class="max-h-[80vh] overflow-auto rounded-xl border border-gray-200">
            <table class="w-full min-w-[900px] text-sm">
                <thead class="sticky top-0 z-10 bg-gray-50 px-4 py-3 text-left font-semibold text-gray-600 shadow-[inset_0_-1px_0_#e5e7eb]">
                    <tr>
                        <th class="w-12 px-4 py-3 text-center">
                            <span class="sr-only">
                                Seleccionar
                            </span>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Afiliado
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Monto
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Períodos
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Estado
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($lote->pagos as $pago)
                        <tr>
                            {{-- CHECKBOX --}}
                            <td class="px-4 py-3 text-center">
                                @if ($pago->estado === 'EN_LOTE')
                                    <input
                                        type="checkbox"
                                        :value="{{ $pago->id }}"
                                        x-model="seleccionados"
                                        @change="loteSeleccionado = {{ $lote->id }}"
                                        class="h-4 w-4 rounded border-gray-300 text-[#1a34c9] focus:ring-[#1a34c9]"
                                    >
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>

                            {{-- AFILIADO --}}
                            <td class="px-4 py-3 text-sm text-gray-900">
                                <div class="font-medium">
                                    {{ $pago->afiliado->nombres }}
                                    {{ $pago->afiliado->apellidos }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    CI: {{ $pago->afiliado->ci }}
                                </div>
                            </td>

                            {{-- MONTO --}}
                            <td class="px-4 py-3 text-sm text-gray-900">
                                Bs. {{ number_format($pago->monto_total, 2) }}
                            </td>

                            {{-- PERIODOS --}}
                            <td class="px-4 py-3 text-sm text-gray-900">
                                {{ $pago->cantidad_periodos }}
                            </td>

                            {{-- ESTADO --}}
                            <td class="px-4 py-3">
                                @if ($pago->estado === 'EN_LOTE')
                                    <span class="rounded-full bg-yellow-100 px-2.5 py-1 text-xs font-semibold text-yellow-800">
                                        EN LOTE
                                    </span>

                                @elseif ($pago->estado === 'RECHAZADO')
                                    <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-800">
                                        RECHAZADO
                                    </span>

                                @elseif ($pago->estado === 'VERIFICADO')
                                    <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-800">
                                        VERIFICADO
                                    </span>
                                @else
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">
                                        {{ $pago->estado }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="5"
                                class="px-4 py-8 text-center text-sm text-gray-500"
                            >
                                Este lote no tiene pagos registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- RESUMEN DE SELECCIÓN --}}
        <div
            class="mt-5 flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50 px-5 py-4"
        >
            <div>
                <p class="text-sm font-medium text-gray-700">
                    Pagos seleccionados
                </p>

                <p class="text-2xl font-bold text-[#0d1b6e]">
                    <span x-text="seleccionados.length"></span>
                </p>
            </div>

            {{-- BOTONES --}}
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    @click="abrirPrevisualizacion()"
                    :disabled="seleccionados.length === 0"
                    :class="seleccionados.length === 0
                        ? 'cursor-not-allowed bg-gray-300 text-gray-500'
                        : 'bg-[#16A34A] text-white hover:bg-[#348b54]'"
                    class="rounded-lg px-4 py-2 text-sm font-semibold transition hover:cursor-pointer inline-flex items-center gap-1.5"
                >
                    <x-icon name="cashRegister" class="text-white" size="20"/>
                    Registrar aportes
                </button>
            </div>
        </div>
    </section>
</div>
