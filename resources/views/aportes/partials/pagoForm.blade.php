<div
    x-data="{
        ci: '',
        afiliadoId: '',
        nombres: '',
        apellidos: '',
        estado: '',
        ultimoAporte: null,
        montoAporteLaboral: null,

        mensual: '',
        total: '',
        saldoAnterior: '0.00',
        resultados: [],
        hayMasResultados: false,
        camposFaltantes: [],

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
            this.montoAporteLaboral = null;
            this.resultados = [];
            this.hayMasResultados = false;
            this.camposFaltantes = [];
            this.mensual = ''; 
            this.total = ''; 
            this.saldoAnterior = '0.00';
            this.buscando = false; 
            this.encontrado = false; 
            this.mensaje = ''; 
        },
        
        get periodos() {
            const mensual = Math.round(parseFloat(this.mensual) * 100);
            const total = Math.round(parseFloat(this.total) * 100)
                + Math.round(parseFloat(this.saldoAnterior || 0) * 100);

            if (
                !this.mensual ||
                !this.total ||
                !Number.isFinite(mensual) ||
                !Number.isFinite(total) ||
                mensual <= 0 ||
                total <= 0
            ) {
                return null;
            }

            return Math.floor(total / mensual);
        },

        get saldo() {
            const mensual = Math.round(parseFloat(this.mensual) * 100);
            const total = Math.round(parseFloat(this.total) * 100)
                + Math.round(parseFloat(this.saldoAnterior || 0) * 100);

            if (
                !this.mensual ||
                !this.total ||
                !Number.isFinite(mensual) ||
                !Number.isFinite(total) ||
                mensual <= 0 ||
                total <= 0
            ) {
                return null;
            }

            return ((total % mensual) / 100).toFixed(2);
        },

        get puedeRegistrar() {
            const mensual = Math.round(parseFloat(this.mensual) * 100);
            const total = Math.round(parseFloat(this.total) * 100);

            return (
                this.encontrado &&
                this.estado === 'ACTIVO' &&
                this.camposFaltantes.length === 0 &&
                Number.isFinite(mensual) &&
                Number.isFinite(total) &&
                mensual > 0 &&
                total > 0
            );
        },

        async buscarAfiliado() {

            this.mensaje = '';
            this.encontrado = false;
            this.resultados = [];
            this.hayMasResultados = false;
            this.camposFaltantes = [];
            this.afiliadoId = '';
            this.nombres = '';
            this.apellidos = '';
            this.estado = '';
            this.ultimoAporte = null;
            this.montoAporteLaboral = null;
            this.saldoAnterior = '0.00';

            const busqueda = this.ci.trim();

            if (!busqueda) {
                this.mensaje = 'Ingresa un CI, nombres o apellidos para realizar la búsqueda.';
                return;
            }

            this.buscando = true;

            try {

                const respuesta = await fetch(
                    '{{ route('afiliados.buscar-ci') }}?buscar=' +
                    encodeURIComponent(busqueda),
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

                if (datos.ambigua) {
                    this.resultados = datos.resultados;
                    this.hayMasResultados = datos.hay_mas_resultados;
                    this.mensaje = 'Se encontraron varias afiliadas. Selecciona la correcta.';
                    return;
                }

                this.mostrarAfiliado(datos);

            } catch (error) {

                this.mensaje = 'Ocurrió un error al buscar el afiliado.';

            } finally {

                this.buscando = false;

            }
        },

        async seleccionarAfiliado(id) {
            this.buscando = true;
            this.mensaje = '';
            this.resultados = [];

            try {
                const respuesta = await fetch(
                    '{{ route('afiliados.buscar-ci') }}?afiliado_id=' +
                    encodeURIComponent(id),
                    { headers: { 'Accept': 'application/json' } }
                );
                const datos = await respuesta.json();

                if (!respuesta.ok) {
                    this.mensaje = datos.message ?? 'No se pudo cargar la afiliada seleccionada.';
                    return;
                }

                this.mostrarAfiliado(datos);
            } catch (error) {
                this.mensaje = 'Ocurrió un error al cargar la afiliada.';
            } finally {
                this.buscando = false;
            }
        },

        mostrarAfiliado(datos) {
            this.afiliadoId = datos.id;
            this.nombres = datos.nombres;
            this.apellidos = datos.apellidos;
            this.estado = datos.estado;
            this.ultimoAporte = datos.ultimo_aporte;
            this.montoAporteLaboral = datos.monto_aporte_laboral;
            this.saldoAnterior = datos.saldo_disponible ?? '0.00';
            this.camposFaltantes = datos.campos_faltantes_pago ?? [];
            this.encontrado = true;
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
        {{-- Búsqueda de afiliada --}}
        <div>
            <label
                for="ci_pago"
                class="mb-1.5 block text-sm font-medium text-gray-700"
            >
                Buscar por CI, nombres o apellidos
            </label>
            <div class="flex gap-2">
                <input
                    type="text"
                    id="ci_pago"
                    x-model="ci"
                    @keydown.enter.prevent="buscarAfiliado()"
                    placeholder="CI, nombres y apellidos..."
                    class="min-w-0 flex-1 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-[#1a34c9] focus:ring-2 focus:ring-[#1a34c9]/10"
                >
                <button
                    type="button"
                    @click="buscarAfiliado()"
                    :disabled="buscando"
                    class="inline-flex shrink-0 items-center justify-center rounded-xl bg-[#1a34c9] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#1429a3] disabled:cursor-not-allowed disabled:opacity-60 hover:cursor-pointer"
                >
                    <span x-show="!buscando" class="inline-flex items-center gap-1.5">
                        <x-icon name="search" class="text-white" size="20"/>
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
            <div
                x-show="resultados.length > 0"
                x-cloak
                class="mt-3 space-y-2 rounded-xl border border-gray-200 bg-white p-3"
            >
                <template x-for="resultado in resultados" :key="resultado.id">
                    <button
                        type="button"
                        @click="seleccionarAfiliado(resultado.id)"
                        class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-left text-sm hover:bg-gray-50"
                    >
                        <span class="font-semibold text-gray-900" x-text="`${resultado.apellidos}, ${resultado.nombres}`"></span>
                        <span class="ml-2 text-gray-500" x-text="`CI ${resultado.ci || 'no registrado'}`"></span>
                    </button>
                </template>
                <p x-show="hayMasResultados" class="text-xs text-gray-500">
                    Hay más coincidencias. Agrega más términos para afinar la búsqueda.
                </p>
            </div>
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
            <div
                x-show="camposFaltantes.length > 0"
                x-cloak
                class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm text-amber-800"
            >
                No se puede registrar el pago. La afiliada debe completar:
                <span class="font-semibold" x-text="camposFaltantes.join(', ')"></span>.
            </div>

            {{-- Estado --}}
            <div
                x-show="estado !== 'ACTIVO'"
                class="mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700"
            >
                Este afiliado se encuentra inactivo y no se puede registrar un pago.
            </div>

            <div class="mt-4 grid gap-4 border-t border-gray-200 pt-4 sm:grid-cols-2">
                {{-- Último aporte --}}
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Último aporte
                    </p>
                    <p
                        x-show="ultimoAporte"
                        class="mt-1 text-sm font-semibold text-gray-800"
                    >
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
                    <p
                        x-show="!ultimoAporte"
                        class="mt-1 text-sm text-gray-400"
                    >
                        Sin aportes verificados registrados.
                    </p>
                </div>

                {{-- Monto de aporte laboral --}}
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Monto de aporte laboral
                    </p>
                    <p
                        x-show="montoAporteLaboral !== null"
                        class="mt-1 text-sm font-semibold text-gray-800"
                    >
                        Bs
                        <span
                            x-text="montoAporteLaboral !== null ? Number(montoAporteLaboral).toFixed(2) : ''"
                        ></span>
                    </p>
                    <p
                        x-show="montoAporteLaboral === null"
                        class="mt-1 text-sm font-semibold text-gray-800"
                    >
                        15.00 Bs
                    </p>
                </div>
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
            <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 space-y-3">
                <div class="flex items-center justify-between gap-4">
                    <span class="text-sm font-medium text-gray-700">
                        Saldo disponible anterior
                    </span>

                    <span
                        class="text-sm font-semibold text-amber-700"
                        x-text="'Bs ' + Number(saldoAnterior).toFixed(2)"
                    ></span>
                </div>

                <div class="flex items-center justify-between gap-4 border-t border-gray-200 pt-3">
                    <span class="text-sm font-medium text-gray-700">
                        Períodos a cubrir
                    </span>

                    <span
                        class="text-lg font-bold text-gray-900"
                        x-text="periodos ?? '—'"
                    ></span>
                </div>

                <div class="flex items-center justify-between gap-4 border-t border-gray-200 pt-3">
                    <span class="text-sm font-medium text-gray-700">
                        Saldo restante estimado
                    </span>

                    <span
                        class="text-sm font-bold"
                        :class="Number(saldo) > 0 ? 'text-amber-700' : 'text-gray-900'"
                        x-text="saldo !== null ? 'Bs ' + saldo : '—'"
                    ></span>
                </div>

                <p
                    x-show="periodos === 0"
                    x-cloak
                    class="text-xs text-amber-700"
                >
                    El saldo disponible se sumará al pago. El remanente quedará como saldo
                    para futuros aportes una vez verificado.
                </p>

                <p
                    x-show="periodos !== null && periodos > 0 && Number(saldo) > 0"
                    x-cloak
                    class="text-xs text-gray-500"
                >
                    Se aplicará primero el saldo anterior y el remanente podrá utilizarse
                    para futuros aportes.
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
                class="rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 inline-flex items-center gap-1.5 hover:cursor-pointer"
            >
                <x-icon name="cancel" class="text-gray-700" size="20"/>
                Cancelar
            </button>
            <button
                type="submit"
                :disabled="!puedeRegistrar"
                class="rounded-xl bg-[#16A34A] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#46a368] disabled:cursor-not-allowed disabled:opacity-50 hover:cursor-pointer inline-flex items-center gap-1.5"
            >
                <x-icon name="cashRegister" class="text-white" size="20"/>
                Registrar pago
            </button>
        </div>
    </form>
</div>
