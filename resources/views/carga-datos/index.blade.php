@extends('layouts.app')
@section('content')

    <div class="p-6">

        {{-- ENCABEZADO --}}
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-800">Carga de datos</h1>
            <p class="mt-1 text-sm text-gray-500">
                Importación y validación de información mediante archivos CSV.
            </p>
        </div>

        {{-- CUADRÍCULA DE MÓDULOS DE CARGA --}}
        <div class="grid grid-cols-1 items-stretch gap-6 lg:auto-rows-fr lg:grid-cols-2">

        {{-- CARGA DE AFILIADAS --}}
        <div class="flex h-full flex-col rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="min-h-[104px] border-b border-gray-200 px-6 py-5">
                <h2 class="text-lg font-semibold text-gray-800">Afiliadas</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Carga las afiliadas desde un archivo CSV.
                </p>
            </div>

            <div class="flex flex-1 flex-col px-6 py-6">
                <form
                    action="{{ route('carga-datos.afiliados.validar') }}"
                    method="POST"
                    enctype="multipart/form-data"
                    class="flex flex-1 flex-col"
                >
                    @csrf
                    <div class="flex flex-1 flex-col justify-between gap-4 md:flex-row md:items-end">
                        {{-- ARCHIVO --}}
                        <div class="flex-1">
                            <div
                                x-data="{
                                    archivo: null,
                                    nombreArchivo: ''
                                }"
                                class="flex-1"
                            >
                                <label
                                    for="archivo_afiliadas"
                                    class="mb-2 block text-sm font-medium text-gray-700"
                                >
                                    Archivo CSV
                                </label>
                                <div class="relative">
                                    <input
                                        type="file"
                                        id="archivo_afiliadas"
                                        name="archivo_afiliadas"
                                        accept=".csv,text/csv"
                                        x-ref="archivo"
                                        class="block h-11 w-full cursor-pointer rounded-lg
                                            border border-gray-300
                                            bg-white text-sm text-gray-600
                                            file:mr-4
                                            file:border-0
                                            file:bg-gray-100
                                            file:px-4
                                            file:py-2
                                            file:text-sm
                                            file:font-medium
                                            file:text-gray-700
                                            hover:file:bg-gray-200
                                            focus:outline-none
                                            focus:ring-2
                                            focus:ring-[#1a34c9]/30"
                                        @change="
                                            archivo = $event.target.files[0] ?? null;
                                            nombreArchivo = archivo ? archivo.name : '';
                                        "
                                    >
                                    <button
                                        x-show="archivo"
                                        x-cloak
                                        type="button"
                                        @click="
                                            archivo = null;
                                            nombreArchivo = '';
                                            $refs.archivo.value = '';
                                        "
                                        class="absolute right-3 top-1/2 z-10
                                                flex h-7 w-7 -translate-y-1/2
                                                items-center justify-center
                                                rounded-full
                                                bg-gray-100
                                                text-gray-500
                                                transition
                                                hover:bg-gray-200
                                                hover:text-gray-800"
                                        aria-label="Quitar archivo"
                                    >
                                        <x-icon name="x" class="text-black" size="16"/>
                                    </button>
                                </div>
                                <p
                                    x-show="!archivo"
                                    class="mt-2 text-xs text-gray-500"
                                >
                                    Selecciona un archivo CSV con los datos de las afiliadas.
                                </p>
                                <p
                                    x-show="archivo"
                                    x-text="nombreArchivo"
                                    class="mt-2 truncate text-xs text-gray-600"
                                ></p>
                            </div>
                        </div>

                        {{-- BOTÓN --}}
                        <div class="w-full md:w-48">
                            <button
                                type="submit"
                                class="inline-flex h-11 w-full items-center justify-center gap-2
                                       rounded-lg
                                       bg-[#1a34c9]
                                       px-5
                                       text-sm font-semibold
                                       text-white
                                       transition
                                       hover:bg-[#1429a3]
                                       focus:outline-none
                                       focus:ring-2
                                       focus:ring-[#1a34c9]/30
                                       focus:ring-offset-2
                                       hover:cursor-pointer"
                            >
                                <x-icon name="upload" size="18"/>
                                Validar archivo
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- CARGA DE APORTES HISTÓRICOS --}}
        <div class="flex h-full flex-col rounded-xl border border-gray-200 bg-white shadow-sm">

            <div class="min-h-[104px] border-b border-gray-200 px-6 py-5">
                <h2 class="text-lg font-semibold text-gray-800">
                    Aportes históricos
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Carga los aportes históricos de las afiliadas mediante un archivo CSV.
                </p>
            </div>

            <div class="flex flex-1 flex-col px-6 py-6">
                <form
                    action="{{ route('aportesHistoricos.analizar') }}"
                    method="POST"
                    enctype="multipart/form-data"
                    class="flex flex-1 flex-col"
                >
                    @csrf

                    <div class="flex flex-1 flex-col justify-between gap-4 md:flex-row md:items-end">

                        {{-- ARCHIVO --}}
                        <div
                            x-data="{
                                archivo: null,
                                nombreArchivo: ''
                            }"
                            class="flex-1"
                        >

                            <label
                                for="archivo_aportes"
                                class="mb-2 block text-sm font-medium text-gray-700"
                            >
                                Archivo CSV
                            </label>

                            <div class="relative">

                                <input
                                    type="file"
                                    id="archivo_aportes"
                                    name="archivo"
                                    accept=".csv,text/csv"
                                    x-ref="archivo"
                                    required
                                    @change="
                                        archivo = $event.target.files[0] ?? null;
                                        nombreArchivo = archivo ? archivo.name : '';
                                    "
                                    class="block h-11 w-full cursor-pointer rounded-lg
                                        border border-gray-300
                                        bg-white
                                        text-sm text-gray-600
                                        file:mr-4
                                        file:border-0
                                        file:bg-gray-100
                                        file:px-4
                                        file:py-2
                                        file:text-sm
                                        file:font-medium
                                        file:text-gray-700
                                        hover:file:bg-gray-200
                                        focus:outline-none
                                        focus:ring-2
                                        focus:ring-[#1a34c9]/30"
                                >

                                {{-- LIMPIAR ARCHIVO --}}
                                <button
                                    x-show="archivo"
                                    x-cloak
                                    type="button"
                                    @click="
                                        archivo = null;
                                        nombreArchivo = '';
                                        $refs.archivo.value = '';
                                    "
                                    class="absolute right-2 top-1/2
                                        -translate-y-1/2
                                        rounded-md
                                        px-2 py-1
                                        text-xs
                                        text-gray-500
                                        hover:bg-gray-100
                                        hover:text-gray-700"
                                >
                                    Limpiar
                                </button>

                            </div>

                            <p class="mt-2 text-xs text-gray-500">
                                CSV con las columnas:
                                APELLIDOS Y NOMBRES, YEAR, ENERO ... DICIEMBRE.
                                Tamaño máximo: 256 MB.
                            </p>

                            <p
                                x-show="archivo"
                                x-cloak
                                x-text="nombreArchivo"
                                class="mt-2 truncate text-xs text-gray-600"
                            ></p>

                        </div>

                        {{-- BOTÓN --}}
                        <div class="w-full md:w-48">

                            <button
                                type="submit"
                                class="inline-flex h-11 w-full items-center justify-center gap-2
                                    rounded-lg
                                    bg-[#1a34c9]
                                    px-5
                                    text-sm font-semibold
                                    text-white
                                    transition
                                    hover:bg-[#1429a3]
                                    focus:outline-none
                                    focus:ring-2
                                    focus:ring-[#1a34c9]/30
                                    focus:ring-offset-2
                                    hover:cursor-pointer"
                            >
                                <x-icon name="upload" size="18"/>
                                Analizar archivo
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- CARGA DE TRANSFERENCIAS --}}
        <div class="flex h-full flex-col rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="min-h-[104px] border-b border-gray-200 px-6 py-5">
                <h2 class="text-lg font-semibold text-gray-800">
                    Transferencias
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Importa y valida el histórico de transferencias de afiliadas desde un archivo CSV.
                </p>
            </div>

            <div class="flex flex-1 flex-col px-6 py-6">
                <form
                    action="{{ route('transferencias.analizar') }}"
                    method="POST"
                    enctype="multipart/form-data"
                    class="flex flex-1 flex-col"
                >
                    @csrf

                    <div class="flex flex-1 flex-col justify-between gap-4 md:flex-row md:items-end">

                        {{-- ARCHIVO --}}
                        <div class="flex-1">
                            <div
                                x-data="{
                                    archivo: null,
                                    nombreArchivo: ''
                                }"
                                class="flex-1"
                            >
                                <label
                                    for="archivo_transferencias"
                                    class="mb-2 block text-sm font-medium text-gray-700"
                                >
                                    Archivo CSV
                                </label>

                                <div class="relative">
                                    <input
                                        type="file"
                                        id="archivo_transferencias"
                                        name="archivo"
                                        accept=".csv,.txt,text/csv"
                                        required
                                        x-ref="archivo"
                                        class="block h-11 w-full cursor-pointer rounded-lg
                                            border border-gray-300
                                            bg-white text-sm text-gray-600
                                            file:mr-4
                                            file:border-0
                                            file:bg-gray-100
                                            file:px-4
                                            file:py-2
                                            file:text-sm
                                            file:font-medium
                                            file:text-gray-700
                                            hover:file:bg-gray-200
                                            focus:outline-none
                                            focus:ring-2
                                            focus:ring-[#1a34c9]/30"
                                        @change="
                                            archivo = $event.target.files[0] ?? null;
                                            nombreArchivo = archivo ? archivo.name : '';
                                        "
                                    >

                                    <button
                                        x-show="archivo"
                                        x-cloak
                                        type="button"
                                        @click="
                                            archivo = null;
                                            nombreArchivo = '';
                                            $refs.archivo.value = '';
                                        "
                                        class="absolute right-3 top-1/2 z-10
                                            flex h-7 w-7 -translate-y-1/2
                                            items-center justify-center
                                            rounded-full bg-gray-100
                                            text-gray-500 transition
                                            hover:bg-gray-200 hover:text-gray-800"
                                        aria-label="Quitar archivo"
                                    >
                                        <x-icon name="x" class="text-black" size="16"/>
                                    </button>
                                </div>

                                <p
                                    x-show="!archivo"
                                    class="mt-2 text-xs text-gray-500"
                                >
                                    CSV con CI, colegio de origen, colegio de destino y fecha.
                                </p>

                                <p
                                    x-show="archivo"
                                    x-text="nombreArchivo"
                                    class="mt-2 truncate text-xs text-gray-600"
                                ></p>
                            </div>
                        </div>

                        {{-- BOTÓN --}}
                        <div class="w-full md:w-48">
                            <button
                                type="submit"
                                class="inline-flex h-11 w-full items-center justify-center gap-2
                                    rounded-lg
                                    bg-[#1a34c9]
                                    px-5
                                    text-sm font-semibold
                                    text-white
                                    transition
                                    hover:bg-[#1429a3]
                                    focus:outline-none
                                    focus:ring-2
                                    focus:ring-[#1a34c9]/30
                                    focus:ring-offset-2
                                    hover:cursor-pointer"
                            >
                                <x-icon name="upload" size="18"/>
                                Validar archivo
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- IMPORTACIÓN DE SALDOS HISTÓRICOS --}}

        <section class="flex h-full flex-col rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="min-h-[104px] border-b border-gray-200 px-6 py-5">
                <h2 class="text-lg font-semibold text-gray-800">
                    Importar saldos históricos
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Carga un archivo CSV con el CI de cada afiliado y su saldo disponible.
                    Antes de guardar los datos podrás revisar y corregir los registros.
                </p>
            </div>

            <div class="flex flex-1 flex-col px-6 py-6">
                <form
                    method="POST"
                    action="{{ route('saldos.preview') }}"
                    enctype="multipart/form-data"
                    class="flex flex-1 flex-col"
                >
                    @csrf

                    <div class="flex flex-1 flex-col">
                        <label
                            for="archivo_saldos"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Archivo CSV
                        </label>

                        <input
                            type="file"
                            id="archivo_saldos"
                            name="archivo"
                            accept=".csv,text/csv"
                            required
                            class="block h-11 w-full cursor-pointer rounded-lg border border-gray-300 bg-white text-sm text-gray-600
                                file:mr-4 file:border-0 file:bg-gray-100 file:px-4 file:py-2
                                file:text-sm file:font-medium file:text-gray-700
                                hover:file:bg-gray-200
                                focus:outline-none focus:ring-2 focus:ring-[#1a34c9]/30"
                        >

                        <p class="mt-2 text-xs text-gray-500">
                            El archivo debe contener las columnas
                            <span class="font-semibold">CI</span> y
                            <span class="font-semibold">SALDO</span>.
                            Los saldos deben expresarse como números, por ejemplo:
                            <span class="font-semibold">125.50</span>.
                        </p>
                    </div>
                    <div class="mt-auto flex justify-end border-t border-gray-200 pt-4">
                        <button
                            type="submit"
                            class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg
                                bg-[#1a34c9] px-5 text-sm font-semibold text-white transition
                                hover:bg-[#1429a3] focus:outline-none focus:ring-2
                                focus:ring-[#1a34c9]/30 focus:ring-offset-2 hover:cursor-pointer
                                md:w-48"
                        >
                            <x-icon name="upload" size="18" />
                            Analizar CSV
                        </button>
                    </div>
                </form>
            </div>
        </section>

        {{-- =========================================================
            IMPORTACIÓN DE OBSERVACIONES HISTÓRICAS
        ========================================================= --}}

        <div class="flex h-full flex-col rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="min-h-[104px] border-b border-gray-200 px-6 py-5">
                <h2 class="text-lg font-semibold text-gray-800">
                    Observaciones históricas
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Importa las observaciones existentes de los afiliados.
                </p>
            </div>

            <div class="flex flex-1 flex-col px-6 py-6">
                <form
                    action="{{ route('observacionesHistoricas.previsualizarObservaciones') }}"
                    method="POST"
                    enctype="multipart/form-data"
                    class="flex flex-1 flex-col"
                >
                    @csrf

                    <div class="flex flex-1 flex-col">
                        <label
                            for="archivo_observaciones"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Archivo CSV
                        </label>

                        <input
                            type="file"
                            name="archivo"
                            id="archivo_observaciones"
                            accept=".csv,.txt"
                            required
                            class="block h-11 w-full cursor-pointer rounded-lg border border-gray-300 bg-white text-sm text-gray-600
                                file:mr-4 file:border-0 file:bg-gray-100 file:px-4 file:py-2
                                file:text-sm file:font-medium file:text-gray-700
                                hover:file:bg-gray-200 focus:outline-none
                                focus:ring-2 focus:ring-[#1a34c9]/30"
                        >

                        <p class="mt-2 text-xs text-gray-500">
                            El CSV debe contener las columnas
                            <span class="font-semibold">CI</span> y
                            <span class="font-semibold">observacion</span>.
                        </p>
                    </div>

                    <div class="mt-auto flex justify-end border-t border-gray-200 pt-4">
                        <button
                            type="submit"
                            class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg
                                bg-[#1a34c9] px-5 text-sm font-semibold text-white transition
                                hover:bg-[#1429a3] focus:outline-none focus:ring-2
                                focus:ring-[#1a34c9]/30 focus:ring-offset-2 hover:cursor-pointer
                                md:w-48"
                        >
                            <x-icon name="upload" size="18" />
                            Analizar CSV
                        </button>
                    </div>
                </form>
            </div>
        </div>

        </div> {{-- Fin de la cuadrícula de módulos de carga --}}

        @php
            $modalCargaActivo = match (true) {
                !empty($observacionesToken ?? null) => 'observaciones',
                isset($yaExisten) => 'saldos',
                isset($resultado) => 'aportes',
                !empty($mostrarPrevisualizacion ?? false) => 'afiliadas',
                default => null,
            };
        @endphp

        {{-- MODAL DE OBSERVACIONES: usa variables exclusivas de esta importación --}}
        @include('carga-datos.modals.modal-observaciones', [
            'token' => $observacionesToken ?? null,
            'abierto' => $modalCargaActivo === 'observaciones',
            'filas' => $observacionesFilas ?? [],
            'total' => $observacionesTotal ?? 0,
            'correctos' => $observacionesCorrectos ?? 0,
            'errores' => $observacionesErrores ?? 0,
            'noEncontrados' => $observacionesNoEncontrados ?? 0,
        ])

        {{-- MODAL DE SALDOS: solo se incluye al regresar de su previsualización --}}
        @if (isset($yaExisten))
            @include('carga-datos.modals.modal-preview', [
                'abierto' => $modalCargaActivo === 'saldos',
            ])
        @endif

        {{-- MODAL DE PREVISUALIZACIÓN DE AFILIADAS --}}
        <div
            x-data="{modalAfiliadasAbierto: @js($modalCargaActivo === 'afiliadas'),
            datos: @js($datosValidacion ?? null),
            filtro: 'todas'}"
        >
            <x-modal
                x-model="modalAfiliadasAbierto"
                titulo="Previsualización de afiliadas"
                ancho="max-w-7xl"
            >
                <div class="space-y-6">
    
                    {{-- RESUMEN --}}
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

                    {{-- TOTAL --}}
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-sm text-gray-500">Total</p>

                        <p
                            class="mt-1 text-2xl font-semibold text-gray-900"
                            x-text="datos?.total ?? 0"
                        ></p>
                    </div>

                    {{-- VÁLIDAS --}}
                    <div class="rounded-xl border border-green-200 bg-green-50 p-4">
                        <p class="text-sm text-green-700">Válidas</p>

                        <p
                            class="mt-1 text-2xl font-semibold text-green-700"
                            x-text="datos?.validas ?? 0"
                        ></p>
                    </div>

                    {{-- CON ERRORES --}}
                    <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                        <p class="text-sm text-red-700">Con errores</p>
                        <p
                            class="mt-1 text-2xl font-semibold text-red-700"
                            x-text="datos?.errores ?? 0"
                        ></p>
                    </div>
                </div>

                {{-- BOTONES DE FILTRO --}}
                <div class="mt-6 flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    @click="filtro = 'todas'"
                    class="rounded-lg px-4 py-2 text-sm font-medium transition"
                    :class="filtro === 'todas'
                        ? 'bg-[#1a34c9] text-white'
                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                >
                    Todas
                </button>

                <button
                    type="button"
                    @click="filtro = 'validas'"
                    class="rounded-lg px-4 py-2 text-sm font-medium transition"
                    :class="filtro === 'validas'
                        ? 'bg-green-600 text-white'
                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                >
                    Válidas
                </button>

                <button
                    type="button"
                    @click="filtro = 'errores'"
                    class="rounded-lg px-4 py-2 text-sm font-medium transition"
                    :class="filtro === 'errores'
                        ? 'bg-red-600 text-white'
                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                >
                    Con errores
                </button>

                <div class="mt-3 text-sm text-gray-500">
                    Mostrando
                    <span
                        class="font-semibold text-gray-700"
                        x-text="
                            (datos?.filas ?? []).filter(fila => {
                                if (filtro === 'validas') {
                                    return fila.estado === 'VALIDA';
                                }

                                if (filtro === 'errores') {
                                    return fila.estado === 'ERROR';
                                }
                                return true;
                            }).length
                        "
                    ></span>de<span
                        class="font-semibold text-gray-700"
                        x-text="datos?.total ?? 0"
                    ></span>
                    filas
                </div>
            </div>
                        {{-- TABLA --}}
                    <div class="overflow-hidden rounded-xl border border-gray-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr class="border-b border-gray-200">
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">
                                           #
                                        </th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">
                                            Registro
                                        </th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">
                                            Matrícula
                                        </th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">
                                            CI
                                        </th>
    
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">
                                            Afiliada
                                        </th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">
                                            Colegio
                                        </th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">
                                            Estado
                                        </th>
                                    </tr>
                                </thead>
    
                                <tbody class="divide-y divide-gray-100">
                                    <template
                                        x-for="fila in (datos?.filas ?? []).filter(fila => {
                                            if (filtro === 'validas') {
                                                return fila.estado === 'VALIDA';
                                            }

                                            if (filtro === 'errores') {
                                                return fila.estado === 'ERROR';
                                            }

                                            return true;
                                        })"
                                        :key="fila.fila"
                                    >
                                        <tr
                                            class="transition"
                                            :class="fila.estado === 'ERROR'
                                                ? 'bg-red-50/60 hover:bg-red-50'
                                                : 'hover:bg-gray-50'"
                                        >

                                            {{-- FILA --}}
                                            <td
                                                class="whitespace-nowrap px-4 py-3 text-sm text-gray-500"
                                                x-text="fila.fila"
                                            ></td>

                                            {{-- REGISTRO --}}
                                            <td
                                                class="whitespace-nowrap px-4 py-3 text-sm text-gray-700"
                                                x-text="fila.registro_departamental"
                                            ></td>

                                            {{-- MATRÍCULA --}}
                                            <td
                                                class="whitespace-nowrap px-4 py-3 text-sm text-gray-700"
                                                x-text="fila.matricula_profesional"
                                            ></td>

                                            {{-- CI --}}
                                            <td
                                                class="whitespace-nowrap px-4 py-3 text-sm text-gray-700"
                                                x-text="fila.ci"
                                            ></td>

                                            {{-- AFILIADA --}}
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                <div
                                                    class="font-medium"
                                                    x-text="fila.nombres + ' ' + fila.apellidos"
                                                ></div>
                                                <template x-if="fila.errores.length > 0">
                                                    <div class="mt-1 space-y-0.5">
                                                        <template
                                                            x-for="error in fila.errores"
                                                            :key="error"
                                                        >
                                                            <p
                                                                class="text-xs text-red-600"
                                                                x-text="error"
                                                            ></p>
                                                        </template>
                                                    </div>
                                                </template>
                                            </td>

                                            {{-- COLEGIO --}}
                                            <td
                                                class="whitespace-nowrap px-4 py-3 text-sm text-gray-700"
                                                x-text="fila.colegio"
                                            ></td>

                                            {{-- ESTADO --}}
                                            <td class="px-4 py-3">

                                                <span
                                                    x-show="fila.estado === 'VALIDA'"
                                                    class="inline-flex items-center rounded-full
                                                        bg-green-100 px-3 py-1 text-xs font-semibold
                                                        text-green-700"
                                                >
                                                    Válida
                                                </span>

                                                <span
                                                    x-show="fila.estado === 'ERROR'"
                                                    class="inline-flex items-center rounded-full
                                                        bg-red-100 px-3 py-1 text-xs font-semibold
                                                        text-red-700"
                                                >
                                                    Error
                                                </span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
    
                    {{-- ACCIONES --}}
                    <div class="flex items-center justify-end gap-3">
    
                        <button
                            type="button"
                            class="rounded-lg border border-gray-300
                                   bg-white px-5 py-2.5
                                   text-sm font-medium text-gray-700
                                   transition hover:bg-gray-50"
                        >
                            Cancelar
                        </button>
    
                        <form
                            method="POST"
                            action="{{ route('carga-datos.afiliados.confirmar') }}"
                        >
                            @csrf
                            <button
                                type="submit"
                                x-bind:disabled="!datos || datos.validas === 0"
                                class="rounded-lg bg-[#1a34c9] px-5 py-2.5
                                    text-sm font-semibold text-white
                                    transition
                                    hover:bg-[#1429a3]
                                    disabled:cursor-not-allowed
                                    disabled:bg-gray-300"
                            >
                                Confirmar carga
                                (<span x-text="datos?.validas ?? 0"></span>)
                            </button>
                        </form>
                    </div>
                </div>
            </x-modal>
        </div>

        {{-- MODAL DE PREVISUALIZACIÓN DE APORTES HISTÓRICOS --}}
        <div
            x-data="{
                modalAportesAbierto: @js($modalCargaActivo === 'aportes'),
                datos: @js($resultado ?? null),
                filtro: 'todas'
            }"
        >
            <x-modal
                x-model="modalAportesAbierto"
                titulo="Previsualización de aportes históricos"
                ancho="max-w-7xl"
            >
                <div class="space-y-6">

                    {{-- =====================================================
                        RESUMEN
                        ===================================================== --}}

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

                        {{-- FILAS CSV --}}
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                            <p class="text-sm text-gray-500">
                                Filas CSV
                            </p>
                            <p
                                class="mt-1 text-2xl font-semibold text-gray-900"
                                x-text="datos?.resumen?.filas_csv ?? 0"
                            ></p>
                        </div>

                        {{-- AFILIADAS ENCONTRADAS --}}
                        <div class="rounded-xl border border-green-200 bg-green-50 p-4">
                            <p class="text-sm text-green-700">
                                Afiliadas encontradas
                            </p>

                            <p
                                class="mt-1 text-2xl font-semibold text-green-700"
                                x-text="datos?.resumen?.afiliados_encontrados ?? 0"
                            ></p>
                        </div>

                        {{-- APORTES VÁLIDOS --}}
                        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                            <p class="text-sm text-blue-700">
                                Aportes a importar
                            </p>
                            <p
                                class="mt-1 text-2xl font-semibold text-blue-700"
                                x-text="datos?.resumen?.aportes_validos ?? 0"
                            ></p>
                        </div>

                        {{-- CON ERRORES --}}
                        <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                            <p class="text-sm text-red-700">
                                Registros con problemas
                            </p>
                            <p
                                class="mt-1 text-2xl font-semibold text-red-700"
                                x-text="
                                    (datos?.resumen?.afiliadas_no_encontradas ?? 0)
                                    + (datos?.resumen?.afiliadas_ambiguas ?? 0)
                                    + (datos?.resumen?.datos_incompletos ?? 0)
                                    + (datos?.resumen?.montos_invalidos ?? 0)
                                    + (datos?.resumen?.aportes_duplicados ?? 0)
                                "
                            ></p>
                        </div>

                    </div>

                    {{-- =====================================================
                        DETALLE DEL RESUMEN
                        ===================================================== --}}

                    <div class="grid grid-cols-2 gap-3 md:grid-cols-5">
                        <div class="rounded-lg border border-gray-200 bg-white p-3">
                            <p class="text-xs text-gray-500">
                                No encontradas
                            </p>

                            <p
                                class="mt-1 text-lg font-semibold text-gray-700"
                                x-text="datos?.resumen?.afiliadas_no_encontradas ?? 0"
                            ></p>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-white p-3">
                            <p class="text-xs text-gray-500">
                                Ambiguas
                            </p>
                            <p
                                class="mt-1 text-lg font-semibold text-gray-700"
                                x-text="datos?.resumen?.afiliadas_ambiguas ?? 0"
                            ></p>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-white p-3">
                            <p class="text-xs text-gray-500">
                                Datos incompletos
                            </p>
                            <p
                                class="mt-1 text-lg font-semibold text-gray-700"
                                x-text="datos?.resumen?.datos_incompletos ?? 0"
                            ></p>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-white p-3">
                            <p class="text-xs text-gray-500">
                                Montos inválidos
                            </p>
                            <p
                                class="mt-1 text-lg font-semibold text-gray-700"
                                x-text="datos?.resumen?.montos_invalidos ?? 0"
                            ></p>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-white p-3">
                            <p class="text-xs text-gray-500">
                                Duplicados
                            </p>
                            <p
                                class="mt-1 text-lg font-semibold text-gray-700"
                                x-text="datos?.resumen?.aportes_duplicados ?? 0"
                            ></p>
                        </div>
                    </div>

                    {{-- =====================================================
                        FILTROS
                        ===================================================== --}}

                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            @click="filtro = 'todas'"
                            class="rounded-lg px-4 py-2 text-sm font-medium transition"
                            :class="filtro === 'todas'
                                ? 'bg-[#1a34c9] text-white'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        >
                            Todas
                        </button>
                        <button
                            type="button"
                            @click="filtro = 'listos'"
                            class="rounded-lg px-4 py-2 text-sm font-medium transition"
                            :class="filtro === 'listos'
                                ? 'bg-green-600 text-white'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        >
                            Listos
                        </button>
                        <button
                            type="button"
                            @click="filtro = 'errores'"
                            class="rounded-lg px-4 py-2 text-sm font-medium transition"
                            :class="filtro === 'errores'
                                ? 'bg-red-600 text-white'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        >
                            Con problemas
                        </button>
                        <button
                            type="button"
                            @click="filtro = 'duplicados'"
                            class="rounded-lg px-4 py-2 text-sm font-medium transition"
                            :class="filtro === 'duplicados'
                                ? 'bg-yellow-500 text-white'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        >
                            Duplicados
                        </button>
                    </div>

                    {{-- =====================================================
                        CONTADOR
                        ===================================================== --}}

                    <div class="text-sm text-gray-500">
                        Mostrando
                        <span
                            class="font-semibold text-gray-700"
                            x-text="
                                (datos?.registros ?? []).filter(fila => {
                                    if (filtro === 'listos') {
                                        return fila.estado === 'LISTO';
                                    }

                                    if (filtro === 'errores') {
                                        return fila.estado !== 'LISTO';
                                    }

                                    if (filtro === 'duplicados') {
                                        return fila.estado === 'APORTE_DUPLICADO';
                                    }

                                    return true;

                                }).length
                            "
                        ></span>
                        de
                        <span
                            class="font-semibold text-gray-700"
                            x-text="datos?.registros?.length ?? 0"
                        ></span>
                        registros
                    </div>

                    {{-- =====================================================
                        TABLA
                        ===================================================== --}}
                    <div class="overflow-hidden rounded-xl border border-gray-200">
                       <div class="max-h-[55vh] overflow-auto">
                            <table class="min-w-full text-sm">
                                <thead class="sticky top-0 z-10 bg-gray-50">
                                    <tr class="border-b border-gray-200">
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">
                                           #
                                        </th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">
                                            Afiliada
                                        </th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">
                                            Año
                                        </th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">
                                            Aportes
                                        </th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">
                                            Coincidencia
                                        </th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">
                                            Estado
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-100">
                                    <template
                                        x-for="fila in (datos?.registros ?? []).filter(fila => {
                                            if (filtro === 'listos') {
                                                return fila.estado === 'LISTO';
                                            }

                                            if (filtro === 'errores') {
                                                return fila.estado !== 'LISTO';
                                            }

                                            if (filtro === 'duplicados') {
                                                return fila.estado === 'APORTE_DUPLICADO';
                                            }

                                            return true;

                                        })"
                                        :key="fila.fila"
                                    >
                                        <tr
                                            class="transition"
                                            :class="fila.estado === 'LISTO'
                                                ? 'hover:bg-gray-50'
                                                : 'bg-red-50/60 hover:bg-red-50'"
                                        >
                                            {{-- FILA --}}
                                            <td
                                                class="whitespace-nowrap px-4 py-3 text-sm text-gray-500"
                                                x-text="fila.fila"
                                            ></td>

                                            {{-- AFILIADA --}}
                                            <td class="px-4 py-3">
                                                <div
                                                    class="font-medium text-gray-800"
                                                    x-text="
                                                        fila.afiliado
                                                            ? fila.afiliado.nombre
                                                            : fila.nombre_csv
                                                    "
                                                ></div>
                                                <template x-if="fila.afiliado">
                                                    <div class="mt-1 space-y-0.5 text-xs text-gray-500">
                                                        <p>
                                                            ID:
                                                            <span
                                                                class="font-medium"
                                                                x-text="fila.afiliado.id"
                                                            ></span>
                                                        </p>
                                                        <p>
                                                            CI:
                                                            <span
                                                                x-text="fila.afiliado.ci ?? '-'"
                                                            ></span>
                                                        </p>
                                                        <p>
                                                            Matrícula:
                                                            <span
                                                                x-text="fila.afiliado.matricula_profesional ?? '-'"
                                                            ></span>
                                                        </p>
                                                    </div>
                                                </template>
                                            </td>

                                            {{-- AÑO --}}
                                            <td
                                                class="whitespace-nowrap px-4 py-3 text-sm text-gray-700"
                                                x-text="fila.year"
                                            ></td>

                                            {{-- APORTES --}}
                                            <td class="px-4 py-3">
                                                <template
                                                    x-if="fila.aportes && fila.aportes.length > 0"
                                                >
                                                    <div class="space-y-1">
                                                        <p
                                                            class="font-medium text-gray-700"
                                                            x-text="
                                                                fila.aportes.length
                                                                + (
                                                                    fila.aportes.length === 1
                                                                        ? ' aporte'
                                                                        : ' aportes'
                                                                )
                                                            "
                                                        ></p>
                                                        <p class="text-xs text-gray-500">
                                                            Bs.
                                                            <span
                                                                x-text="
                                                                    fila.aportes
                                                                        .reduce(
                                                                            (total, aporte) =>
                                                                                total + Number(aporte.monto || 0),
                                                                            0
                                                                        )
                                                                        .toFixed(2)
                                                                "
                                                            ></span>
                                                        </p>
                                                    </div>
                                                </template>
                                                <template
                                                    x-if="!fila.aportes || fila.aportes.length === 0"
                                                >
                                                    <span class="text-xs text-gray-400">
                                                        Sin aportes
                                                    </span>
                                                </template>
                                            </td>

                                            {{-- TIPO DE MATCH --}}
                                            <td class="px-4 py-3">
                                                <template
                                                    x-if="fila.tipo_match === 'ENCONTRADA'"
                                                >
                                                    <span
                                                        class="inline-flex items-center rounded-full
                                                            bg-green-100 px-2.5 py-1
                                                            text-xs font-semibold text-green-700"
                                                    >
                                                        Exacta
                                                    </span>
                                                </template>
                                                <template
                                                    x-if="fila.tipo_match === 'ENCONTRADA_POR_ABREVIACION'"
                                                >
                                                    <span
                                                        class="inline-flex items-center rounded-full
                                                            bg-yellow-100 px-2.5 py-1
                                                            text-xs font-semibold text-yellow-700"
                                                    >
                                                        Abreviación
                                                    </span>
                                                </template>
                                                <template
                                                    x-if="
                                                        !fila.tipo_match
                                                        && fila.estado !== 'LISTO'
                                                    "
                                                >
                                                    <span class="text-xs text-gray-400">
                                                        -
                                                    </span>
                                                </template>
                                            </td>

                                            {{-- ESTADO --}}
                                            <td class="px-4 py-3">
                                                {{-- LISTO --}}
                                                <template
                                                    x-if="fila.estado === 'LISTO'"
                                                >
                                                    <span
                                                        class="inline-flex items-center rounded-full
                                                            bg-green-100 px-3 py-1
                                                            text-xs font-semibold text-green-700"
                                                    >
                                                        Listo
                                                    </span>
                                                </template>

                                                {{-- NO ENCONTRADA --}}
                                                <template
                                                    x-if="fila.estado === 'AFILIADA_NO_ENCONTRADA'"
                                                >
                                                    <div>
                                                        <span
                                                            class="inline-flex items-center rounded-full
                                                                bg-red-100 px-3 py-1
                                                                text-xs font-semibold text-red-700"
                                                        >
                                                            Afiliada no encontrada
                                                        </span>
                                                        <p
                                                            class="mt-1 max-w-xs text-xs text-red-600"
                                                            x-text="fila.mensaje"
                                                        ></p>
                                                    </div>
                                                </template>

                                                {{-- AMBIGUA --}}
                                                <template
                                                    x-if="fila.estado === 'AFILIADA_AMBIGUA'"
                                                >
                                                    <div>
                                                        <span
                                                            class="inline-flex items-center rounded-full
                                                                bg-orange-100 px-3 py-1
                                                                text-xs font-semibold text-orange-700"
                                                        >
                                                            Ambigua
                                                        </span>
                                                        <p
                                                            class="mt-1 max-w-xs text-xs text-orange-600"
                                                            x-text="fila.mensaje"
                                                        ></p>
                                                    </div>
                                                </template>

                                                {{-- DUPLICADO --}}
                                                <template
                                                    x-if="fila.estado === 'APORTE_DUPLICADO'"
                                                >
                                                    <div>
                                                        <span
                                                            class="inline-flex items-center rounded-full
                                                                bg-yellow-100 px-3 py-1
                                                                text-xs font-semibold text-yellow-700"
                                                        >
                                                            Aporte duplicado
                                                        </span>
                                                        <p
                                                            class="mt-1 max-w-xs text-xs text-yellow-700"
                                                            x-text="fila.mensaje"
                                                        ></p>
                                                    </div>
                                                </template>

                                                {{-- MONTO INVÁLIDO --}}
                                                <template
                                                    x-if="fila.estado === 'MONTO_INVALIDO'"
                                                >
                                                    <div>
                                                        <span
                                                            class="inline-flex items-center rounded-full
                                                                bg-red-100 px-3 py-1
                                                                text-xs font-semibold text-red-700"
                                                        >
                                                            Monto inválido
                                                        </span>
                                                        <p
                                                            class="mt-1 max-w-xs text-xs text-red-600"
                                                            x-text="fila.mensaje"
                                                        ></p>
                                                    </div>
                                                </template>

                                                {{-- DATOS INCOMPLETOS --}}
                                                <template
                                                    x-if="fila.estado === 'DATOS_INCOMPLETOS'"
                                                >
                                                    <div>
                                                        <span
                                                            class="inline-flex items-center rounded-full
                                                                bg-gray-200 px-3 py-1
                                                                text-xs font-semibold text-gray-700"
                                                        >
                                                            Datos incompletos
                                                        </span>
                                                        <p
                                                            class="mt-1 max-w-xs text-xs text-gray-600"
                                                            x-text="fila.mensaje"
                                                        ></p>
                                                    </div>
                                                </template>

                                                {{-- SIN APORTES --}}
                                                <template
                                                    x-if="fila.estado === 'SIN_APORTES'"
                                                >
                                                    <div>
                                                        <span
                                                            class="inline-flex items-center rounded-full
                                                                bg-gray-100 px-3 py-1
                                                                text-xs font-semibold text-gray-600"
                                                        >
                                                            Sin aportes
                                                        </span>
                                                        <p
                                                            class="mt-1 max-w-xs text-xs text-gray-500"
                                                            x-text="fila.mensaje"
                                                        ></p>
                                                    </div>
                                                </template>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- =====================================================
                        AVISO
                       ===================================================== --}}
                    <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                        <div class="flex gap-3">
                            <div class="mt-0.5 text-yellow-600">
                                <x-icon name="triangle-alert" size="20"/>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-yellow-800">
                                    Importación selectiva
                                </p>
                                <p class="mt-1 text-sm text-yellow-700">
                                    Solamente se registrarán los aportes de las filas
                                    marcadas como <strong>Listo</strong>.
                                    Las filas con errores, duplicados, afiliadas no
                                    encontradas o datos incompletos no serán importadas.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- =====================================================
                        ACCIONES
                        ===================================================== --}}
                    <div class="flex items-center justify-end gap-3">
                        {{-- CANCELAR --}}
                        <form
                            method="POST"
                            action="{{ route('aportesHistoricos.cancelar') }}"
                        >
                            @csrf
                            <button
                                type="submit"
                                class="rounded-lg border border-gray-300
                                    bg-white px-5 py-2.5
                                    text-sm font-medium text-gray-700
                                    transition hover:bg-gray-50"
                            >
                                Cancelar
                            </button>
                        </form>

                        {{-- CONFIRMAR --}}
                        <form
                            method="POST"
                            action="{{ route('aportesHistoricos.importar') }}"
                        >
                            @csrf
                            <button
                                type="submit"
                                x-bind:disabled="
                                    !datos
                                    || !datos.resumen
                                    || datos.resumen.aportes_validos === 0
                                "
                                class="rounded-lg bg-[#1a34c9]
                                    px-5 py-2.5
                                    text-sm font-semibold text-white
                                    transition
                                    hover:bg-[#1429a3]
                                    disabled:cursor-not-allowed
                                    disabled:bg-gray-300"
                            >
                                Confirmar importación
                                (<span
                                    x-text="datos?.resumen?.aportes_validos ?? 0"
                                ></span>)
                            </button>
                        </form>
                    </div>
                </div>
            </x-modal>
        </div>
    </div>
    @include('carga-datos.modals.revision-transferencias')
@endsection