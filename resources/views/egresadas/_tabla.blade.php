<div class="max-h-150 overflow-x-auto overflow-y-auto">
    <table class="min-w-250 w-full">
        <thead class="border-b border-[#1a34c9] bg-[#edeef3] text-left text-xs uppercase tracking-wide text-gray-600">
            <tr>
                <th class="sticky top-0 z-10 bg-[#edeef3] px-6 py-3 font-semibold">CI</th>
                <th class="sticky top-0 z-10 bg-[#edeef3] px-6 py-3 font-semibold">Egresada</th>
                <th class="sticky top-0 z-10 bg-[#edeef3] px-6 py-3 font-semibold">Universidad</th>
                <th class="sticky top-0 z-10 bg-[#edeef3] px-6 py-3 font-semibold">Departamento</th>
                <th class="sticky top-0 z-10 bg-[#edeef3] px-6 py-3 font-semibold">Carrera</th>
                <th class="sticky top-0 z-10 bg-[#edeef3] px-6 py-3 font-semibold">Estado</th>
                <th class="sticky top-0 z-10 bg-[#edeef3] px-6 py-3 font-semibold">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($egresadas as $egresada)
                <tr class="transition hover:bg-blue-50">
                    <td class="whitespace-nowrap px-6 py-5 text-sm text-gray-700">{{ $egresada->ci }}</td>
                    <td class="whitespace-nowrap px-6 py-5 text-sm font-semibold text-gray-900">
                        {{ $egresada->apellidos }} {{ $egresada->nombres }}
                        @if ($egresada->celular)
                            <span class="mt-1 block text-xs font-normal text-gray-500">{{ $egresada->celular }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-5 text-sm text-gray-700">
                        @forelse ($egresada->formaciones as $formacion)
                            <div class="mb-2 last:mb-0">{{ $formacion->universidad?->nombre ?? '—' }}</div>
                        @empty
                            <span class="text-gray-400">Sin formación registrada</span>
                        @endforelse
                    </td>
                    <td class="px-6 py-5 text-sm text-gray-700">
                        @forelse ($egresada->formaciones as $formacion)
                            <div class="mb-2 last:mb-0">{{ $formacion->departamento?->nombre ?? '—' }}</div>
                        @empty
                            <span class="text-gray-400">—</span>
                        @endforelse
                    </td>
                    <td class="px-6 py-5 text-sm text-gray-700">
                        @forelse ($egresada->formaciones as $formacion)
                            <div class="mb-2 last:mb-0">
                                {{ $formacion->carrera }}
                                @if ($formacion->fecha_egreso)
                                    <span class="block text-xs text-gray-500">Egreso: {{ $formacion->fecha_egreso->format('d/m/Y') }}</span>
                                @endif
                            </div>
                        @empty
                            <span class="text-gray-400">—</span>
                        @endforelse
                    </td>
                    <td class="whitespace-nowrap px-6 py-5 text-sm">
                        @if ($egresada->estaConvertida())
                            <span class="rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-700">Convertida en afiliada</span>
                        @else
                            <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Pendiente</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-6 py-5">
                        @include('egresadas._acciones', ['egresada' => $egresada])
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-14 text-center">
                        <p class="text-sm font-medium text-gray-600">No se encontraron egresadas.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="flex flex-col gap-4 border-t border-gray-200 bg-white px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
    <p class="text-sm text-gray-600">
        Mostrando <span class="font-semibold text-gray-800">{{ $egresadas->firstItem() ?? 0 }}</span>
        a <span class="font-semibold text-gray-800">{{ $egresadas->lastItem() ?? 0 }}</span>
        de <span class="font-semibold text-gray-800">{{ $egresadas->total() }}</span> egresadas
    </p>
    <div>{{ $egresadas->links() }}</div>
</div>
