<div class="space-y-6">
    <div
        x-show="confirmandoAportes"
        x-cloak
        class="fixed inset-0 z-[10000] flex items-center justify-center bg-gray-900/60 px-4 backdrop-blur-sm"
        role="status"
        aria-live="polite"
    >
        <div class="w-full max-w-sm rounded-2xl bg-white p-8 text-center shadow-2xl">
            <div class="mx-auto h-12 w-12 animate-spin rounded-full border-4 border-gray-200 border-t-[#1a34c9]"></div>
            <p class="mt-5 text-lg font-semibold text-gray-900">
                Procesando aportes
            </p>
            <p class="mt-2 text-sm text-gray-500">
                Se están registrando los aportes del lote. No cierres esta ventana.
            </p>
        </div>
    </div>

    <div>
        <h3 class="text-lg font-semibold text-gray-900">
            Previsualización de aportes
        </h3>

        <p class="mt-1 text-sm text-gray-500">
            Revisa los aportes que se generarán antes de confirmarlos.
        </p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-green-200 bg-green-50 px-5 py-4">
            <p class="text-sm font-medium text-green-700">
                Pagos a registrar
            </p>

            <p
                class="mt-1 text-2xl font-bold text-green-800"
                x-text="pagosProcesables().length"
            ></p>
        </div>

        <div class="rounded-xl border border-orange-200 bg-orange-50 px-5 py-4">
            <p class="text-sm font-medium text-orange-700">
                Pagos a observar
            </p>
            <p
                class="mt-1 text-2xl font-bold text-orange-800"
                x-text="pagosObservados().length"
            ></p>
        </div>
    </div>

    <div
        x-show="resumenPagos.some(resumen => Number(resumen.saldo_aplicado) > 0 && !pagosObservados().includes(Number(resumen.pago_id)))"
        class="space-y-3"
    >
        <h4 class="text-sm font-semibold text-gray-900">
            Aplicación de saldos
        </h4>
        <div class="max-h-[80vh] overflow-auto rounded-xl border border-gray-200">
            <table class="w-full min-w-[900px] text-sm">
                <thead class="sticky top-0 z-10 bg-gray-50 px-4 py-3 text-left font-semibold text-gray-600 shadow-[inset_0_-1px_0_#e5e7eb]">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Afiliado
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Saldo anterior
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Saldo aplicado
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Períodos a generar
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Saldo restante estimado
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <template
                        x-for="resumen in resumenPagos.filter(resumen => Number(resumen.saldo_aplicado) > 0 && !pagosObservados().includes(Number(resumen.pago_id)))"
                        :key="resumen.pago_id"
                    >
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                <span x-text="resumen.nombre"></span>
                                <span class="text-gray-500">
                                    (CI: <span x-text="resumen.ci"></span>)
                                </span>
                            </td>
                            <td
                                class="px-4 py-3 text-right text-sm text-gray-900"
                                x-text="'Bs. ' + Number(resumen.saldo_anterior).toFixed(2)"
                            ></td>
                            <td
                                class="px-4 py-3 text-right text-sm font-semibold text-blue-700"
                                x-text="'Bs. ' + Number(resumen.saldo_aplicado).toFixed(2)"
                            ></td>
                            <td
                                class="px-4 py-3 text-right text-sm text-gray-900"
                                x-text="resumen.cantidad_periodos"
                            ></td>
                            <td
                                class="px-4 py-3 text-right text-sm font-semibold text-amber-700"
                                x-text="'Bs. ' + Number(resumen.saldo_final_estimado).toFixed(2)"
                            ></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
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
                        CI
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Mes
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Año
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Monto
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200 bg-white">
                <template
                    x-for="(aporte, index) in aportesPreview"
                    :key="index"
                >
                    <tr>
                        <td class="px-4 py-3 text-center">
                            <input
                                type="checkbox"
                                x-model="aportesPreview[index].seleccionado"
                                class="h-4 w-4 rounded border-gray-300 text-[#1a34c9] focus:ring-[#1a34c9]"
                            >
                        </td>
                        <td
                            class="px-4 py-3 text-sm text-gray-900"
                            x-text="aporte.nombre"
                        ></td>
                        <td
                            class="px-4 py-3 text-sm text-gray-900"
                            x-text="aporte.ci"
                        ></td>
                        <td
                            class="px-4 py-3 text-sm text-gray-900"
                            x-text="String(aporte.mes).padStart(2, '0')"
                        ></td>
                        <td
                            class="px-4 py-3 text-sm text-gray-900"
                            x-text="aporte.year"
                        ></td>
                        <td
                            class="px-4 py-3 text-sm font-medium text-gray-900"
                            x-text="'Bs. ' + Number(aporte.monto).toFixed(2)"
                        ></td>
                    </tr>
                </template>
            </tbody>
        </table>

        <div
            x-show="pagosObservados().length > 0"
            x-cloak
            class="space-y-4"
        >
            <div class="rounded-xl border border-orange-200 bg-orange-50 px-5 py-4">
                <p class="text-sm font-semibold text-orange-800">
                    Hay pagos que serán observados
                </p>

                <p class="mt-1 text-sm text-orange-700">
                    Si un aporte de un mismo pago es desmarcado, el pago completo
                    no será registrado y quedará observado para su conciliación
                    por parte del colegio de origen.
                </p>
            </div>

            <div>
                <h4 class="text-sm font-semibold text-gray-900">
                    Motivo de observación
                </h4>

                <p class="mt-1 text-sm text-gray-500">
                    Registra el motivo correspondiente para cada pago observado.
                </p>
            </div>

            <template
                x-for="pagoId in pagosObservados()"
                :key="pagoId"
            >
                <div class="rounded-xl border border-orange-200 bg-orange-50 p-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">
                            <span x-text="datosPago(pagoId).nombre"></span>
                        </p>

                        <p class="mt-1 text-sm text-gray-600">
                            CI:
                            <span x-text="datosPago(pagoId).ci"></span>
                        </p>

                        <p class="mt-1 text-sm text-gray-600">
                            <span x-text="datosPago(pagoId).periodos"></span>
                            período(s)
                            ·
                            Bs.
                            <span x-text="datosPago(pagoId).monto"></span>
                        </p>

                        <p class="mt-2 text-xs text-orange-700">
                            Pago #<span x-text="pagoId"></span>.
                            Este pago completo quedará observado y no generará aportes.
                        </p>
                    </div>                    
                    <div class="mt-3">
                        <label
                            :for="'observacion-pago-' + pagoId"
                            class="mb-1 block text-sm font-medium text-gray-700"
                        >
                            Observación
                        </label>

                        <textarea
                            :id="'observacion-pago-' + pagoId"
                            x-model="observacionesPagos[pagoId]"
                            rows="3"
                            maxlength="1000"
                            class="p-4 w-full rounded-lg border-gray-300 text-sm focus:border-[#1a34c9] focus:ring-[#1a34c9]"
                            placeholder="Indique el motivo de la observación..."
                        ></textarea>

                        <p class="mt-1 text-right text-xs text-gray-400">
                            Máximo 1000 caracteres
                        </p>
                    </div>

                </div>
            </template>
        </div>
    </div>

    <div class="flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50 px-5 py-4">
        <div>
            <p class="text-sm text-gray-500">
                Aportes seleccionados
            </p>
            <p
                class="text-xl font-bold text-[#0d1b6e]"
                x-text="aportesPreview.filter(aporte => aporte.seleccionado).length"
            ></p>
        </div>

        <div>
            <p class="text-sm text-gray-500">
                Monto seleccionado
            </p>

            <p
                class="text-xl font-bold text-[#0d1b6e]"
                x-text="'Bs. ' + montoSeleccionado()"
            ></p>
        </div>
    </div>


    <div class="flex justify-end gap-3">
        <button
            type="button"
            @click="cerrarPrevisualizacion()"
            :disabled="confirmandoAportes"
            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-100 hover:cursor-pointer inline-flex items-center gap-1.5"
        >
            <x-icon name="cancel" class="text-gray-700" size="20"/>
            Cancelar
        </button>
        <button
            type="button"
            @click="confirmarPrevisualizacion()"
            :disabled="confirmandoAportes"
            class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#9deba1] hover:cursor-pointer disabled:cursor-not-allowed disabled:opacity-60 inline-flex items-center gap-1.5"
        >
            <span x-show="!confirmandoAportes" class="inline-flex items-center gap-1.5">
                <x-icon name="confirm" class="text-white" size="20"/>
                Confirmar aportes
            </span>
            <span x-show="confirmandoAportes" x-cloak>
                Procesando...
            </span>
        </button>
    </div>
</div>