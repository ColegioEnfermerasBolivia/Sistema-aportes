<div class="space-y-6">
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


    <div class="overflow-x-auto rounded-xl border border-gray-200">

        <table class="min-w-full divide-y divide-gray-200">

            <thead class="bg-gray-50">
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
            class="rounded-xl border border-orange-200 bg-orange-50 px-5 py-4"
        >
            <p class="text-sm font-semibold text-orange-800">
                Hay pagos que serán observados
            </p>

            <p class="mt-1 text-sm text-orange-700">
                Si un aporte de un mismo pago es desmarcado, el pago completo
                no será registrado y quedará observado para su conciliación
                por parte del colegio de origen.
            </p>
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
            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-100"
        >
            Cancelar
        </button>

        <button
            type="button"
            @click="confirmarPrevisualizacion()"
            class="rounded-lg bg-[#1a34c9] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#1428a0]"
        >
            Confirmar aportes
        </button>

    </div>

</div>