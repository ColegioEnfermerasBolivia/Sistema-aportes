<div class="space-y-5">
    <header class="space-y-1 text-center">
        <h3 class="text-lg font-bold text-[#0d1b6e]">
            COLEGIO DE ENFERMERAS DE BOLIVIA
        </h3>
        <p class="text-base font-bold text-[#1a34c9]">
            REPORTE DETALLADO DE PAGOS POR LOTE
        </p>
        <div class="grid gap-1 pt-2 text-left text-sm text-gray-700 sm:grid-cols-2">
            <p><span class="font-semibold">Colegio:</span> {{ $reporte['colegio'] }}</p>
            <p><span class="font-semibold">Código del lote:</span> {{ $reporte['codigo_lote'] }}</p>
            <p><span class="font-semibold">Fecha de generación:</span> {{ $reporte['fecha_generacion'] }}</p>
            <p><span class="font-semibold">Usuario que genera el reporte:</span> {{ $reporte['usuario_generador'] }}</p>
        </div>
    </header>

    <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="w-max table-auto border-collapse text-left text-sm">
            <thead class="bg-[#2e5b8a] text-white">
                <tr>
                    <th class="w-49 border border-gray-300 px-3 py-2 max-h-[60vh] overflow-auto sticky top-0 z-10">Nombre completo</th>
                    <th class="whitespace-nowrap border border-gray-300 px-3 py-2 max-h-[60vh] overflow-auto sticky top-0 z-10">CI</th>
                    <th class="whitespace-nowrap border border-gray-300 px-3 py-2 max-h-[60vh] overflow-auto sticky top-0 z-10">Gestión</th>
                    @foreach ([
                        'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun',
                        'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic',
                    ] as $mes)
                        <th class="whitespace-nowrap border-gray-300 px-3 py-2 text-center max-h-[60vh] overflow-auto sticky top-0 z-10">{{ $mes }}</th>
                    @endforeach
                    <th class="whitespace-nowrap border border-gray-300 px-3 py-2 text-right max-h-[60vh] overflow-auto sticky top-0 z-10">Saldo</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($reporte['filas'] as $fila)
                    @php($fondo = $loop->even ? 'bg-[#2e5b8a]/5' : 'bg-white')

                    @foreach ($fila['gestiones'] as $gestion => $aportes)
                        <tr class="align-top {{ $fondo }}">
                            @if ($loop->first)
                                <td rowspan="{{ count($fila['gestiones']) }}" class="border border-gray-200 px-3 py-2 align-middle">
                                    {{ $fila['aportante'] }}
                                </td>
                                <td rowspan="{{ count($fila['gestiones']) }}" class="border border-gray-200 px-3 py-2 align-middle">
                                    {{ $fila['ci'] }}
                                </td>
                            @endif
                            <td class="border border-gray-200 px-3 py-2 font-medium">{{ $gestion }}</td>
                            @foreach (range(1, 12) as $mes)
                                <td class="border border-gray-200 px-3 py-2 text-right tabular-nums">
                                    @if (isset($aportes[$mes]))
                                        {{ number_format($aportes[$mes], 2, ',', '.') }}
                                    @else
                                        —
                                    @endif
                                </td>
                            @endforeach
                            @if ($loop->first)
                                <td rowspan="{{ count($fila['gestiones']) }}" class="border border-gray-200 px-3 py-2 text-right align-middle tabular-nums">
                                    {{ number_format($fila['saldo_disponible'], 2, ',', '.') }}
                                </td>
                            @endif
                        </tr>
                    @endforeach
                @empty
                    {{-- sin cambios --}}
                @endforelse
            </tbody>
        </table>
    </div>
</div>
