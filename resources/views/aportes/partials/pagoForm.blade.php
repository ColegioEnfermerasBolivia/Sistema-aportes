<div
    x-data="{
        ci: '',
        afiliadoId: '',
        nombres: '',
        apellidos: '',
        estado: '',
        ultimoAporte: null,

        mensual: '',
        total: '',

        buscando: false,
        encontrado: false,
        mensaje: '',

        limpiar() { 
            this.ci = ''; 
            this.afiliadoId = ''; 
            this.nombres = ''; 
            this.apellidos = ''; 
            this.estado = ''; 
            this.ultimoAporte = null; 
            this.mensual = ''; 
            this.total = ''; 
            this.buscando = false; 
            this.encontrado = false; 
            this.mensaje = ''; 
        },

        get periodos() {
            const mensual = parseFloat(this.mensual);
            const total = parseFloat(this.total);

            if (
                !mensual ||
                !total ||
                mensual <= 0 ||
                total <= 0
            ) {
                return null;
            }

            const resultado = total / mensual;

            if (!Number.isInteger(resultado)) {
                return '—';
            }

            return resultado;
        },

        get puedeRegistrar() {
            const mensual = parseFloat(this.mensual);
            const total = parseFloat(this.total);

            return (
                this.encontrado &&
                this.estado === 'ACTIVO' &&
                mensual > 0 &&
                total > 0 &&
                Number.isInteger(total / mensual)
            );
        },

        async buscarAfiliado() {

            this.mensaje = '';
            this.encontrado = false;
            this.afiliadoId = '';
            this.nombres = '';
            this.apellidos = '';
            this.estado = '';
            this.ultimoAporte = null;

            const ciBuscado = this.ci.trim();

            if (!ciBuscado) {
                this.mensaje = 'Ingresa un CI para realizar la búsqueda.';
                return;
            }

            this.buscando = true;

            try {

                const respuesta = await fetch(
                    '{{ route('afiliados.buscar-ci') }}?ci=' +
                    encodeURIComponent(ciBuscado),
                    {
                        headers: {
                            'Accept': 'application/json',
                        }
                    }
                );

                const datos = await respuesta.json();

                if (!respuesta.ok) {
                    this.mensaje = datos.message ?? 'No se encontró el afiliado.';
                    return;
                }

                this.afiliadoId = datos.id;
                this.nombres = datos.nombres;
                this.apellidos = datos.apellidos;
                this.estado = datos.estado;
                this.ultimoAporte = datos.ultimo_aporte;

                this.encontrado = true;

            } catch (error) {

                this.mensaje = 'Ocurrió un error al buscar el afiliado.';

            } finally {

                this.buscando = false;

            }
        }
    }"

    @limpiar-formulario-pago.window="limpiar()"
>

    <form
        method="POST"
        action="{{ route('pagos.store') }}"
        class="space-y-6"
    >

        @csrf

        {{-- Afiliado encontrado --}}
        <input
            type="hidden"
            name="afiliado_id"
            x-model="afiliadoId"
        >

        {{-- CI --}}
        <div>

            <label
                for="ci_pago"
                class="mb-1.5 block text-sm font-medium text-gray-700"
            >
                CI del afiliado
            </label>

            <div class="flex gap-2">

                <input
                    type="text"
                    id="ci_pago"
                    x-model="ci"
                    @keydown.enter.prevent="buscarAfiliado()"
                    placeholder="Ingresa el CI..."
                    class="min-w-0 flex-1 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-[#1a34c9] focus:ring-2 focus:ring-[#1a34c9]/10"
                >

                <button
                    type="button"
                    @click="buscarAfiliado()"
                    :disabled="buscando"
                    class="inline-flex shrink-0 items-center justify-center rounded-xl bg-[#1a34c9] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#1429a3] disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <span x-show="!buscando">
                        Buscar
                    </span>

                    <span x-show="buscando" x-cloak>
                        Buscando...
                    </span>
                </button>

            </div>

            {{-- Mensaje de búsqueda --}}
            <p
                x-show="mensaje"
                x-cloak
                x-text="mensaje"
                class="mt-2 text-sm text-red-600"
            ></p>

        </div>


        {{-- Información del afiliado --}}
        <div
            x-show="encontrado"
            x-cloak
            class="rounded-xl border border-gray-200 bg-gray-50 p-4"
        >

            <div class="grid gap-4 sm:grid-cols-2">

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Nombres
                    </p>

                    <p
                        class="mt-1 text-sm font-semibold text-gray-900"
                        x-text="nombres"
                    ></p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Apellidos
                    </p>

                    <p
                        class="mt-1 text-sm font-semibold text-gray-900"
                        x-text="apellidos"
                    ></p>
                </div>

            </div>


            {{-- Estado --}}
            <div
                x-show="estado !== 'ACTIVO'"
                class="mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700"
            >
                Este afiliado se encuentra inactivo y no se puede registrar un pago.
            </div>


            {{-- Último aporte --}}
            <div
                x-show="ultimoAporte"
                class="mt-4 border-t border-gray-200 pt-4"
            >

                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                    Último aporte
                </p>

                <p class="mt-1 text-sm font-semibold text-gray-800">

                    Bs
                    <span
                        x-text="ultimoAporte ? Number(ultimoAporte.monto).toFixed(2) : ''"
                    ></span>

                    <span class="font-normal text-gray-500">
                        —
                        <span
                            x-text="ultimoAporte ? [
                                '',
                                'ENE',
                                'FEB',
                                'MAR',
                                'ABR',
                                'MAY',
                                'JUN',
                                'JUL',
                                'AGO',
                                'SEP',
                                'OCT',
                                'NOV',
                                'DIC'
                            ][ultimoAporte.mes] : ''"
                        ></span>

                        <span
                            x-text="ultimoAporte ? ultimoAporte.year : ''"
                        ></span>
                    </span>

                </p>

            </div>


            <div
                x-show="!ultimoAporte"
                class="mt-4 border-t border-gray-200 pt-4"
            >
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                    Último aporte
                </p>

                <p class="mt-1 text-sm text-gray-400">
                    Sin aportes verificados registrados.
                </p>
            </div>

        </div>


        {{-- Datos del pago --}}
        <div
            x-show="encontrado && estado === 'ACTIVO'"
            x-cloak
            class="space-y-5"
        >

            <div class="border-t border-gray-200 pt-5">

                <h3 class="text-sm font-semibold text-gray-900">
                    Datos del pago
                </h3>

                <p class="mt-1 text-xs text-gray-500">
                    Ingresa el monto mensual de referencia y el monto total pagado.
                </p>

            </div>


            {{-- Monto mensual --}}
            <div>

                <label
                    for="monto_mensual"
                    class="mb-1.5 block text-sm font-medium text-gray-700"
                >
                    Monto mensual
                </label>

                <div class="relative">

                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm text-gray-400">
                        Bs
                    </span>

                    <input
                        type="number"
                        id="monto_mensual"
                        name="monto_mensual"
                        x-model="mensual"
                        min="0.01"
                        step="0.01"
                        required
                        placeholder="0.00"
                        class="w-full rounded-xl border border-gray-300 bg-white py-2.5 pl-10 pr-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-[#1a34c9] focus:ring-2 focus:ring-[#1a34c9]/10"
                    >

                </div>

                <p class="mt-1.5 text-xs text-gray-400">
                    Dato de referencia utilizado para calcular la cantidad de períodos.
                </p>

            </div>


            {{-- Monto total --}}
            <div>

                <label
                    for="monto_total"
                    class="mb-1.5 block text-sm font-medium text-gray-700"
                >
                    Monto total pagado
                </label>

                <div class="relative">

                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm text-gray-400">
                        Bs
                    </span>

                    <input
                        type="number"
                        id="monto_total"
                        name="monto_total"
                        x-model="total"
                        min="0.01"
                        step="0.01"
                        required
                        placeholder="0.00"
                        class="w-full rounded-xl border border-gray-300 bg-white py-2.5 pl-10 pr-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-[#1a34c9] focus:ring-2 focus:ring-[#1a34c9]/10"
                    >

                </div>

            </div>


            {{-- Cantidad de períodos --}}
            <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">

                <div class="flex items-center justify-between gap-4">

                    <span class="text-sm font-medium text-gray-700">
                        Cantidad de períodos
                    </span>

                    <span
                        class="text-lg font-bold text-gray-900"
                        x-text="periodos ?? '—'"
                    ></span>

                </div>

                <p
                    x-show="periodos === '—'"
                    x-cloak
                    class="mt-1 text-xs text-red-600"
                >
                    El monto total debe ser divisible exactamente entre el monto mensual.
                </p>

            </div>


            {{-- Observaciones --}}
            <div>

                <label
                    for="observaciones"
                    class="mb-1.5 block text-sm font-medium text-gray-700"
                >
                    Observaciones
                </label>

                <textarea
                    id="observaciones"
                    name="observaciones"
                    rows="3"
                    maxlength="1000"
                    placeholder="Observaciones opcionales..."
                    class="w-full resize-none rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-[#1a34c9] focus:ring-2 focus:ring-[#1a34c9]/10"
                ></textarea>

            </div>

        </div>


        {{-- Botones --}}
        <div class="flex justify-end gap-3 border-t border-gray-200 pt-5">

            <button
                type="button"
                @click="$dispatch('cerrar-modal-pago')"
                class="rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
            >
                Cancelar
            </button>

            <button
                type="submit"
                :disabled="!puedeRegistrar"
                class="rounded-xl bg-[#1a34c9] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#1429a3] disabled:cursor-not-allowed disabled:opacity-50"
            >
                Registrar pago
            </button>

        </div>

    </form>

</div>
