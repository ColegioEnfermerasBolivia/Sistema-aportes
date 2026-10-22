
{{-- MODAL DE REVISIÓN DE TRANSFERENCIAS --}}
<div
    x-data="revisionTransferencias()"
    x-cloak
    x-show="abierto"
    x-transition.opacity
    @keydown.escape.window="cerrar()"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    style="display: none;"
>
    <div
        @click.outside="cerrar()"
        class="flex max-h-[92vh] w-full max-w-7xl flex-col overflow-hidden rounded-xl bg-white shadow-xl"
    >
        {{-- ENCABEZADO --}}
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Revisión de transferencias
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Revisa los registros y corrige los errores antes de importar.
                </p>
            </div>

            <button
                type="button"
                @click="cerrar()"
                class="rounded-lg px-3 py-1 text-2xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                aria-label="Cerrar revisión"
            >
                &times;
            </button>
        </div>

        {{-- RESUMEN --}}
        <div class="grid grid-cols-1 gap-3 border-b border-gray-200 bg-gray-50 p-5 sm:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <p class="text-sm text-gray-500">Total de registros</p>
                <p class="mt-1 text-2xl font-semibold text-gray-800"
                   x-text="filas.length"></p>
            </div>

            <div class="rounded-lg border border-green-200 bg-white p-4">
                <p class="text-sm text-gray-500">Listos para importar</p>
                <p class="mt-1 text-2xl font-semibold text-green-700"
                   x-text="cantidadListos"></p>
            </div>

            <div class="rounded-lg border border-red-200 bg-white p-4">
                <p class="text-sm text-gray-500">Con errores</p>
                <p class="mt-1 text-2xl font-semibold text-red-600"
                   x-text="cantidadErrores"></p>
            </div>
        </div>

        {{-- MENSAJE --}}
        <div
            x-show="mensaje"
            x-text="mensaje"
            class="mx-6 mt-4 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800"
        ></div>

        {{-- FILTROS --}}
        <div class="flex flex-col gap-3 border-b border-gray-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    @click="filtro = 'TODOS'"
                    :class="filtro === 'TODOS'
                        ? 'bg-[#1a34c9] text-white'
                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    class="rounded-lg px-3 py-2 text-sm font-medium"
                >
                    Todos
                </button>

                <button
                    type="button"
                    @click="filtro = 'LISTO'"
                    :class="filtro === 'LISTO'
                        ? 'bg-green-700 text-white'
                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    class="rounded-lg px-3 py-2 text-sm font-medium"
                >
                    Listos
                </button>

                <button
                    type="button"
                    @click="filtro = 'ERROR'"
                    :class="filtro === 'ERROR'
                        ? 'bg-red-600 text-white'
                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    class="rounded-lg px-3 py-2 text-sm font-medium"
                >
                    Errores
                </button>
            </div>

            <p class="text-sm text-gray-500">
                Los registros corregidos deben validarse nuevamente.
            </p>
        </div>

        {{-- TABLA --}}
        <div class="min-h-0 flex-1 overflow-auto">
            <table class="min-w-[1250px] w-full text-left text-sm">
                <thead class="sticky top-0 z-10 bg-gray-100 text-xs uppercase tracking-wide text-gray-600">
                    <tr>
                        <th class="px-4 py-3">Fila</th>
                        <th class="px-4 py-3">CI</th>
                        <th class="px-4 py-3">Colegio origen</th>
                        <th class="px-4 py-3">Colegio destino</th>
                        <th class="px-4 py-3">Fecha</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Observaciones</th>
                        <th class="px-4 py-3">Acción</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    <template
                        x-for="(fila, indice) in filasFiltradas"
                        :key="fila.clave"
                    >
                        <tr class="align-top hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-gray-500"
                                x-text="fila.numero_fila"></td>

                            {{-- CI --}}
                            <td class="px-4 py-3">
                                <template x-if="!fila.editando">
                                    <span x-text="fila.ci"></span>
                                </template>
                                <template x-if="fila.editando">
                                    <input
                                        type="text"
                                        x-model="fila.ci"
                                        class="w-32 rounded-md border border-gray-300 px-2 py-1"
                                    >
                                </template>
                            </td>

                            {{-- COLEGIO ORIGEN --}}
                            <td class="px-4 py-3">
                                <template x-if="!fila.editando">
                                    <span x-text="fila.colegio_origen"></span>
                                </template>
                                <template x-if="fila.editando">
                                    <input
                                        type="text"
                                        x-model="fila.colegio_origen"
                                        class="w-48 rounded-md border border-gray-300 px-2 py-1"
                                    >
                                </template>
                            </td>

                            {{-- COLEGIO DESTINO --}}
                            <td class="px-4 py-3">
                                <template x-if="!fila.editando">
                                    <span x-text="fila.colegio_destino"></span>
                                </template>
                                <template x-if="fila.editando">
                                    <input
                                        type="text"
                                        x-model="fila.colegio_destino"
                                        class="w-48 rounded-md border border-gray-300 px-2 py-1"
                                    >
                                </template>
                            </td>

                            {{-- FECHA --}}
                            <td class="px-4 py-3">
                                <template x-if="!fila.editando">
                                    <span x-text="fila.fecha_transferencia"></span>
                                </template>
                                <template x-if="fila.editando">
                                    <input
                                        type="text"
                                        x-model="fila.fecha_transferencia"
                                        placeholder="dd/mm/aaaa"
                                        class="w-32 rounded-md border border-gray-300 px-2 py-1"
                                    >
                                </template>
                            </td>

                            {{-- ESTADO --}}
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="fila.estado === 'LISTO'
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-red-100 text-red-700'"
                                    x-text="fila.estado === 'LISTO' ? 'LISTO' : 'ERROR'"
                                ></span>
                            </td>

                            {{-- ERRORES --}}
                            <td class="max-w-xs px-4 py-3">
                                <template x-if="fila.errores && fila.errores.length">
                                    <ul class="list-inside list-disc space-y-1 text-xs text-red-600">
                                        <template
                                            x-for="(error, i) in fila.errores"
                                            :key="i"
                                        >
                                            <li x-text="error"></li>
                                        </template>
                                    </ul>
                                </template>

                                <template x-if="!fila.errores || !fila.errores.length">
                                    <span class="text-xs text-green-700">
                                        Sin observaciones
                                    </span>
                                </template>
                            </td>

                            {{-- ACCIONES POR FILA --}}
                            <td class="whitespace-nowrap px-4 py-3">
                                <button
                                    type="button"
                                    x-show="!fila.editando"
                                    @click="editar(fila)"
                                    class="font-medium text-blue-700 hover:underline"
                                >
                                    Corregir
                                </button>

                                <button
                                    type="button"
                                    x-show="fila.editando"
                                    @click="guardarEdicion(fila)"
                                    class="font-medium text-green-700 hover:underline"
                                >
                                    Validar
                                </button>

                                <button
                                    type="button"
                                    x-show="fila.editando"
                                    @click="cancelarEdicion(fila)"
                                    class="ml-2 font-medium text-gray-500 hover:underline"
                                >
                                    Descartar
                                </button>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="filasFiltradas.length === 0">
                        <td colspan="8" class="px-4 py-10 text-center text-gray-500">
                            No hay registros para mostrar con este filtro.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- PIE --}}
        <div class="flex flex-col gap-3 border-t border-gray-200 bg-white px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs text-gray-500">
                La revisión no guarda cambios en la base de datos.
            </p>

            <div class="flex flex-wrap justify-end gap-3">
                <button
                    type="button"
                    @click="cerrar()"
                    class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100"
                >
                    Cerrar
                </button>

                <button
                    type="button"
                    @click="confirmarImportacion()"
                    :disabled="cantidadListos === 0 || cantidadErrores > 0 || procesando"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#1a34c9] px-5 py-2.5 text-sm font-medium text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <x-icon name="check-circle" size="18"/>
                    <span x-text="procesando ? 'Importando...' : 'Confirmar importación'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function revisionTransferencias() {
        return {
            abierto: @json(session('mostrar_revision_transferencias', false)),
            filas: @json(session('filas_revision_transferencias', [])),
            filtro: 'TODOS',
            mensaje: '',
            procesando: false,
            filaEnEdicion: null,

            get cantidadListos() {
                return this.filas.filter(fila => fila.estado === 'LISTO').length;
            },

            get cantidadErrores() {
                return this.filas.filter(fila => fila.estado === 'ERROR').length;
            },

            get filasFiltradas() {
                if (this.filtro === 'TODOS') {
                    return this.filas;
                }

                return this.filas.filter(fila => fila.estado === this.filtro);
            },

            editar(fila) {
                this.filaEnEdicion = {
                    clave: fila.clave,
                    ci: fila.ci,
                    colegio_origen: fila.colegio_origen,
                    colegio_destino: fila.colegio_destino,
                    fecha_transferencia: fila.fecha_transferencia,
                };

                fila.editando = true;
            },

            cancelarEdicion(fila) {
                if (this.filaEnEdicion?.clave === fila.clave) {
                    Object.assign(fila, this.filaEnEdicion);
                    fila.editando = false;
                    this.filaEnEdicion = null;
                }
            },

            async guardarEdicion(fila) {
                this.mensaje = '';
                this.procesando = true;

                try {
                    const respuesta = await fetch(
                        @json(route('transferencias.validar-fila')),
                        {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': @json(csrf_token()),
                            },
                            body: JSON.stringify({
                                clave: fila.clave,
                                ci: fila.ci,
                                colegio_origen: fila.colegio_origen,
                                colegio_destino: fila.colegio_destino,
                                fecha_transferencia: fila.fecha_transferencia,
                            }),
                        }
                    );

                    const datos = await respuesta.json();

                    if (!respuesta.ok) {
                        throw new Error(datos.message || 'No se pudo validar la fila.');
                    }

                    Object.assign(fila, datos.fila);
                    fila.editando = false;
                    this.filas = datos.filas
                    this.filaEnEdicion = null;
                    this.mensaje = 'La fila se validó nuevamente.';
                } catch (error) {
                    this.mensaje = error.message || 'Ocurrió un error al validar la fila.';
                } finally {
                    this.procesando = false;
                }
            },

            async confirmarImportacion() {
                if (this.cantidadListos === 0 || this.cantidadErrores > 0) {
                    this.mensaje = 'Corrige todos los errores antes de importar.';
                    return;
                }

                if (!confirm('¿Confirmas la importación de estas transferencias?')) {
                    return;
                }

                this.procesando = true;

                try {
                    const respuesta = await fetch(
                        @json(route('transferencias.confirmar')),
                        {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': @json(csrf_token()),
                            },
                        }
                    );

                    const datos = await respuesta.json();

                    if (!respuesta.ok) {
                        throw new Error(datos.message || 'No se pudo completar la importación.');
                    }

                    window.location.reload();
                } catch (error) {
                    this.mensaje = error.message || 'Ocurrió un error al importar.';
                } finally {
                    this.procesando = false;
                }
            },

            cerrar() {
                this.abierto = false;
            },
        };
    }
</script>
