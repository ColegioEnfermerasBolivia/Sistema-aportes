@extends('layouts.app')
@section('title', 'Afiliados')
@section('content')

<div
    class="min-h-screen bg-[#f7f7f9] px-6 py-8 lg:px-12"
    x-data="{
        afiliadoModalAbierto: false,
        afiliadoModalCargando: false,
        afiliadoModalError: '',
        afiliadoModalTitulo: 'Información del afiliado',
        afiliadoModalRequest: 0,
        async cargarContenidoAfiliado(url, titulo) {
            const requestId = ++this.afiliadoModalRequest;
            const contenido = document.getElementById('afiliado-modal-contenido');

            this.afiliadoModalAbierto = true;
            this.afiliadoModalCargando = true;
            this.afiliadoModalError = '';
            this.afiliadoModalTitulo = titulo;

            if (contenido) {
                Alpine.destroyTree(contenido);
                contenido.replaceChildren();
            }

            try {
                const response = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (!response.ok) {
                    throw new Error('No se pudo cargar el contenido del afiliado.');
                }

                const html = await response.text();

                if (requestId !== this.afiliadoModalRequest) return;

                contenido.innerHTML = html;
                Alpine.initTree(contenido);
            } catch (error) {
                if (requestId === this.afiliadoModalRequest) {
                    this.afiliadoModalError = error.message;
                }
            } finally {
                if (requestId === this.afiliadoModalRequest) {
                    this.afiliadoModalCargando = false;
                }
            }
        }
    }"
>

    {{-- ENCABEZADO --}}
    <div class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="m-0 text-3xl font-semibold tracking-tight text-gray-900">
                Gestión de Afiliados
            </h1>
            <p class="mt-2 text-base text-gray-600">
                Administre la información de los afiliados del Colegio de Enfermeras.
            </p>
        </div>


        {{-- AGREGAR AFILIADO --}}
        @if (in_array(auth()->user()->rol?->nombre, ['ADMIN', 'USUARIO']))
            <x-modal titulo="Registrar afiliado">
                <x-slot:trigger>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2
                               rounded-full
                               bg-[#1a34c9]
                               px-7 py-3
                               text-sm font-semibold text-white
                               shadow-[0_5px_14px_rgba(26,52,201,0.25)]
                               transition
                               hover:bg-[#1429a3]
                               focus:outline-none
                               focus:ring-2
                               focus:ring-[#1a34c9]
                               focus:ring-offset-2 hover:cursor-pointer"
                    >
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center text-base">
                            <x-icon name="add" class="text-white" size="35"/>
                        </span>
                        Agregar afiliado
                    </button>
                </x-slot:trigger>
                @include('afiliados.partials.form', [
                    'modo' => 'crear',
                    'afiliado' => null
                ])
            </x-modal>
        @endif
    </div>

    {{-- CONTENEDOR PRINCIPAL --}}
    <div
        class="overflow-hidden rounded-2xl
               border border-gray-200
               bg-white
               shadow-sm flex flex-col gap-4"
    >

        {{-- FILTROS --}}
        <div
            class="border-b border-gray-200
                   bg-white
                   px-6 py-5"
        >
            <form
                method="GET"
                action="{{ route('afiliados.index') }}"
                class="flex flex-col gap-4 lg:flex-row lg:items-end"
            >
                {{-- BUSCAR --}}
                <div class="flex-1">
                    <label
                        for="buscar"
                        class="mb-2 block text-sm font-medium text-gray-800"
                    >
                        Buscar afiliado
                    </label>
                    <div class="relative">
                        <span
                            class="pointer-events-none absolute inset-y-0 left-4
                                   flex items-center
                                   text-gray-400"
                        >
                            <x-icon name="search" class="text-gray-400" size="25"/>
                        </span>

                        <input
                            type="text"
                            id="buscar"
                            name="buscar"
                            value="{{ request('buscar') }}"
                            placeholder="CI, nombre o apellido (en cualquier orden)"
                            class="w-full rounded-lg
                                   border border-gray-300
                                   bg-white
                                   py-3 pl-11 pr-4
                                   text-sm text-gray-900
                                   outline-none
                                   transition
                                   placeholder:text-gray-400
                                   focus:border-[#1a34c9]
                                   focus:ring-2
                                   focus:ring-[#1a34c9]/15"
                        >

                    </div>
                </div>

                {{-- FILTRO COLEGIO --}}
                @if (in_array(auth()->user()->rol?->nombre, ['ADMIN', 'SUPER ADMIN']))
                    <div class="w-full lg:w-64">
                        <label
                            for="colegio_id"
                            class="mb-2 block text-sm font-medium text-gray-800"
                        >
                            Colegio
                        </label>
                        <select
                            id="colegio_id"
                            name="colegio_id"
                            class="w-full rounded-lg
                                   border border-gray-300
                                   bg-white
                                   px-4 py-3
                                   text-sm text-gray-900
                                   outline-none
                                   transition
                                   focus:border-[#1a34c9]
                                   focus:ring-2
                                   focus:ring-[#1a34c9]/15"
                        >
                            <option value="">
                                Todos los colegios
                            </option>
                            @foreach ($colegios as $colegio)
                                <option
                                    value="{{ $colegio->id }}"
                                    @selected(request('colegio_id') == $colegio->id)
                                >
                                    {{ $colegio->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- BUSCAR --}}
                <button
                    type="submit"
                    class="rounded-lg
                           bg-[#1a34c9]
                           px-6 py-3
                           text-sm font-semibold text-white
                           transition
                           hover:bg-[#1429a3]
                           focus:outline-none
                           focus:ring-2
                           focus:ring-[#1a34c9]/30 flex gap-1.5 hover:cursor-pointer"
                >
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center text-base">
                        <x-icon name="search" class="text-white" size="35"/>
                    </span>
                    Buscar
                </button>

                {{-- LIMPIAR --}}
                <a
                    href="{{ route('afiliados.index') }}"
                    class="rounded-lg
                           border border-gray-300
                           bg-white
                           px-6 py-3
                           text-center
                           text-sm font-semibold text-gray-700
                           transition
                           hover:bg-gray-50 flex gap-1.5 hover:cursor-pointer"
                >
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center text-base">
                        <x-icon name="eraser" class="text-gray-700" size="35"/>
                    </span>
                    Limpiar
                </a>
            </form>
        </div>

        {{-- TABLA --}}
        <div class="max-h-150 overflow-y-auto overflow-x-auto">
            <table class="min-w-300 w-full">
                <thead class="border-b-1 border-[#1a34c9] bg-[#4f588a]/10 text-xs uppercase tracking-wide text-[#1a34c9]">
                    <tr>
                        <th class="sticky top-0 z-10 bg-[#edeef3] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 shadow-[inset_0_-2px_0_#1a34c9]">
                            Registro departamental
                        </th>
                        <th class="sticky top-0 z-10 bg-[#edeef3] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 shadow-[inset_0_-2px_0_#1a34c9]">
                            Matrícula profesional
                        </th>
                        <th class="sticky top-0 z-10 bg-[#edeef3] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 shadow-[inset_0_-2px_0_#1a34c9]">
                            CI
                        </th>
                        <th class="sticky top-0 z-10 bg-[#edeef3] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 shadow-[inset_0_-2px_0_#1a34c9]">
                            Afiliado
                        </th>
                        @if (auth()->user()->rol?->nombre === 'ADMIN' || auth()->user()->rol?->nombre === 'SUPER ADMIN')
                        <th class="sticky top-0 z-10 bg-[#edeef3] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 shadow-[inset_0_-2px_0_#1a34c9]">
                            Colegio
                        </th>
                        @endif
                        <th class="sticky top-0 z-10 bg-[#edeef3] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 shadow-[inset_0_-2px_0_#1a34c9]">
                            Celular
                        </th>
                        <th class="sticky top-0 z-10 bg-[#edeef3] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 shadow-[inset_0_-2px_0_#1a34c9]">
                            Estado
                        </th>
                        <th class="sticky top-0 z-10 bg-[#edeef3] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 shadow-[inset_0_-2px_0_#1a34c9]">
                            Fecha afiliación
                        </th>
                        <th class="sticky top-0 z-10 bg-[#edeef3] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 shadow-[inset_0_-2px_0_#1a34c9]">
                            Acciones
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse ($afiliados as $afiliado)
                        @php($camposIncompletos = $afiliado->camposIncompletos())
                        <tr class="transition hover:bg-blue-100">

                            {{-- REGISTRO --}}
                            <td class="whitespace-nowrap px-5 py-5 text-sm text-gray-700">
                                {{ $afiliado->registro_departamental }}
                            </td>

                            {{-- MATRÍCULA --}}
                            <td class="whitespace-nowrap px-5 py-5 text-sm text-gray-700">
                                {{ $afiliado->matricula_profesional }}
                            </td>

                            {{-- CI --}}
                            <td class="whitespace-nowrap px-5 py-5 text-sm text-gray-700">
                                {{ $afiliado->ci }}
                            </td>

                            {{-- AFILIADO --}}
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    
                                    <div class="min-w-0">
                                        <p
                                            class="whitespace-nowrap text-sm font-semibold {{ $camposIncompletos ? 'text-orange-700' : 'text-gray-900' }}"
                                            @if ($camposIncompletos)
                                                title="Datos incompletos: {{ implode(', ', $camposIncompletos) }}"
                                            @endif
                                        >
                                            {{ $afiliado->apellidos }}
                                            {{ $afiliado->nombres }}
                                        </p>
                                        @if ($camposIncompletos)
                                            <span class="text-xs font-medium text-orange-700">
                                                Datos incompletos
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            @if (auth()->user()->rol?->nombre === 'ADMIN' || auth()->user()->rol?->nombre === 'SUPER ADMIN')
                            {{-- COLEGIO --}}
                            <td class="px-4 py-4 text-sm text-gray-700">
                                {{ $afiliado->colegio?->nombre ?? 'Sin colegio' }}
                            </td>
                            @endif

                            {{-- CELULAR --}}
                            <td class="whitespace-nowrap px-5 py-5 text-sm text-gray-700">
                                {{ $afiliado->celular }}
                            </td>

                            {{-- ESTADO --}}
                            <td class="px-5 py-5">
                                @if ($afiliado->estado === 'ACTIVO')
                                    <span
                                        class="inline-flex items-center
                                               rounded-full
                                               border border-[#b8d8e2]
                                               bg-[#edf7fa]
                                               px-3 py-1
                                               text-xs font-medium
                                               text-[#176078]"
                                    >
                                        Activo
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center
                                               rounded-full
                                               border border-red-200
                                               bg-red-50
                                               px-3 py-1
                                               text-xs font-medium
                                               text-red-600"
                                    >
                                        Inactivo
                                    </span>
                                @endif
                            </td>

                            {{-- FECHA --}}
                            <td class="whitespace-nowrap px-5 py-5 text-sm text-gray-600">
                                {{ $afiliado->fecha_afiliacion?->format('d/m/Y') }}
                            </td>

                            {{-- ACCIONES --}}
                            <td class="px-5 py-5">
                                <div class="flex items-center gap-2">

                                    {{-- VER INFORMACIÓN --}}
                                    <button
                                        type="button"
                                        @click="cargarContenidoAfiliado(
                                            '{{ route('afiliados.detalle', $afiliado) }}',
                                            'Información del afiliado'
                                        )"
                                        class="inline-flex items-center gap-1.5
                                               rounded-lg
                                               border border-sky-500
                                               bg-white
                                               px-3 py-2
                                               text-xs font-semibold
                                               text-sky-500
                                               transition
                                               hover:bg-gray-200
                                               hover:cursor-pointer"
                                    >
                                        <span class="flex h-5 w-5 shrink-0 items-center justify-center text-base">
                                            <x-icon name="see" class="text-sky-500" size="25"/>
                                        </span>
                                        Ver
                                    </button>

                                    {{-- EDITAR --}}
                                    @if (in_array(auth()->user()->rol?->nombre, [
                                        'SUPER ADMIN',
                                        'ADMIN',
                                        'USUARIO'
                                    ]))

                                        <button
                                            type="button"
                                            @click="cargarContenidoAfiliado(
                                                '{{ route('afiliados.editar', $afiliado) }}',
                                                'Editar afiliado'
                                            )"
                                            class="inline-flex items-center
                                                   rounded-lg
                                                   border border-blue-500
                                                   bg-white
                                                   px-3 py-2
                                                   text-xs font-semibold
                                                   text-blue-500
                                                   transition
                                                   hover:bg-blue-100
                                                   hover:cursor-pointer"
                                        >
                                            <span class="flex h-5 w-5 shrink-0 items-center justify-center text-base">
                                                <x-icon name="edit" class="text-[#3B82F6]" size="35"/>
                                            </span>
                                            Editar
                                        </button>
                                    @endif

                                    {{-- ACTIVAR / DESACTIVAR --}}
                                    @if (auth()->user()->rol?->nombre === 'ADMIN')
                                        <form
                                            method="POST"
                                            action="{{ route('afiliados.estado', $afiliado) }}"
                                            class="inline"
                                            onsubmit="return confirm(
                                                '¿Está seguro de cambiar el estado de este afiliado?'
                                            );"
                                        >
                                            @csrf
                                            @method('PATCH')
                                            <button
                                                type="submit"
                                                class="inline-flex items-center
                                                       rounded-lg
                                                       bg-white
                                                       px-3 py-2
                                                       text-xs font-semibold
                                                       hover:cursor-pointer
                                                       hover:bg-gray-50
                                                        {{ $afiliado->estado === 'ACTIVO'
                                                            ? 'text-amber-500 border border-amber-500'
                                                            : 'text-green-600 border border-green-600'
                                                        }}"
                                            >
                                                @if ($afiliado->estado === 'ACTIVO')
                                                    <x-icon name="userOff" size="20" />
                                                    Desactivar
                                                @else
                                                    <x-icon name="userUp" size="20" />
                                                    Activar
                                                @endif
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="9"
                                class="px-6 py-14 text-center"
                            >
                                <p class="text-sm font-medium text-gray-600">
                                    No se encontraron afiliados.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-modal
            titulo="Información del afiliado"
            ancho="max-w-5xl"
            titulo-alpine="afiliadoModalTitulo"
            x-model="afiliadoModalAbierto"
        >
            <div
                x-show="afiliadoModalCargando"
                class="py-10 text-center text-sm text-gray-600"
            >
                Cargando...
            </div>
            <p
                x-show="afiliadoModalError"
                x-text="afiliadoModalError"
                class="py-6 text-center text-sm text-red-600"
            ></p>
            <div id="afiliado-modal-contenido"></div>
        </x-modal>

        {{-- PAGINACIÓN - SOLO VISUAL POR AHORA --}}
        <div
            class="flex flex-col gap-4
                border-t border-gray-200
                bg-white
                px-6 py-4
                sm:flex-row
                sm:items-center
                sm:justify-between"
        >
            <p class="text-sm text-gray-600">
                Mostrando
                <span class="font-semibold text-gray-800">
                    {{ $afiliados->firstItem() ?? 0 }}
                </span>
                a
                <span class="font-semibold text-gray-800">
                    {{ $afiliados->lastItem() ?? 0 }}
                </span>
                de
                <span class="font-semibold text-gray-800">
                    {{ $afiliados->total() }}
                </span>
                afiliados
            </p>

            <div>
                {{ $afiliados->links() }}
            </div>
        </div>
        <script>
        document.addEventListener('change', function (event) {
            if (event.target.id !== 'editar_colegio') {
                return;
            }
        
            const colegioSelect = event.target;
            const modal = colegioSelect.closest('form') || document;
        
            const contenedorFecha = modal.querySelector(
                '#contenedor_fecha_transferencia'
            );
        
            const fechaTransferencia = modal.querySelector(
                '#fecha_transferencia'
            );
        
            if (!contenedorFecha || !fechaTransferencia) {
                console.warn('No se encontró el campo de fecha de transferencia.');
                return;
            }
        
            const colegioOriginal = colegioSelect.dataset.colegioOriginal;
            const colegioSeleccionado = colegioSelect.value;
        
            const huboCambio =
                colegioSeleccionado !== '' &&
                colegioSeleccionado !== colegioOriginal;
        
            contenedorFecha.classList.toggle('hidden', !huboCambio);
            fechaTransferencia.required = huboCambio;
        
            if (!huboCambio) {
                fechaTransferencia.value = '';
            }
        });
        </script>
</div>

@endsection