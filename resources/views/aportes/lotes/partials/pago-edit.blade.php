
<div class="space-y-6">

    {{-- INFORMACIÓN DEL AFILIADO --}}
    <section>
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-gray-900">
                Información del afiliado
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                El afiliado asociado al pago no puede modificarse.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <p class="text-sm text-gray-500">Afiliado</p>
                <p
                    class="mt-1 font-medium text-gray-900"
                    x-text="pagoSeleccionado.afiliado"
                ></p>
            </div>

            <div>
                <p class="text-sm text-gray-500">CI</p>
                <p
                    class="mt-1 font-medium text-gray-900"
                    x-text="pagoSeleccionado.ci"
                ></p>
            </div>
        </div>
    </section>

    {{-- DATOS DEL PAGO --}}
    <section>
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-gray-900">
                Datos del pago
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                Modifica el monto del pago. La cantidad de períodos se calculará automáticamente.
            </p>
        </div>

        <form
            method="POST"
            :action="'{{ route('pagos.update', ['pago' => '__ID__']) }}'.replace('__ID__', pagoSeleccionado.id)"
            class="space-y-5"
        >
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                {{-- MONTO MENSUAL --}}
                <div>
                    <label
                        for="monto_mensual_editar"
                        class="mb-2 block text-sm font-medium text-gray-700"
                    >
                        Monto mensual de referencia
                    </label>

                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-500">
                            Bs.
                        </span>

                        <input
                            type="number"
                            name="monto_mensual"
                            id="monto_mensual_editar"
                            x-model="pagoSeleccionado.montoMensual"
                            min="0.01"
                            step="0.01"
                            required
                            class="w-full rounded-lg border border-gray-300 py-2.5 pl-10 pr-3 text-sm text-gray-900 focus:border-[#1a34c9] focus:outline-none focus:ring-2 focus:ring-[#1a34c9]/20"
                        >
                    </div>
                </div>

                {{-- MONTO TOTAL --}}
                <div>
                    <label
                        for="monto_total_editar"
                        class="mb-2 block text-sm font-medium text-gray-700"
                    >
                        Monto total
                    </label>

                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-500">
                            Bs.
                        </span>

                        <input
                            type="number"
                            name="monto_total"
                            id="monto_total_editar"
                            x-model="pagoSeleccionado.montoTotal"
                            min="0.01"
                            step="0.01"
                            required
                            class="w-full rounded-lg border border-gray-300 py-2.5 pl-10 pr-3 text-sm text-gray-900 focus:border-[#1a34c9] focus:outline-none focus:ring-2 focus:ring-[#1a34c9]/20"
                        >
                    </div>
                </div>
            </div>

            {{-- CANTIDAD DE PERÍODOS --}}
            <div class="rounded-xl border border-blue-200 bg-blue-50 px-5 py-4">
                <p class="text-sm font-medium text-blue-700">
                    Cantidad de períodos
                </p>

                <p
                    class="mt-1 text-2xl font-bold text-blue-800"
                    x-text="pagoSeleccionado.cantidadPeriodos"
                ></p>

                <p class="mt-1 text-xs text-blue-600">
                    Este valor se calculará automáticamente al guardar el pago.
                </p>
            </div>

            {{-- OBSERVACIONES --}}
            <div>
                <label
                    for="observaciones_editar"
                    class="mb-2 block text-sm font-medium text-gray-700"
                >
                    Observaciones
                </label>

                <textarea
                    name="observaciones"
                    id="observaciones_editar"
                    rows="4"
                    maxlength="1000"
                    x-model="pagoSeleccionado.observaciones"
                    class="w-full resize-none rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-[#1a34c9] focus:outline-none focus:ring-2 focus:ring-[#1a34c9]/20"
                    placeholder="Observaciones adicionales sobre el pago..."
                ></textarea>

                <p class="mt-1 text-xs text-gray-500">
                    Máximo 1000 caracteres.
                </p>
            </div>

            {{-- INFORMACIÓN --}}
            <div class="rounded-xl border border-gray-200 bg-gray-50 px-5 py-4">
                <p class="text-sm text-gray-600">
                    Los períodos se recalcularán al guardar, aplicando el saldo
                    disponible del afiliado. Cualquier remanente quedará como
                    saldo para futuros aportes cuando se verifique el pago.
                </p>
            </div>

            {{-- BOTONES --}}
            <div class="flex justify-end gap-3 border-t border-gray-200 pt-5">
                <button
                    type="button"
                    @click="modalEditarPago = false"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-100 inline-flex items-center gap-1.5"
                >
                    <x-icon name="cancel" class="text-gray-700" size="20"/>
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="rounded-lg bg-[#1a34c9] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#1428a0] inline-flex items-center gap-1.5"
                >
                    <x-icon name="save" class="text-white" size="20"/>
                    Guardar cambios
                </button>
            </div>
        </form>
    </section>
</div>
