@php
    $datosIniciales = [
        'gestiones' => $gestiones,
        'seleccionados' => $seleccionados,
        'lotes' => $lotesDisponibles,
        'afiliada' => $afiliada ? [
            'id' => $afiliada->id,
            'ci' => $afiliada->ci,
            'nombres' => $afiliada->nombres,
            'apellidos' => $afiliada->apellidos,
            'estado' => $afiliada->estado,
        ] : null,
        'pago' => $pago ? [
            'monto_total' => $pago->monto_total,
            'monto_mensual' => $pago->monto_mensual,
            'metodo_determinacion' => $pago->detalles->first()?->periodo?->metodo_determinacion,
            'observaciones' => $pago->observaciones,
            'clave_idempotencia' => $pago->clave_idempotencia,
        ] : null,
        'lote_id' => $pago?->devengado_lote_id,
    ];
@endphp

<div x-data="devengadoPagoForm(@js($datosIniciales))" class="space-y-5">
    @if ($modoEdicion)
        <div>
            <p class="mt-1 text-sm text-gray-600">El pago solo puede editarse antes de que el lote entre en verificación.</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST"
        action="{{ $modoEdicion ? route('devengados.pagos.update', $pago) : route('devengados.pagos.store') }}"
        class="space-y-5">
        @csrf
        @if ($modoEdicion)
            @method('PUT')
        @else
            <input type="hidden" name="clave_idempotencia" x-model="clave">
            <input type="hidden" name="afiliado_id" :value="afiliada?.id ?? ''">
            <input type="hidden" name="devengado_lote_id" x-model="loteId">
        @endif
        <template x-for="(periodo, indice) in seleccionados" :key="periodo">
            <input type="hidden" name="periodos[]" :value="periodo">
        </template>

        @if (! $modoEdicion)
            <div>
                <label for="ci_devengado" class="mb-1.5 block text-sm font-medium text-gray-700">Buscar por CI, nombres o apellidos</label>
                <div class="flex gap-2">
                    <input type="text" id="ci_devengado" x-model="ci"
                        @keydown.enter.prevent="buscarAfiliada()"
                        placeholder="CI, nombres y apellidos"
                        class="min-w-0 flex-1 rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-[#1a34c9] focus:ring-[#1a34c9]">
                    <button type="button" @click="buscarAfiliada()" :disabled="buscando"
                        class="rounded-xl bg-[#1a34c9] px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-60">
                        <span x-text="buscando ? 'Buscando...' : 'Buscar'"></span>
                    </button>
                </div>
                <p x-show="mensaje" x-cloak x-text="mensaje" class="mt-2 text-sm"
                    :class="resultados.length > 0 ? 'text-blue-700' : 'text-red-600'"></p>
                <div x-show="resultados.length > 0" x-cloak
                    class="mt-3 max-h-64 space-y-2 overflow-y-auto rounded-xl border border-gray-200 bg-white p-3">
                    <template x-for="resultado in resultados" :key="resultado.id">
                        <button type="button" @click="seleccionarAfiliada(resultado.id)"
                            class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-left text-sm hover:bg-gray-50">
                            <span class="font-semibold text-gray-900"
                                x-text="`${resultado.apellidos}, ${resultado.nombres}`"></span>
                            <span class="ml-2 text-gray-500"
                                x-text="`CI ${resultado.ci || 'no registrado'}`"></span>
                            <span x-show="resultado.colegio" class="ml-2 text-gray-500"
                                x-text="resultado.colegio"></span>
                        </button>
                    </template>
                    <p x-show="hayMasResultados" class="text-xs text-gray-500">
                        Hay más coincidencias. Agrega más términos para afinar la búsqueda.
                    </p>
                </div>
            </div>
        @endif

        <template x-if="afiliada">
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Afiliada</p>
                        <p class="mt-1 font-semibold text-gray-900" x-text="`${afiliada.apellidos}, ${afiliada.nombres}`"></p>
                        <p class="text-sm text-gray-500" x-text="`CI ${afiliada.ci || 'no registrado'}`"></p>
                    </div>
                    <span x-show="afiliada.estado !== 'ACTIVO'" class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">Afiliada inactiva</span>
                </div>
                <div x-show="camposFaltantes.length > 0" x-cloak
                    class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm text-amber-800">
                    No se puede registrar el pago. La afiliada debe completar:
                    <span class="font-semibold" x-text="camposFaltantes.join(', ')"></span>.
                </div>
            </div>
        </template>

        <div x-show="afiliada && afiliada.estado === 'ACTIVO'" x-cloak class="space-y-5">
            @if (! $modoEdicion)
                <label class="block text-sm font-medium text-gray-700">
                    Lote devengado abierto
                    <select x-model="loteId" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                        <option value="">Selecciona un lote</option>
                        <template x-for="lote in lotes" :key="lote.id">
                            <option :value="lote.id" x-text="`${lote.codigo_lote} · ${String(lote.mes).padStart(2, '0')}/${lote.year}`"></option>
                        </template>
                    </select>
                    <span x-show="lotes.length === 0" class="mt-1 block text-xs text-red-600">No hay lotes abiertos para el colegio de esta afiliada.</span>
                </label>
            @else
                <div class="rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm text-gray-700">
                    Lote: <span class="font-semibold">{{ $pago->lote->codigo_lote }}</span>
                </div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label for="monto_total" class="mb-1.5 block text-sm font-medium text-gray-700">
                        Pago total (Bs)
                    </label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm font-medium text-gray-400">Bs</span>
                        <input type="number" id="monto_total" name="monto_total" x-model="montoTotal" min="0.01" step="0.01" required
                            placeholder="0.00"
                            class="w-full rounded-xl border border-gray-300 bg-white py-2.5 pl-10 pr-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-[#1a34c9] focus:ring-2 focus:ring-[#1a34c9]/10">
                    </div>
                </div>

                <div>
                    <label for="monto_mensual" class="mb-1.5 block text-sm font-medium text-gray-700">
                        Aporte mensual calculado (Bs)
                    </label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm font-medium text-gray-400">Bs</span>
                        <input type="number" id="monto_mensual" name="monto_mensual" x-model="montoMensual" min="0.01" step="0.01" required
                            placeholder="0.00"
                            class="w-full rounded-xl border border-gray-300 bg-white py-2.5 pl-10 pr-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-[#1a34c9] focus:ring-2 focus:ring-[#1a34c9]/10">
                    </div>
                </div>

                <div class="sm:col-span-2 lg:col-span-1">
                    <label for="metodo_determinacion" class="mb-1.5 block text-sm font-medium text-gray-700">
                        Determinación del importe
                    </label>
                    <select id="metodo_determinacion" name="metodo_determinacion" x-model="metodo" required
                        class="w-full rounded-xl border border-gray-300 bg-white py-2.5 pl-4 pr-10 text-sm text-gray-900 outline-none transition focus:border-[#1a34c9] focus:ring-2 focus:ring-[#1a34c9]/10">
                        <option value="MINIMO_VIGENTE">Mínimo vigente</option>
                        <option value="BOLETAS_SALARIALES">Boletas salariales históricas</option>
                    </select>
                </div>
            </div>

            <div class="max-h-[42vh] overflow-auto rounded-xl border border-gray-200 bg-white">
                <table class="min-w-[900px] w-full text-center text-sm">
                    <thead class="sticky top-0 z-10 bg-gray-50 text-xs font-semibold uppercase text-gray-600 shadow-[inset_0_-1px_0_#e5e7eb]">
                        <tr>
                            <th class="sticky left-0 bg-gray-50 px-3 py-3">Gestión</th>
                            <th class="px-2 py-3">ENE</th><th class="px-2 py-3">FEB</th>
                            <th class="px-2 py-3">MAR</th><th class="px-2 py-3">ABR</th>
                            <th class="px-2 py-3">MAY</th><th class="px-2 py-3">JUN</th>
                            <th class="px-2 py-3">JUL</th><th class="px-2 py-3">AGO</th>
                            <th class="px-2 py-3">SEP</th><th class="px-2 py-3">OCT</th>
                            <th class="px-2 py-3">NOV</th><th class="px-2 py-3">DIC</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="gestion in gestiones" :key="gestion.year">
                            <tr>
                                <th class="whitespace-nowrap bg-gray-50 px-3 py-3 font-semibold text-gray-800">
                                    <label class="inline-flex items-center gap-2">
                                        <input type="checkbox" :checked="gestionSeleccionada(gestion)"
                                            :disabled="!gestion.meses.some(mes => mes.disponible)"
                                            @change="seleccionarGestion(gestion, $event.target.checked)"
                                            class="rounded border-gray-300 text-[#1a34c9] focus:ring-[#1a34c9]">
                                        <span x-text="gestion.year"></span>
                                    </label>
                                </th>
                                <template x-for="mes in 12" :key="mes">
                                    <td class="px-2 py-2">
                                        <template x-if="datoMes(gestion, mes)">
                                            <label class="inline-flex items-center justify-center">
                                                <input type="checkbox"
                                                    :checked="seleccionados.includes(claveMes(gestion.year, mes))"
                                                    :disabled="!datoMes(gestion, mes).disponible"
                                                    @change="seleccionarMes(gestion.year, mes, $event.target.checked)"
                                                    :aria-label="`Seleccionar ${mes}/${gestion.year}`"
                                                    class="rounded border-gray-300 text-[#1a34c9] focus:ring-[#1a34c9]">
                                            </label>
                                        </template>
                                        <span x-show="datoMes(gestion, mes) && !datoMes(gestion, mes).disponible"
                                            x-text="datoMes(gestion, mes)?.estado === 'REGISTRADO' ? '✓' : '—'"
                                            class="text-xs text-gray-400"></span>
                                    </td>
                                </template>
                            </tr>
                        </template>
                        <tr x-show="gestiones.length === 0">
                            <td colspan="13" class="px-4 py-8 text-center text-gray-500">Busca una afiliada para mostrar sus períodos.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-gray-500">Solo se pueden seleccionar lagunas desde el mes de afiliación hasta el mes actual. Los períodos ya registrados o reservados no se pueden volver a elegir.</p>

            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <span class="text-sm font-medium text-gray-700">Saldo del pago después de los meses seleccionados</span>
                    <strong :class="saldoRestanteCentavos < 0 ? 'text-red-700' : 'text-emerald-700'"
                        x-text="`Bs ${formatearMonto(saldoRestanteCentavos)}`"></strong>
                </div>
                <p class="mt-2 text-xs text-gray-500" x-show="saldoRestanteCentavos >= 0">
                    El saldo positivo se registrará como saldo disponible cuando central verifique el pago; no se contará como aporte.
                </p>
                <p class="mt-2 text-xs text-gray-500" x-show="seleccionados.length === 0 && saldoRestanteCentavos > 0">
                    Si aún no se cubre un mes completo, puedes registrar el pago como saldo; no se generará ningún aporte hasta seleccionar períodos.
                </p>
                <p class="mt-2 text-xs text-red-700" x-show="saldoRestanteCentavos < 0">
                    El total debe cubrir el aporte mensual de todos los meses seleccionados.
                </p>
            </div>

            <label class="block text-sm font-medium text-gray-700">
                Observaciones
                <textarea name="observaciones" x-model="observaciones" rows="2" cols="2" maxlength="1000"
                    class="mt-1 block w-full rounded-lg border border-gray-300 text-sm p-2"></textarea>
            </label>
        </div>

        <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
            @if ($modoEdicion)
                <button type="button" @click="$dispatch('cerrar-modal-devengado')" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancelar</button>
            @else
                <button type="button" @click="$dispatch('cerrar-modal-devengado')" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancelar</button>
            @endif
            <button type="submit"
                :disabled="!puedeRegistrar"
                class="rounded-lg bg-[#16A34A] px-5 py-2.5 text-sm font-semibold text-white hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50">
                Registrar pago en lote
            </button>
        </div>
        <p
            x-show="!puedeRegistrar"
            x-cloak
            class="text-right text-xs text-gray-500"
            x-text="motivoBloqueoRegistro"
        ></p>
    </form>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('devengadoPagoForm', initial => ({
            ci: initial.afiliada?.ci ?? '',
            afiliada: initial.afiliada,
            camposFaltantes: initial.afiliada?.campos_faltantes_pago ?? [],
            gestiones: initial.gestiones ?? [],
            lotes: initial.lotes ?? [],
            loteId: initial.lote_id ?? (initial.lotes?.length === 1 ? initial.lotes[0].id : ''),
            seleccionados: (initial.seleccionados ?? []).map(periodo =>
                typeof periodo === 'string'
                    ? periodo
                    : `${periodo.year}-${String(periodo.mes).padStart(2, '0')}`
            ),
            montoTotal: initial.pago?.monto_total ?? '',
            montoMensual: initial.pago?.monto_mensual ?? '',
            metodo: initial.pago?.metodo_determinacion ?? 'MINIMO_VIGENTE',
            observaciones: initial.pago?.observaciones ?? '',
            clave: initial.pago?.clave_idempotencia ?? '',
            buscando: false,
            mensaje: '',
            resultados: [],
            hayMasResultados: false,

            init() {
                if (!this.clave) {
                    this.clave = this.nuevaClave();
                }
            },

            nuevaClave() {
                if (window.crypto?.randomUUID) {
                    return window.crypto.randomUUID();
                }

                return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, caracter => {
                    const aleatorio = Math.random() * 16 | 0;
                    return (caracter === 'x' ? aleatorio : (aleatorio & 0x3 | 0x8)).toString(16);
                });
            },

            get saldoRestanteCentavos() {
                const total = Math.round(Number(this.montoTotal || 0) * 100);
                const mensual = Math.round(Number(this.montoMensual || 0) * 100);
                return total - mensual * this.seleccionados.length;
            },

            get puedeRegistrar() {
                return this.afiliada?.estado === 'ACTIVO'
                    && this.camposFaltantes.length === 0
                    && Boolean(this.loteId)
                    && Number(this.montoTotal) > 0
                    && Number(this.montoMensual) > 0
                    && this.saldoRestanteCentavos >= 0
                    && !this.buscando;
            },

            get motivoBloqueoRegistro() {
                if (this.buscando) return 'Espera a que termine la búsqueda de la afiliada.';
                if (!this.afiliada) return 'Busca una afiliada por CI, nombres o apellidos para habilitar el registro.';
                if (this.afiliada.estado !== 'ACTIVO') return 'No se pueden registrar pagos para una afiliada inactiva.';
                if (this.camposFaltantes.length > 0) {
                    return `Completa estos datos antes de registrar un pago: ${this.camposFaltantes.join(', ')}.`;
                }
                if (!this.loteId) return 'Selecciona un lote devengado abierto.';
                if (Number(this.montoTotal) <= 0 || Number(this.montoMensual) <= 0) {
                    return 'Ingresa el importe total y el aporte mensual.';
                }
                if (this.saldoRestanteCentavos < 0) {
                    return 'El importe no alcanza para cubrir los períodos seleccionados.';
                }
                return '';
            },

            async buscarAfiliada() {
                this.mensaje = '';
                this.afiliada = null;
                this.camposFaltantes = [];
                this.gestiones = [];
                this.lotes = [];
                this.loteId = '';
                this.seleccionados = [];
                this.resultados = [];
                this.hayMasResultados = false;
                const valor = this.ci.trim();
                if (!valor) {
                    this.mensaje = 'Ingresa un CI, nombres o apellidos para realizar la búsqueda.';
                    return;
                }

                this.buscando = true;
                try {
                    const respuesta = await fetch(
                        '{{ route('devengados.buscar-afiliada') }}?buscar=' + encodeURIComponent(valor),
                        { headers: { Accept: 'application/json' } }
                    );
                    const datos = await respuesta.json();
                    if (!respuesta.ok) {
                        this.mensaje = datos.message ?? 'No se encontró la afiliada.';
                        return;
                    }

                    if (datos.lista) {
                        this.resultados = datos.resultados;
                        this.hayMasResultados = datos.hay_mas_resultados;
                        this.mensaje = 'Selecciona la afiliada correcta.';
                        return;
                    }

                    this.mostrarAfiliada(datos);
                } catch (error) {
                    this.mensaje = 'No se pudo completar la búsqueda. Intenta nuevamente.';
                } finally {
                    this.buscando = false;
                }
            },

            async seleccionarAfiliada(id) {
                this.buscando = true;
                this.mensaje = '';
                this.resultados = [];
                this.hayMasResultados = false;

                try {
                    const respuesta = await fetch(
                        '{{ route('devengados.buscar-afiliada') }}?afiliado_id=' + encodeURIComponent(id),
                        { headers: { Accept: 'application/json' } }
                    );
                    const datos = await respuesta.json();
                    if (!respuesta.ok) {
                        this.mensaje = datos.message ?? 'No se pudo cargar la afiliada seleccionada.';
                        return;
                    }

                    this.mostrarAfiliada(datos);
                } catch (error) {
                    this.mensaje = 'No se pudo cargar la afiliada seleccionada.';
                } finally {
                    this.buscando = false;
                }
            },

            mostrarAfiliada(datos) {
                this.afiliada = datos;
                this.camposFaltantes = datos.campos_faltantes_pago ?? [];
                this.gestiones = datos.gestiones ?? [];
                this.lotes = datos.lotes ?? [];
                this.loteId = this.lotes.length === 1 ? this.lotes[0].id : '';
                this.clave = this.nuevaClave();
                if (this.lotes.length === 0) {
                    this.mensaje = 'No hay un lote devengado abierto para el colegio de esta afiliada.';
                }
            },

            claveMes(year, mes) {
                return `${year}-${String(mes).padStart(2, '0')}`;
            },

            datoMes(gestion, mes) {
                return gestion.meses.find(item => Number(item.mes) === mes);
            },

            seleccionarMes(year, mes, checked) {
                const clave = this.claveMes(year, mes);
                this.seleccionados = checked
                    ? [...new Set([...this.seleccionados, clave])]
                    : this.seleccionados.filter(item => item !== clave);
            },

            gestionSeleccionada(gestion) {
                const disponibles = gestion.meses.filter(item => item.disponible);
                return disponibles.length > 0
                    && disponibles.every(item => this.seleccionados.includes(this.claveMes(gestion.year, item.mes)));
            },

            seleccionarGestion(gestion, checked) {
                gestion.meses
                    .filter(item => item.disponible)
                    .forEach(item => this.seleccionarMes(gestion.year, Number(item.mes), checked));
            },

            formatearMonto(centavos) {
                return (centavos / 100).toLocaleString('es-BO', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
        }));
    });
</script>
@endpush
