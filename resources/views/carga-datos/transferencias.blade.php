<div
    id="modal_transferencias"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4"
>
    <div class="w-full max-w-xl overflow-hidden rounded-xl bg-white shadow-xl">

        {{-- Encabezado --}}
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Carga de transferencias
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Importación del histórico de transferencias de afiliadas.
                </p>
            </div>

            <button
                type="button"
                onclick="cerrarModalTransferencias()"
                class="rounded-lg px-3 py-1 text-2xl text-gray-400 hover:bg-gray-100 hover:text-gray-700"
                aria-label="Cerrar modal"
            >
                &times;
            </button>
        </div>

        {{-- Formulario --}}
        <form
            action="{{ route('transferencias.analizar') }}"
            method="POST"
            enctype="multipart/form-data"
            class="p-6"
        >
            @csrf

            <div>
                <label
                    for="archivo_transferencias"
                    class="mb-2 block text-sm font-medium text-gray-700"
                >
                    Archivo CSV
                </label>

                <input
                    type="file"
                    id="archivo_transferencias"
                    name="archivo"
                    accept=".csv,.txt,text/csv"
                    required
                    class="block w-full cursor-pointer rounded-lg border border-gray-300 text-sm text-gray-600
                           focus:outline-none focus:ring-2 focus:ring-blue-500"
                >

                <p class="mt-2 text-xs text-gray-500">
                    El archivo debe contener las columnas:
                    <span class="font-medium">
                        ci, colegio_origen, colegio_destino, fecha_transferencia
                    </span>.
                </p>

                <p class="mt-2 text-xs text-gray-500">
                    La fecha debe estar en formato día/mes/año, por ejemplo:
                    15/08/2016.
                </p>

                @error('archivo')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Acciones --}}
            <div class="mt-6 flex justify-end gap-3">
                <button
                    type="button"
                    onclick="cerrarModalTransferencias()"
                    class="rounded-lg border border-gray-300 px-5 py-2.5 font-medium text-gray-700 transition hover:bg-gray-100"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="rounded-lg bg-[#1a34c9] px-5 py-2.5 font-medium text-white transition hover:bg-blue-800"
                >
                    Validar archivo
                </button>
            </div>
        </form>
    </div>
</div>
