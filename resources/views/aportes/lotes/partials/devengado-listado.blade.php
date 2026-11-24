<section class="mt-8 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
    <div class="border-b border-gray-200 px-6 py-5">
        <h2 class="text-xl font-semibold text-gray-900">Lotes devengados activos</h2>
        <p class="mt-1 text-sm text-gray-500">Lotes de regularización del mismo colegio y período que los lotes ordinarios.</p>
    </div>
    <div class="max-h-[60vh] overflow-auto">
        <table class="min-w-[900px] w-full text-left text-sm">
            <thead class="sticky top-0 z-10 bg-gray-50 text-xs uppercase tracking-wide text-gray-600 shadow-[inset_0_-1px_0_#e5e7eb]">
                <tr>
                    <th class="px-5 py-3">Código</th>
                    <th class="px-5 py-3">Colegio</th>
                    <th class="px-5 py-3">Período</th>
                    <th class="px-5 py-3">Pagos</th>
                    <th class="px-5 py-3">Estado</th>
                    <th class="px-5 py-3">Acción</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($devengadoLotes as $lote)
                    <tr class="hover:bg-blue-50">
                        <td class="whitespace-nowrap px-5 py-4 font-semibold text-gray-900">{{ $lote->codigo_lote }}</td>
                        <td class="px-5 py-4">{{ $lote->colegio->nombre }}</td>
                        <td class="px-5 py-4">
                            @if ($lote->mes && $lote->year)
                                {{ str_pad((string) $lote->mes, 2, '0', STR_PAD_LEFT) }}/{{ $lote->year }}
                            @else
                                Lote anterior sin período asignado
                            @endif
                        </td>
                        <td class="px-5 py-4">{{ $lote->pagos_count }}</td>
                        <td class="px-5 py-4">
                            @if ($lote->estado === 'ABIERTO')
                                <span class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">Abierto</span>
                            @elseif ($lote->estado === 'ENVIADO')
                                <span class="inline-flex items-center rounded-full border border-[#b8d8e2] bg-[#edf7fa] px-3 py-1 text-xs font-medium text-[#176078]">Enviado</span>
                            @elseif ($lote->estado === 'EN_VERIFICACION')
                                <span class="inline-flex items-center rounded-full border border-orange-200 bg-orange-50 px-3 py-1 text-xs font-medium text-orange-600">En verificación</span>
                            @elseif ($lote->estado === 'VERIFICADO')
                                <span class="inline-flex items-center rounded-full border border-green-200 bg-green-50 px-3 py-1 text-xs font-medium text-green-700">Verificado</span>
                            @else
                                <span class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-medium text-gray-700">{{ str_replace('_', ' ', $lote->estado) }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap justify-end gap-2">
                                <x-modal titulo="Detalle del lote {{ $lote->codigo_lote }}" ancho="max-w-6xl">
                                    <x-slot:trigger>
                                        <button type="button"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-sky-500 bg-white px-4 py-2 text-sm font-semibold text-sky-500 transition hover:bg-gray-50 hover:cursor-pointer">
                                            <x-icon name="see" class="text-sky-500" size="20"/>
                                            Ver pagos
                                        </button>
                                    </x-slot:trigger>
                                    @include('devengados.partials.lote-detalle', ['lote' => $lote])
                                </x-modal>
                                @if (auth()->user()->rol?->nombre === 'ADMIN' && in_array($lote->estado, ['ENVIADO', 'EN_VERIFICACION'], true))
                                    <a href="{{ route('devengados.show', $lote) }}"
                                        class="inline-flex items-center rounded-lg bg-[#1a34c9] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#152aa3]">
                                        Abrir revisión
                                    </a>
                                @endif
                                @if (in_array($lote->estado, ['ENVIADO', 'EN_VERIFICACION', 'VERIFICADO'], true))
                                    <a href="{{ route('devengados.lotes.excel', $lote) }}"
                                        class="inline-flex items-center rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                        Descargar Excel
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-gray-500">No hay lotes devengados activos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if (method_exists($devengadoLotes, 'hasPages') && $devengadoLotes->hasPages())
        <div class="border-t border-gray-100 px-5 py-3">{{ $devengadoLotes->links() }}</div>
    @endif
</section>
