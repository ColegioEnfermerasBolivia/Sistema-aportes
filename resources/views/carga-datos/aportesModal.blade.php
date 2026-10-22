{{-- ============================================================
     MODAL DE PREVISUALIZACIÓN DE APORTES HISTÓRICOS
     ============================================================ --}}

@if(isset($resultado))

    <div
        x-data="{
            mostrar: true
        }"
        x-init="$nextTick(() => mostrar = true)"
    >

        <x-modal
            x-model="mostrar"
            titulo="Previsualización de aportes históricos"
            ancho="max-w-7xl"
        >

            @php
                $resumen = $resultado['resumen'] ?? [];
                $registros = $resultado['registros'] ?? [];

                $filasCsv = $resumen['filas_csv'] ?? 0;
                $filasProcesadas = $resumen['filas_procesadas'] ?? 0;
                $afiliadosEncontrados = $resumen['afiliados_encontrados'] ?? 0;
                $aportesValidos = $resumen['aportes_validos'] ?? 0;
                $noEncontradas = $resumen['afiliadas_no_encontradas'] ?? 0;
                $ambiguas = $resumen['afiliadas_ambiguas'] ?? 0;
                $incompletos = $resumen['datos_incompletos'] ?? 0;
                $montosInvalidos = $resumen['montos_invalidos'] ?? 0;
                $duplicados = $resumen['aportes_duplicados'] ?? 0;

                $sinAportes = collect($registros)
                    ->where('estado', 'SIN_APORTES')
                    ->count();

                $matchesAbreviacion = collect($registros)
                    ->where('estado', 'LISTO')
                    ->where('tipo_match', 'ENCONTRADA_POR_ABREVIACION')
                    ->count();

                $registrosNoImportables = collect($registros)
                    ->whereIn('estado', [
                        'AFILIADA_NO_ENCONTRADA',
                        'AFILIADA_AMBIGUA',
                        'DATOS_INCOMPLETOS',
                        'MONTO_INVALIDO',
                        'APORTE_DUPLICADO',
                        'SIN_APORTES',
                    ]);

                $registrosListos = collect($registros)
                    ->where('estado', 'LISTO');
            @endphp

            {{-- ====================================================
                 RESUMEN
                 ==================================================== --}}

            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">
                    Resultado del análisis
                </h2>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

                    {{-- Filas CSV --}}
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <p class="text-xs text-gray-500">
                            Filas del CSV
                        </p>

                        <p class="text-2xl font-semibold text-gray-800">
                            {{ $filasCsv }}
                        </p>
                    </div>

                    {{-- Afiliadas encontradas --}}
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <p class="text-xs text-green-700">
                            Afiliadas encontradas
                        </p>

                        <p class="text-2xl font-semibold text-green-700">
                            {{ $afiliadosEncontrados }}
                        </p>
                    </div>

                    {{-- Aportes válidos --}}
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <p class="text-xs text-blue-700">
                            Aportes a importar
                        </p>

                        <p class="text-2xl font-semibold text-blue-700">
                            {{ $aportesValidos }}
                        </p>
                    </div>

                    {{-- Registros que requieren revisión --}}
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <p class="text-xs text-red-700">
                            Registros con problemas
                        </p>

                        <p class="text-2xl font-semibold text-red-700">
                            {{ $registrosNoImportables->count() }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- ====================================================
                 DETALLE DEL ANÁLISIS
                 ==================================================== --}}
            <div class="mb-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">
                    Detalle
                </h3>

                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">

                    <div class="border rounded-lg p-3">
                        <p class="text-xs text-gray-500">
                            No encontradas
                        </p>
                        <p class="text-lg font-semibold text-red-600">
                            {{ $noEncontradas }}
                        </p>
                    </div>

                    <div class="border rounded-lg p-3">
                        <p class="text-xs text-gray-500">
                            Ambiguas
                        </p>
                        <p class="text-lg font-semibold text-orange-600">
                            {{ $ambiguas }}
                        </p>
                    </div>

                    <div class="border rounded-lg p-3">
                        <p class="text-xs text-gray-500">
                            Datos incompletos
                        </p>
                        <p class="text-lg font-semibold text-orange-600">
                            {{ $incompletos }}
                        </p>
                    </div>

                    <div class="border rounded-lg p-3">
                        <p class="text-xs text-gray-500">
                            Montos inválidos
                        </p>
                        <p class="text-lg font-semibold text-red-600">
                            {{ $montosInvalidos }}
                        </p>
                    </div>

                    <div class="border rounded-lg p-3">
                        <p class="text-xs text-gray-500">
                            Aportes duplicados
                        </p>
                        <p class="text-lg font-semibold text-red-600">
                            {{ $duplicados }}
                        </p>
                    </div>

                    <div class="border rounded-lg p-3">
                        <p class="text-xs text-gray-500">
                            Sin aportes
                        </p>
                        <p class="text-lg font-semibold text-gray-600">
                            {{ $sinAportes }}
                        </p>
                    </div>
                </div>
            </div>


            {{-- ====================================================
                 AVISO SOBRE ABREVIACIONES
                 ==================================================== --}}

            @if($matchesAbreviacion > 0)
                <div class="mb-6 rounded-lg border border-yellow-300 bg-yellow-50 p-4">
                    <div class="flex items-start gap-3">
                        <div class="text-yellow-600 text-lg">
                            ⚠
                        </div>
                        <div>
                            <p class="font-semibold text-yellow-800">
                                Coincidencias por abreviación
                            </p>

                            <p class="text-sm text-yellow-700 mt-1">
                                Se encontraron
                                <strong>{{ $matchesAbreviacion }}</strong>
                                registros donde el nombre del CSV coincide
                                con una afiliada mediante una abreviación.
                            </p>

                            <p class="text-xs text-yellow-700 mt-1">
                                Estos registros pueden importarse, pero se
                                recomienda revisarlos antes de confirmar.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ====================================================
                 REGISTROS QUE NO SE IMPORTARÁN
                 ==================================================== --}}

            @if($registrosNoImportables->count() > 0)
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">
                                Registros que no se importarán
                            </h3>
                            <p class="text-sm text-gray-500">
                                Estos registros requieren revisión antes de
                                poder incorporarlos al sistema.
                            </p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                            {{ $registrosNoImportables->count() }}
                            registros
                        </span>
                    </div>

                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <div class="overflow-x-auto max-h-[450px]">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 border-b border-gray-200 sticky top-0">

                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">
                                            Fila
                                        </th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">
                                            Nombre CSV
                                        </th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">
                                            Año
                                        </th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">
                                            Estado
                                        </th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">
                                            Motivo
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">

                                    @foreach($registrosNoImportables as $registro)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2 whitespace-nowrap">
                                                {{ $registro['fila'] }}
                                            </td>
                                            <td class="px-3 py-2">
                                                {{ $registro['nombre_csv'] ?: '—' }}
                                            </td>
                                            <td class="px-3 py-2 whitespace-nowrap">
                                                {{ $registro['year'] ?: '—' }}
                                            </td>
                                            <td class="px-3 py-2 whitespace-nowrap">
                                                @switch($registro['estado'])
                                                    @case('AFILIADA_NO_ENCONTRADA')
                                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                                            No encontrada
                                                        </span>
                                                        @break
                                                    @case('AFILIADA_AMBIGUA')
                                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-700">
                                                            Ambigua
                                                        </span>
                                                        @break

                                                    @case('DATOS_INCOMPLETOS')
                                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                                            Datos incompletos
                                                        </span>
                                                        @break
                                                    @case('MONTO_INVALIDO')
                                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                                            Monto inválido
                                                        </span>
                                                        @break
                                                    @case('APORTE_DUPLICADO')
                                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                            Duplicado
                                                        </span>
                                                        @break
                                                    @case('SIN_APORTES')

                                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                                            Sin aportes
                                                        </span>
                                                        @break
                                                    @default
                                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                                            {{ $registro['estado'] }}
                                                        </span>
                                                @endswitch
                                            </td>
                                            <td class="px-3 py-2 text-gray-600">
                                                {{ $registro['mensaje'] ?? '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif


            {{-- ====================================================
                 AFILIADAS AMBIGUAS
                 ==================================================== --}}

            @php
                $registrosAmbiguos = collect($registros)
                    ->where('estado', 'AFILIADA_AMBIGUA');
            @endphp

            @if($registrosAmbiguos->count() > 0)
                <div class="mb-6">
                    <h3 class="text-base font-semibold text-gray-800 mb-3">
                        Afiliadas con coincidencias ambiguas
                    </h3>
                    <div class="space-y-3">
                        @foreach($registrosAmbiguos as $registro)
                            <div class="border border-orange-200 bg-orange-50 rounded-lg p-4">
                                <div class="flex justify-between">
                                    <div>
                                        <p class="font-medium text-gray-800">
                                            {{ $registro['nombre_csv'] }}
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            Fila {{ $registro['fila'] }}
                                            · Año {{ $registro['year'] }}
                                        </p>
                                    </div>
                                    <span class="px-2 py-1 h-fit rounded-full text-xs font-medium bg-orange-100 text-orange-700">
                                        Requiere revisión
                                    </span>
                                </div>

                                @if(!empty($registro['afiliados']))
                                    <div class="mt-3 bg-white rounded border border-orange-200">
                                        @foreach($registro['afiliados'] as $afiliada)
                                            <div class="px-3 py-2 border-b last:border-b-0 border-gray-100">
                                                <p class="text-sm font-medium text-gray-800">
                                                    {{ $afiliada['nombre'] ?? 'Sin nombre' }}
                                                </p>
                                                <p class="text-xs text-gray-500">

                                                    @if(isset($afiliada['id']))
                                                        ID: {{ $afiliada['id'] }}
                                                    @endif

                                                    @if(isset($afiliada['ci']))
                                                        · CI: {{ $afiliada['ci'] }}
                                                    @endif

                                                    @if(isset($afiliada['matricula_profesional']))
                                                        · Matrícula:
                                                        {{ $afiliada['matricula_profesional'] }}
                                                    @endif
                                                </p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ====================================================
                 APORTES LISTOS
                 ==================================================== --}}

            @if($registrosListos->count() > 0)
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">
                                Registros listos para importar
                            </h3>
                            <p class="text-sm text-gray-500">
                                Estos registros cumplen las validaciones del
                                importador.
                            </p>

                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                            {{ $aportesValidos }}
                            aportes
                        </span>
                    </div>

                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <div class="overflow-x-auto max-h-[400px]">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 border-b border-gray-200 sticky top-0">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">
                                            Fila
                                        </th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">
                                            Afiliada
                                        </th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">
                                            Año
                                        </th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">
                                            Coincidencia
                                        </th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">
                                            Aportes
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @foreach($registrosListos as $registro)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2 whitespace-nowrap">
                                                {{ $registro['fila'] }}
                                            </td>
                                            <td class="px-3 py-2">
                                                <p class="font-medium text-gray-800">
                                                    {{ $registro['afiliado']['nombre'] ?? '—' }}
                                                </p>
                                                @if(isset($registro['afiliado']['id']))
                                                    <p class="text-xs text-gray-500">
                                                        ID:
                                                        {{ $registro['afiliado']['id'] }}
                                                    </p>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 whitespace-nowrap">
                                                {{ $registro['year'] }}
                                            </td>
                                            <td class="px-3 py-2 whitespace-nowrap">
                                                @if(($registro['tipo_match'] ?? '') === 'ENCONTRADA_POR_ABREVIACION')
                                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        Abreviación
                                                    </span>
                                                @else
                                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                                        Exacta
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2">
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach($registro['aportes'] as $aporte)
                                                        <span class="inline-flex items-center px-2 py-1 rounded bg-blue-50 text-blue-700 text-xs">
                                                            {{ $aporte['nombre_mes'] }}
                                                            :
                                                            {{ number_format(
                                                                $aporte['monto'],
                                                                2,
                                                                '.',
                                                                ''
                                                            ) }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ====================================================
                 BOTONES
                 ==================================================== --}}

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                <form
                    action="{{ route('aportesHistoricos.cancelar') }}"
                    method="POST"
                >
                    @csrf

                    <button
                        type="submit"
                        class="px-5 py-2.5 rounded-lg
                               border border-gray-300
                               text-gray-700
                               hover:bg-gray-50
                               font-medium
                               transition"
                    >
                        Cancelar
                    </button>
                </form>

                @if($aportesValidos > 0)
                    <form
                        action="{{ route('aportesHistoricos.importar') }}"
                        method="POST"
                    >
                        @csrf
                        <button
                            type="submit"
                            class="bg-[#1a34c9]
                                   hover:bg-blue-800
                                   text-white
                                   font-medium
                                   px-5 py-2.5
                                   rounded-lg
                                   transition"
                        >
                            Confirmar importación
                            ({{ $aportesValidos }} aportes)
                        </button>
                    </form>
                @endif
            </div>
        </x-modal>
    </div>
@endif