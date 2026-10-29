<div
    x-data="{
        mostrar: @js($abierto ?? isset($token)),
        filtro: 'TODAS',
        buscar: ''
    }"
    @abrir-preview-saldos.window="mostrar = true"
>
    <x-modal
        titulo="Previsualización de saldos históricos"
        ancho="max-w-7xl"
        x-model="mostrar"
    >
        @isset($token)
            <div class="space-y-5">

                {{-- Resumen --}}
                <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-xs text-gray-500">Total de filas</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">
                            {{ $total }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-green-200 bg-green-50 p-4">
                        <p class="text-xs text-green-700">Correctas</p>
                        <p class="mt-1 text-2xl font-bold text-green-800">
                            {{ $correctos }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                        <p class="text-xs text-red-700">Errores</p>
                        <p class="mt-1 text-2xl font-bold text-red-800">
                            {{ $errores }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-orange-200 bg-orange-50 p-4">
                        <p class="text-xs text-orange-700">
                            No encontrados / existentes
                        </p>
                        <p class="mt-1 text-2xl font-bold text-orange-800">
                            {{ $noEncontrados + $yaExisten }}
                        </p>
                    </div>
                </div>

                {{-- Filtros --}}
                <div class="flex flex-col gap-3 sm:flex-row">
                    <input
                        type="search"
                        x-model="buscar"
                        placeholder="Buscar por CI o nombre..."
                        class="min-w-0 flex-1 rounded-xl border border-gray-300 px-4 py-2.5 text-sm
                               focus:border-[#1a34c9] focus:outline-none focus:ring-2 focus:ring-[#1a34c9]/10"
                    >

                    <select
                        x-model="filtro"
                        class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm"
                    >
                        <option value="TODAS">Todas las filas</option>
                        <option value="OK">Correctas</option>
                        <option value="ERROR">Con errores</option>
                        <option value="NO_ENCONTRADO">CI no encontrados</option>
                        <option value="YA_EXISTE">Con saldo activo</option>
                    </select>
                </div>

                {{-- Tabla --}}
                <div class="max-h-[55vh] overflow-auto rounded-xl border border-gray-200">
                    <table class="min-w-[900px] w-full divide-y divide-gray-200">
                        <thead class="sticky top-0 bg-gray-50">
                            <tr>
                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                    Fila
                                </th>
                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                    CI del CSV
                                </th>
                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                    Afiliado encontrado
                                </th>
                                <th class="px-3 py-3 text-right text-xs font-semibold uppercase text-gray-500">
                                    Saldo (Bs)
                                </th>
                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                    Resultado
                                </th>
                                <th class="px-3 py-3 text-center text-xs font-semibold uppercase text-gray-500">
                                    Acción
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($filas as $fila)
                                <tr
                                    x-show="
                                        (filtro === 'TODAS' || filtro === @js($fila['estado']))
                                        && (
                                            buscar === ''
                                            || @js(mb_strtolower(
                                                ($fila['ci_original'] ?? '') . ' ' .
                                                ($fila['nombre'] ?? '')
                                            )).includes(buscar.toLowerCase())
                                        )
                                    "
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-3 py-3 text-sm text-gray-600">
                                        {{ $fila['fila'] }}
                                    </td>

                                    <td class="px-3 py-3 text-sm font-medium text-gray-900">
                                        {{ $fila['ci_original'] }}
                                    </td>

                                    <td class="px-3 py-3 text-sm text-gray-700">
                                        {{ $fila['nombre'] ?? '—' }}
                                    </td>

                                    <td class="px-3 py-3 text-right text-sm font-medium text-gray-900">
                                        {{ $fila['saldo'] !== null
                                            ? number_format((float) $fila['saldo'], 2)
                                            : '—' }}
                                    </td>

                                    <td class="px-3 py-3">
                                        @if ($fila['estado'] === 'OK')
                                            <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-800">
                                                Correcto
                                            </span>
                                        @elseif ($fila['estado'] === 'NO_ENCONTRADO')
                                            <span class="rounded-full bg-orange-100 px-2.5 py-1 text-xs font-semibold text-orange-800">
                                                CI no encontrado
                                            </span>
                                        @elseif ($fila['estado'] === 'YA_EXISTE')
                                            <span class="rounded-full bg-yellow-100 px-2.5 py-1 text-xs font-semibold text-yellow-800">
                                                Saldo existente
                                            </span>
                                        @else
                                            <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-800">
                                                Error
                                            </span>
                                        @endif

                                        @if (!empty($fila['mensaje']))
                                            <p class="mt-1 max-w-xs text-xs text-gray-500">
                                                {{ $fila['mensaje'] }}
                                            </p>
                                        @endif
                                    </td>

                                    <td class="px-3 py-3 text-center">
                                        @if ($fila['estado'] !== 'OK')
                                            <button
                                                type="button"
                                                @click="$dispatch('editar-saldo-historico', {
                                                    fila: @js($fila['fila']),
                                                    ci: @js($fila['ci_original'] ?? ''),
                                                    saldo: @js($fila['saldo'])
                                                })"
                                                class="rounded-lg bg-[#1a34c9] px-3 py-2 text-xs font-semibold text-white hover:bg-[#1429a3]"
                                            >
                                                Corregir
                                            </button>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Confirmación --}}
                <div class="flex flex-col justify-between gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center">
                    <p class="text-sm text-gray-500">
                        Solo podrás confirmar cuando todas las filas estén correctas.
                    </p>

                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="mostrar = false"
                            class="rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                        >
                            Cerrar
                        </button>

                        <form
                            method="POST"
                            action="{{ route('saldos.confirmar', $token) }}"
                        >
                            @csrf

                            <button
                                type="submit"
                                @disabled($correctos !== $total)
                                class="rounded-xl bg-green-600 px-5 py-2.5 text-sm font-semibold text-white
                                       hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                Confirmar importación
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endisset
    </x-modal>
</div>