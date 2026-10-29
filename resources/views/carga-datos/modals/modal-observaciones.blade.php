@if (!empty($token))
    <div
        x-data="{ mostrarObservacionesHistoricas: @js($abierto ?? true) }"
        x-cloak
    >
        <x-modal
            x-model="mostrarObservacionesHistoricas"
            titulo="Previsualización de observaciones históricas"
            ancho="max-w-6xl"
        >
            <div class="space-y-5">
                <p class="text-sm text-gray-500">
                    Revisa los registros antes de importarlos.
                </p>

                {{-- Resumen --}}
                <div class="text-sm text-gray-600">
                    Se analizaron
                    <strong>{{ $total }}</strong>
                    filas.

                    Correctas:
                    <strong class="text-green-700">
                        {{ $correctos }}
                    </strong>

                    Errores:
                    <strong class="text-red-700">
                        {{ $errores }}
                    </strong>

                    CI no encontrados:
                    <strong class="text-amber-700">
                        {{ $noEncontrados }}
                    </strong>
                </div>

                {{-- Tabla --}}
                <div class="max-h-[55vh] overflow-auto rounded-xl border border-gray-200">
                    <table class="w-full text-left text-sm">
                        <thead class="sticky top-0 bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-4 py-3">Fila</th>
                                <th class="px-4 py-3">CI</th>
                                <th class="px-4 py-3">Afiliado</th>
                                <th class="px-4 py-3">Observación</th>
                                <th class="px-4 py-3">Estado</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse ($filas as $fila)
                                <tr>
                                    <td class="px-4 py-3">
                                        {{ $fila['fila'] }}
                                    </td>

                                    <td class="px-4 py-3">
                                        {{ $fila['ci_original'] }}
                                    </td>

                                    <td class="px-4 py-3">
                                        {{ $fila['nombre'] ?? '—' }}
                                    </td>

                                    <td class="min-w-64 whitespace-normal px-4 py-3">
                                        {{ $fila['observacion'] }}
                                    </td>

                                    <td class="px-4 py-3">
                                        @if ($fila['estado'] === 'OK')
                                            <span class="font-medium text-green-700">
                                                Correcto
                                            </span>
                                        @elseif ($fila['estado'] === 'NO_ENCONTRADO')
                                            <span class="font-medium text-amber-700">
                                                CI no encontrado
                                            </span>
                                        @else
                                            <span class="font-medium text-red-700">
                                                {{ $fila['mensaje'] ?? 'Error' }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="5"
                                        class="px-4 py-8 text-center text-gray-500"
                                    >
                                        El archivo no contiene observaciones.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Acciones --}}
                <div class="flex flex-col justify-between gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center">
                    <p class="text-sm text-gray-500">
                        Se importarán las {{ $correctos }} observaciones correctas.
                        Las filas con errores o CI no encontrados se omitirán.
                    </p>

                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="mostrarObservacionesHistoricas = false"
                            class="rounded-lg border border-gray-300 px-4 py-2
                                   text-gray-700 hover:bg-gray-50"
                        >
                            Cerrar
                        </button>

                        <form
                            method="POST"
                            action="{{ route('observacionesHistoricas.confirmar', $token) }}"
                        >
                            @csrf

                            <button
                                type="submit"
                                @disabled($correctos === 0)
                                class="rounded-lg bg-green-600 px-5 py-2 text-sm
                                       font-semibold text-white hover:bg-green-700
                                       disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                Importar observaciones ({{ $correctos }})
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </x-modal>
    </div>
@endif