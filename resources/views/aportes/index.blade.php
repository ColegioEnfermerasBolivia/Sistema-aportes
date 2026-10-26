@extends('layouts.app')
@section('title', 'Aportes')
@section('content')

<div
    x-data="{
        modalPago: false,
        limpiarFormularioPago() {
            this.$dispatch('limpiar-formulario-pago');
        },
        abrirModalPago() {
            this.limpiarFormularioPago();
            this.modalPago = true;
        },
        cerrarModalPago() {
            this.modalPago = false;
            this.limpiarFormularioPago();
        }
    }"
    @cerrar-modal-pago.window="cerrarModalPago()"
    class="min-h-screen bg-[#f7f7f9] px-6 py-8 lg:px-12"
    >

    {{--ENCABEZADO--}}

    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-gray-900">
                Gestión de Aportes
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Consulta y seguimiento de los aportes consolidados de los afiliados.
            </p>
        </div>

        {{-- Botón registrar pago --}}
        @if (in_array(auth()->user()->rol?->nombre, ['ADMIN', 'USUARIO']))
            <button
                type="button"
                @click="abrirModalPago()"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#1a34c9] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1429a3] focus:outline-none focus:ring-2 focus:ring-[#1a34c9]/30 hover:cursor-pointer"
            >
                <span class="text-lg leading-none">
                    <x-icon name="cashRegister" class="text-white" size="20"/>
                </span>
                Registrar pago
            </button>
        @endif
    </div>

    {{-- MENSAJES--}}

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    {{-- CONTENEDOR PRINCIPAL--}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">

        {{-- FILTROS--}}
        <div class="border-b border-gray-200 px-6 py-5">
            <form
                method="GET"
                action="{{ route('aportes.index') }}"
                class="flex flex-col gap-4 lg:flex-row lg:items-end"
            >
                {{-- Buscar --}}
                <div class="flex-1">
                    <label
                        for="buscar"
                        class="mb-1.5 block text-sm font-medium text-gray-700"
                    >
                        Buscar afiliado
                    </label>
                    <input
                        type="text"
                        id="buscar"
                        name="buscar"
                        value="{{ request('buscar') }}"
                        placeholder="CI, nombres o apellidos..."
                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-[#1a34c9] focus:ring-2 focus:ring-[#1a34c9]/10"
                    >
                </div>

                {{-- Gestión --}}
                <div class="w-full lg:w-44">
                    <label
                        for="gestion"
                        class="mb-1.5 block text-sm font-medium text-gray-700"
                    >
                        Gestión
                    </label>
                    <select
                        id="gestion"
                        name="gestion"
                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none transition focus:border-[#1a34c9] focus:ring-2 focus:ring-[#1a34c9]/10"
                    >
                        @foreach ($gestiones as $anio)
                            <option
                                value="{{ $anio }}"
                                @selected((int) $gestion === (int) $anio)
                            >
                                {{ $anio }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Colegio --}}
                @if (in_array(auth()->user()->rol?->nombre, ['SUPER ADMIN', 'ADMIN']))
                    <div class="w-full lg:w-64">
                        <label
                            for="colegio_id"
                            class="mb-1.5 block text-sm font-medium text-gray-700"
                        >
                            Colegio
                        </label>
                        <select
                            id="colegio_id"
                            name="colegio_id"
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none transition focus:border-[#1a34c9] focus:ring-2 focus:ring-[#1a34c9]/10"
                        >
                            <option value="">
                                Todos los colegios
                            </option>
                            @foreach ($colegios as $colegio)
                                <option
                                    value="{{ $colegio->id }}"
                                    @selected((string) request('colegio_id') === (string) $colegio->id)
                                >
                                    {{ $colegio->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Botones --}}
                <div class="flex gap-2">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-[#1a34c9] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#1429a3] focus:outline-none focus:ring-2 focus:ring-[#1a34c9]/30 hover:cursor-pointer gap-1.5"
                    >
                        <x-icon name="search" class="text-white" size="20"/>
                        Buscar
                    </button>
                    <a
                        href="{{ route('aportes.index') }}"
                        class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 hover:cursor-pointer gap-1.5"
                    >
                        <x-icon name="eraser" class="text-gray-700" size="20"/>
                        Limpiar
                    </a>
                </div>
            </form>
        </div>

        {{--TABLA DE APORTES--}}
        <div class="overflow-x-auto">
            <table class="min-w-300 w-full border-collapse text-sm">
                <thead>
                    <tr class="bg-[#f1f3f8] text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                        <th class="whitespace-nowrap px-5 py-4">
                           Nombres
                        </th>
                        <th class="whitespace-nowrap px-5 py-4">
                           Apellidos
                        </th>
                        <th class="whitespace-nowrap px-5 py-4">
                            CI
                        </th>
                        <th class="whitespace-nowrap px-5 py-4 text-center"> 
                            Último aporte 
                        </th>
                        <th class="whitespace-nowrap px-5 py-4 text-center">
                            Gestión
                        </th>
                        <th class="px-4 py-4 text-center">
                            ENE
                        </th>
                        <th class="px-4 py-4 text-center">
                            FEB
                        </th>
                        <th class="px-4 py-4 text-center">
                            MAR
                        </th>
                        <th class="px-4 py-4 text-center">
                            ABR
                        </th>
                        <th class="px-4 py-4 text-center">
                            MAY
                        </th>
                        <th class="px-4 py-4 text-center">
                            JUN
                        </th>
                        <th class="px-4 py-4 text-center">
                            JUL
                        </th>
                        <th class="px-4 py-4 text-center">
                            AGO
                        </th>
                        <th class="px-4 py-4 text-center">
                            SEP
                        </th>
                        <th class="px-4 py-4 text-center">
                            OCT
                        </th>
                        <th class="px-4 py-4 text-center">
                            NOV
                        </th>
                        <th class="px-4 py-4 text-center">
                            DIC
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($afiliados as $afiliado)
                        @php
                            $aportesPorMes = $afiliado->aportes->keyBy('mes');
                            $ultimoAporte = $ultimoAportePorAfiliado->get($afiliado->id);
                            $meses = [
                                1 =>'ENE',
                                2 => 'FEB',
                                3 => 'MAR',
                                4 => 'ABR',
                                5 => 'MAY',
                                6 => 'JUN',
                                7 => 'JUL',
                                8 => 'AGO',
                                9 => 'SEP',
                                10 => 'OCT',
                                11 => 'NOV',
                                12 => 'DIC',
                            ];
                        @endphp
                        <tr class="transition hover:bg-gray-200">
                            {{-- Nombres --}}
                            <td class="whitespace-nowrap px-5 py-4 font-medium text-gray-900">
                                {{ $afiliado->nombres }}
                            </td>

                            {{-- Apellidos --}}
                            <td class="whitespace-nowrap px-5 py-4 text-gray-700">
                                {{ $afiliado->apellidos }}
                            </td>

                            {{-- CI --}}
                            <td class="whitespace-nowrap px-5 py-4 text-gray-700">
                                {{ $afiliado->ci }}
                            </td>

                            <td class="px-5 py-4 text-center">
                                @if ($ultimoAporte) 
                                    <div class="flex flex-col items-center"> 
                                        <span class="font-semibold text-gray-800"> 
                                            Bs {{ number_format((float) $ultimoAporte->monto, 2, '.', ',') }} 
                                        </span> 
                                        <span class="mt-0.5 text-xs text-gray-500"> 
                                        {{ $meses[$ultimoAporte->mes] }} {{ $ultimoAporte->year }} 
                                        </span> 
                                    </div> @else 
                                    <span class="text-gray-400"> — </span> 
                                @endif
                            </td>

                            {{-- Gestión --}}
                            <td class="px-5 py-4 text-center font-medium text-gray-700">
                                {{ $gestion }}
                            </td>

                            {{-- MESES --}}
                            @for ($mes = 1; $mes <= 12; $mes++)
                                <td class="px-4 py-4 text-center">
                                    @if ($aportesPorMes->has($mes))
                                        <span
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-green-100 text-sm font-bold text-green-700"
                                            title="Aporte verificado"
                                        >
                                            ✓
                                        </span>
                                    @else
                                        <span class="text-gray-300">
                                            —
                                        </span>
                                    @endif
                                </td>
                            @endfor
                        </tr>
                    @empty
                        <tr>
                            <td colspan="100%" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center gap-3">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-100">
                                        <x-icon name="file-search" class="text-gray-400" size="28"/>
                                    </div>

                                    <div>
                                        <p class="text-base font-semibold text-gray-700">
                                            No se encontraron aportes
                                        </p>

                                        <p class="mt-1 text-sm text-gray-500">
                                            @if (request()->filled('buscar'))
                                                La afiliada o el afiliado buscado no tiene aportes registrados en el sistema.
                                            @else
                                                No hay afiliados con aportes verificados en la gestión {{ $gestion }}.
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINACIÓN--}}
        @if ($afiliados->hasPages())
            <div class="border-t border-gray-200 px-6 py-4">
                {{ $afiliados->links() }}
            </div>
        @endif
    </div>

    {{-- MODAL REGISTRAR PAGO--}}
    <x-modal
        titulo="Registrar pago"
        ancho="max-w-2xl"
        x-model="modalPago"
    >
        @include('aportes.partials.pagoForm')
    </x-modal>
</div>
@endsection
