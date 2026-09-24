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

        {{-- CARGA DE AFILIADAS --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-6 py-5">
                <h2 class="text-lg font-semibold text-gray-800">Afiliadas</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Carga las afiliadas desde un archivo CSV.
                </p>
            </div>

            <div class="px-6 py-6">
                <form
                    action="{{ route('carga-datos.afiliados.validar') }}"
                    method="POST"
                    enctype="multipart/form-data"
                >

                    @csrf
                    <div class="flex flex-col gap-4 md:flex-row md:items-end">

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
                                        class="block w-full cursor-pointer rounded-lg
                                            border border-gray-300
                                            bg-white text-sm text-gray-600
                                            file:mr-4
                                            file:border-0
                                            file:bg-gray-100
                                            file:px-4
                                            file:py-2.5
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
                        <div>
                            <button
                                type="submit"
                                class="inline-flex items-center gap-2
                                       rounded-lg
                                       bg-[#1a34c9]
                                       px-5 py-2.5
                                       text-sm font-medium
                                       text-white
                                       transition
                                       hover:bg-blue-800
                                       focus:outline-none
                                       focus:ring-2
                                       focus:ring-[#1a34c9]/30
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

        {{-- MODAL DE PREVISUALIZACIÓN --}}
        <div
            x-data="{mostrarPrevisualizacion: @js($mostrarPrevisualizacion ?? false),
            datos: @js($datosValidacion ?? null),
            filtro: 'todas'}"
        >

            <x-modal
                x-model="mostrarPrevisualizacion"
                titulo="Previsualización de afiliadas"
                ancho="max-w-7xl"
            >
                <div class="space-y-6">
    
                    {{-- RESUMEN --}}
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

                    {{-- TOTAL --}}
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-sm text-gray-500">
                            Total
                        </p>

                        <p
                            class="mt-1 text-2xl font-semibold text-gray-900"
                            x-text="datos?.total ?? 0"
                        ></p>
                    </div>

                    {{-- VÁLIDAS --}}
                    <div class="rounded-xl border border-green-200 bg-green-50 p-4">
                        <p class="text-sm text-green-700">
                            Válidas
                        </p>

                        <p
                            class="mt-1 text-2xl font-semibold text-green-700"
                            x-text="datos?.validas ?? 0"
                        ></p>
                    </div>

                    {{-- CON ERRORES --}}
                    <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                        <p class="text-sm text-red-700">
                            Con errores
                        </p>

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
    </div>
@endsection