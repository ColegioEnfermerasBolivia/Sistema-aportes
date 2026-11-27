@extends('layouts.app')
@section('title', 'Egresadas universitarias')
@section('content')

@php
    $mostrarErroresEgresada = $errors->any()
        && old('_form_type') === 'egresada';
    $modoAnterior = old('_form_mode', 'crear');
    $idAnterior = old('_egresada_id');
    $accionEgresadaAnterior = $modoAnterior === 'editar' && $idAnterior
        ? route('egresadas.update', $idAnterior)
        : route('egresadas.store');
    $datosEgresadaAnteriores = [
        'id' => $idAnterior,
        'ci' => old('ci', ''),
        'nombres' => old('nombres', ''),
        'apellidos' => old('apellidos', ''),
        'celular' => old('celular', ''),
    ];
    $mostrarErroresUniversidad = $errors->any()
        && old('_form_type') === 'universidad';
    $datosUniversidadAnteriores = [
        'nombre' => old('nombre', ''),
        'sigla' => old('sigla', ''),
        'tipo' => old('tipo', ''),
    ];
    $mostrarErroresImportacion = $errors->any()
        && old('_form_type') === 'importacion';
    $mostrarErroresConversion = $errors->any()
        && old('_form_type') === 'conversion';
    $egresadaConversionAnterior = $mostrarErroresConversion
        ? $egresadas->getCollection()
            ->firstWhere('id', (int) old('_egresada_id'))
            ?->loadMissing('formaciones.universidad', 'formaciones.departamento')
            ?->toArray()
        : null;
    $datosConversionAnteriores = [
        'registro_departamental' => old('registro_departamental', ''),
        'matricula_profesional' => old('matricula_profesional', ''),
        'celular' => old('celular', ''),
        'fecha_afiliacion' => old('fecha_afiliacion', now()->toDateString()),
        'colegio_id' => old(
            'colegio_id',
            $colegiosConvertibles->first()?->id ?? ''
        ),
    ];
@endphp

<div
    class="min-h-screen bg-[#f7f7f9] px-6 py-8 lg:px-12"
    x-data="{
        modalEgresadaAbierto: @js($mostrarErroresEgresada),
        modalUniversidadAbierto: @js($mostrarErroresUniversidad),
        modalImportacionAbierto: @js($mostrarErroresImportacion),
        modalConversionAbierto: @js($mostrarErroresConversion),
        tituloModalEgresada: @js($modoAnterior === 'editar' ? 'Editar egresada' : 'Registrar egresada'),
        editandoEgresada: @js($mostrarErroresEgresada && $modoAnterior === 'editar'),
        cargandoEgresada: false,
        errorEgresada: '',
        accionEgresada: @js($mostrarErroresEgresada ? $accionEgresadaAnterior : route('egresadas.store')),
        egresada: @js($mostrarErroresEgresada ? $datosEgresadaAnteriores : [
            'id' => null,
            'ci' => '',
            'nombres' => '',
            'apellidos' => '',
            'celular' => '',
        ]),
        formaciones: @js($mostrarErroresEgresada ? old('formaciones', []) : []),
        universidad: @js($mostrarErroresUniversidad ? $datosUniversidadAnteriores : [
            'nombre' => '',
            'sigla' => '',
            'tipo' => '',
        ]),
        departamentoInicial: @js($departamentosOperables->first()?->id ?? ''),
        colegioInicial: @js($colegiosConvertibles->first()?->id ?? ''),
        egresadaConversion: @js($egresadaConversionAnterior ?? []),
        accionConversion: @js($egresadaConversionAnterior
            ? route('egresadas.convertir', $egresadaConversionAnterior['id'])
            : ''),
        conversion: @js($datosConversionAnteriores),
        urlEgresadas: @js(url('/egresadas')),
        nuevaFormacion() {
            return {
                id: null,
                universidad_id: '',
                departamento_id: this.departamentoInicial,
                carrera: '',
                fecha_egreso: '',
                estado_titulacion: 'EGRESADA',
                numero_titulo: ''
            };
        },
        abrirRegistroEgresada() {
            this.editandoEgresada = false;
            this.tituloModalEgresada = 'Registrar egresada';
            this.accionEgresada = @js(route('egresadas.store'));
            this.egresada = {
                id: null,
                ci: '',
                nombres: '',
                apellidos: '',
                celular: ''
            };
            this.formaciones = [this.nuevaFormacion()];
            this.errorEgresada = '';
            this.modalEgresadaAbierto = true;
        },
        async editarEgresada(url) {
            this.cargandoEgresada = true;
            this.errorEgresada = '';

            try {
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' }
                });

                if (!response.ok) {
                    throw new Error('No se pudieron cargar los datos de la egresada.');
                }

                const resultado = await response.json();
                this.egresada = resultado.data;
                this.formaciones = resultado.data.formaciones.map((formacion) => ({
                    id: formacion.id,
                    universidad_id: String(formacion.universidad_id),
                    departamento_id: String(formacion.departamento_id),
                    carrera: formacion.carrera || '',
                    fecha_egreso: formacion.fecha_egreso
                        ? String(formacion.fecha_egreso).slice(0, 10)
                        : '',
                    estado_titulacion: formacion.estado_titulacion || 'EGRESADA',
                    numero_titulo: formacion.numero_titulo || ''
                }));
                this.editandoEgresada = true;
                this.tituloModalEgresada = 'Editar egresada';
                this.accionEgresada = `${this.urlEgresadas}/${resultado.data.id}`;
                this.modalEgresadaAbierto = true;
            } catch (error) {
                this.errorEgresada = error.message;
                this.modalEgresadaAbierto = true;
            } finally {
                this.cargandoEgresada = false;
            }
        },
        abrirConversion(egresada) {
            this.egresadaConversion = egresada;
            this.accionConversion = `${this.urlEgresadas}/${egresada.id}/convertir-afiliada`;
            this.conversion = {
                registro_departamental: '',
                matricula_profesional: '',
                celular: egresada.celular || '',
                fecha_afiliacion: @js(now()->toDateString()),
                colegio_id: String(this.colegioInicial || '')
            };
            this.modalConversionAbierto = true;
        }
    }"
>
    <div class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="m-0 text-3xl font-semibold tracking-tight text-gray-900">
                Registro de Egresadas Universitarias
            </h1>
            <p class="mt-2 text-base text-gray-600">
                Consulte y gestione egresadas y sus formaciones universitarias.
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            <button
                type="button"
                @click="abrirRegistroEgresada()"
                @disabled($departamentosOperables->isEmpty())
                class="inline-flex items-center gap-2 rounded-full bg-[#1a34c9]
                       px-6 py-3 text-sm font-semibold text-white shadow-sm
                       transition hover:bg-[#1429a3] disabled:cursor-not-allowed
                       disabled:opacity-50 hover:cursor-pointer"
            >
                <x-icon name="add" class="text-white" size="25"/>
                Agregar egresada
            </button>

            @if (auth()->user()->rol?->nombre === 'SUPER ADMIN')
                <button
                    type="button"
                    @click="modalImportacionAbierto = true"
                    class="inline-flex items-center gap-2 rounded-full border
                           border-[#1a34c9] bg-white px-6 py-3 text-sm
                           font-semibold text-[#1a34c9] transition hover:bg-blue-50 hover:cursor-pointer"
                >
                    Carga masiva CSV
                </button>
                <button
                    type="button"
                    @click="modalUniversidadAbierto = true"
                    class="inline-flex items-center gap-2 rounded-full border
                           border-green-600 bg-white px-6 py-3 text-sm
                           font-semibold text-green-700 transition hover:bg-green-50 hover:cursor-pointer"
                >
                    <x-icon name="add" class="text-green-700" size="25"/>
                    Agregar universidad
                </button>
            @endif
        </div>
    </div>

    @if ($departamentosOperables->isEmpty())
        <p class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-5 py-3 text-sm text-amber-800">
            No se pudo determinar el departamento de su colegio. No es posible registrar formaciones hasta que se configure esa correspondencia.
        </p>
    @endif

    <div class="flex flex-col gap-4 overflow-hidden rounded-2xl border
                border-gray-200 bg-white shadow-sm">
        @include('egresadas._filtros')

        @include('egresadas._tabla')
    </div>

    @include('egresadas._modal-editar')
    @include('egresadas._modal-convertir')

    @if (auth()->user()->rol?->nombre === 'SUPER ADMIN')
        <x-modal
            titulo="Importar egresadas desde CSV"
            ancho="max-w-2xl"
            x-model="modalImportacionAbierto"
        >
            <p class="mb-4 text-sm text-gray-600">
                El archivo debe tener una fila por formación. Las filas con el mismo CI se agrupan en una sola egresada.
                Se aceptan archivos separados por coma o punto y coma.
            </p>
            <p class="mb-5 rounded-lg bg-blue-50 px-4 py-3 text-xs leading-5 text-blue-900">
                Columnas requeridas: <strong>ci, nombres, apellidos, universidad, departamento, carrera</strong>.<br>
                Opcionales: celular, fecha_egreso, estado_titulacion (EGRESADA o TITULADA), numero_titulo.
                Departamento acepta código o nombre; universidad debe estar registrada.
            </p>
            <form
                method="POST"
                action="{{ route('egresadas.importar.store') }}"
                enctype="multipart/form-data"
                class="space-y-5"
            >
                @csrf
                <input type="hidden" name="_form_type" value="importacion">
                <div>
                    <label for="archivo_egresadas" class="mb-2 block text-sm font-medium text-gray-700">
                        Archivo CSV
                    </label>
                    <input
                        id="archivo_egresadas"
                        name="archivo"
                        type="file"
                        accept=".csv,text/csv"
                        required
                        class="block w-full rounded-lg border border-gray-300 bg-white
                               px-3 py-3 text-sm text-gray-700 file:mr-4 file:rounded-md
                               file:border-0 file:bg-blue-50 file:px-4 file:py-2
                               file:font-semibold file:text-[#1a34c9]"
                    >
                </div>
                <div class="flex justify-end border-t border-gray-200 pt-5">
                    <button
                        type="submit"
                        class="rounded-lg bg-[#1a34c9] px-6 py-3 text-sm
                               font-semibold text-white hover:bg-[#1429a3] hover:cursor-pointer"
                    >
                        Importar CSV
                    </button>
                </div>
            </form>
        </x-modal>

        <x-modal
            titulo="Agregar universidad"
            ancho="max-w-2xl"
            x-model="modalUniversidadAbierto"
        >
            <form
                method="POST"
                action="{{ route('universidades.store') }}"
                class="space-y-5"
            >
                @csrf
                <input type="hidden" name="_form_type" value="universidad">
                <div>
                    <label for="universidad_nombre" class="mb-2 block text-sm font-medium text-gray-700">
                        Nombre
                    </label>
                    <input
                        id="universidad_nombre"
                        name="nombre"
                        type="text"
                        maxlength="200"
                        required
                        value="{{ $mostrarErroresUniversidad ? $datosUniversidadAnteriores['nombre'] : '' }}"
                        class="w-full rounded-lg border border-gray-300 px-3.5 py-3 text-sm"
                    >
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label for="universidad_sigla" class="mb-2 block text-sm font-medium text-gray-700">
                            Sigla
                        </label>
                        <input
                            id="universidad_sigla"
                            name="sigla"
                            type="text"
                            maxlength="30"
                            value="{{ $mostrarErroresUniversidad ? $datosUniversidadAnteriores['sigla'] : '' }}"
                            class="w-full rounded-lg border border-gray-300 px-3.5 py-3 text-sm"
                        >
                    </div>
                    <div>
                        <label for="universidad_tipo" class="mb-2 block text-sm font-medium text-gray-700">
                            Tipo
                        </label>
                        <select
                            id="universidad_tipo"
                            name="tipo"
                            required
                            class="w-full rounded-lg border border-gray-300 px-3.5 py-3 text-sm"
                        >
                            <option value="">Seleccione un tipo</option>
                            @foreach (\App\Models\Universidad::TIPOS_UNIVERSIDAD as $tipoUniversidad)
                                <option
                                    value="{{ $tipoUniversidad }}"
                                    @selected(
                                        ($mostrarErroresUniversidad
                                            ? $datosUniversidadAnteriores['tipo']
                                            : '') === $tipoUniversidad
                                    )
                                >
                                    {{ $tipoUniversidad === 'PUBLICA' ? 'Pública' : 'Privada' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex justify-end border-t border-gray-200 pt-5">
                    <button
                        type="submit"
                        class="rounded-lg bg-[#1a34c9] px-6 py-3 text-sm
                               font-semibold text-white hover:bg-[#1429a3] hover:cursor-pointer"
                    >
                        Registrar universidad
                    </button>
                </div>
            </form>
        </x-modal>
    @endif
</div>

@endsection
