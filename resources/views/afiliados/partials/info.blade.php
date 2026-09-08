<div
    x-data="{
        laboralModal: false,
        modoLaboral: 'crear',
        trabajoEditar: null,

        institucion: '',
        tipoContrato: '',
        haberBasico: '',
        fechaIngreso: '',
        culminado: false,
        fechaRetiro: '',

        academicaModal: false,
        modoAcademica: 'crear',
        academicaId: null,

        postgradoId: '',
        gradoAcademicoId: '',
        institucionAcademica: '',
        numeroTitulo: '',
        fechaTitulo: '',
        numeroCertificado: ''
    }"
>

    @php
        $trabajoActivo = $afiliado->informacionesLaborales
            ->firstWhere('culminado', false);

        $historialLaboral = $afiliado->informacionesLaborales
            ->where('culminado', true);
    @endphp


    {{-- ====================================================== --}}
    {{-- DATOS DEL AFILIADO --}}
    {{-- ====================================================== --}}

    <section class="mb-8">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="m-0 text-lg font-semibold text-gray-900">
                    Datos del afiliado
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Información general y de registro profesional.
                </p>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200">
            <table class="w-full">
                <tbody class="divide-y divide-gray-200">
                    <tr>
                        <th
                            class="w-1/3 bg-[#f7f7f9] px-4 py-3 text-left
                                   text-sm font-semibold text-gray-700"
                        >
                            Registro departamental
                        </th>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            {{ $afiliado->registro_departamental }}
                        </td>
                    </tr>

                    <tr>
                        <th
                            class="bg-[#f7f7f9] px-4 py-3 text-left
                                   text-sm font-semibold text-gray-700"
                        >
                            Matrícula profesional
                        </th>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            {{ $afiliado->matricula_profesional }}
                        </td>
                    </tr>

                    <tr>
                        <th
                            class="bg-[#f7f7f9] px-4 py-3 text-left
                                   text-sm font-semibold text-gray-700"
                        >
                            CI
                        </th>

                        <td class="px-4 py-3 text-sm text-gray-900">
                            {{ $afiliado->ci }}
                        </td>
                    </tr>

                    <tr>
                        <th
                            class="bg-[#f7f7f9] px-4 py-3 text-left
                                   text-sm font-semibold text-gray-700"
                        >
                            Nombre completo
                        </th>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            {{ $afiliado->nombres }}
                            {{ $afiliado->apellidos }}
                        </td>
                    </tr>

                    <tr>
                        <th
                            class="bg-[#f7f7f9] px-4 py-3 text-left
                                   text-sm font-semibold text-gray-700"
                        >
                            Colegio
                        </th>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            {{ $afiliado->colegio?->nombre ?? 'Sin colegio' }}
                        </td>
                    </tr>

                    <tr>
                        <th
                            class="bg-[#f7f7f9] px-4 py-3 text-left
                                   text-sm font-semibold text-gray-700"
                        >
                            Celular
                        </th>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            {{ $afiliado->celular }}
                        </td>
                    </tr>

                    <tr>
                        <th
                            class="bg-[#f7f7f9] px-4 py-3 text-left
                                   text-sm font-semibold text-gray-700"
                        >
                            Estado
                        </th>
                        <td class="px-4 py-3">

                            @if ($afiliado->estado === 'ACTIVO')
                                <span
                                    class="inline-flex rounded-full
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
                                    class="inline-flex rounded-full
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
                    </tr>

                    <tr>
                        <th
                            class="bg-[#f7f7f9] px-4 py-3 text-left
                                   text-sm font-semibold text-gray-700"
                        >
                            Fecha de afiliación
                        </th>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            {{ $afiliado->fecha_afiliacion?->format('d/m/Y') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    {{-- INFORMACIÓN LABORAL --}}
    <section class="mb-8">
        <div
            class="mb-4 flex flex-col gap-3
                   sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h3 class="m-0 text-lg font-semibold text-gray-900">
                    Información laboral
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Situación laboral actual e historial de trabajos.
                </p>
            </div>

            {{-- REGISTRAR TRABAJO --}}
            @if (!$trabajoActivo)
                <button
                    type="button"
                    @click="
                        modoLaboral = 'crear';
                        trabajoEditar = null;
                        institucion = '';
                        tipoContrato = '';
                        haberBasico = '';
                        fechaIngreso = '';
                        culminado = false;
                        fechaRetiro = '';
                        laboralModal = true;
                    "
                    class="inline-flex items-center justify-center
                           rounded-lg
                           bg-[#1a34c9]
                           px-4 py-2.5
                           text-sm font-semibold text-white
                           transition
                           hover:bg-[#1429a3]"
                >
                    <span class="mr-1 text-lg">
                        +
                    </span>
                    Registrar trabajo
                </button>
            @endif
        </div>

        {{-- TRABAJO ACTUAL --}}
        @if ($trabajoActivo)
            <div class="mb-5">
                <div
                    class="mb-3 flex items-center justify-between"
                >
                    <h4 class="m-0 text-sm font-semibold text-gray-800">
                        Trabajo actual
                    </h4>
                    <span
                        class="rounded-full
                               bg-green-50
                               px-3 py-1
                               text-xs font-medium
                               text-green-700"
                    >
                        Activo
                    </span>
                </div>

                <div class="overflow-hidden rounded-xl border border-gray-200">
                    <table class="w-full">
                        <thead class="bg-[#f1f3f8]">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">
                                    Institución
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">
                                    Tipo de contrato
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">
                                    Haber básico
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">
                                    Aporte referencial
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">
                                    Fecha de ingreso
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">
                                    Acción
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td class="px-4 py-4 text-sm text-gray-900">
                                    {{ $trabajoActivo->institucion }}
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-700">
                                    {{ $trabajoActivo->tipo_contrato }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">
                                    Bs {{ number_format($trabajoActivo->haber_basico, 2, ',', '.') }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-sm font-medium text-gray-900">
                                    Bs {{ number_format($trabajoActivo->monto_aporte, 2, ',', '.') }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">
                                    {{ $trabajoActivo->fecha_ingreso_trabajo?->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-4">
                                    <button
                                        type="button"
                                        @click="
                                            modoLaboral = 'editar';
                                            trabajoEditar = {{ $trabajoActivo->id }};
                                            institucion = @js($trabajoActivo->institucion);
                                            tipoContrato = @js($trabajoActivo->tipo_contrato);
                                            haberBasico = @js($trabajoActivo->haber_basico);
                                            fechaIngreso = @js(
                                                $trabajoActivo->fecha_ingreso_trabajo?->format('Y-m-d')
                                            );
                                            culminado = {{ $trabajoActivo->culminado ? 'true' : 'false' }};
                                            fechaRetiro = @js(
                                                $trabajoActivo->fecha_retiro?->format('Y-m-d')
                                            );
                                            laboralModal = true;
                                        "
                                        class="rounded-lg
                                               border border-gray-300
                                               bg-white
                                               px-3 py-2
                                               text-xs font-semibold
                                               text-gray-700
                                               transition
                                               hover:bg-gray-50
                                               hover:text-[#1a34c9]"
                                    >
                                        Editar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div
                class="mb-5 rounded-xl border border-gray-200
                       bg-gray-50 px-5 py-4"
            >
                <p class="m-0 text-sm text-gray-600">
                    Este afiliado no tiene un trabajo activo.
                </p>
            </div>

        @endif

        {{-- HISTORIAL LABORAL --}}
        @if ($historialLaboral->isNotEmpty())
            <div>
                <h4 class="mb-3 text-sm font-semibold text-gray-800">
                    Historial laboral
                </h4>
                <div class="overflow-hidden rounded-xl border border-gray-200">
                    <table class="w-full">
                        <thead class="bg-[#f1f3f8]">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">
                                    Institución
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">
                                    Tipo de contrato
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">
                                    Haber básico
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">
                                    Aporte referencial
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">
                                    Ingreso
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">
                                    Retiro
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @foreach ($historialLaboral as $trabajo)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4 text-sm text-gray-900">
                                        {{ $trabajo->institucion }}
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-700">
                                        {{ $trabajo->tipo_contrato }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">
                                        Bs {{ number_format($trabajo->haber_basico, 2, ',', '.') }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">
                                        Bs {{ number_format($trabajo->monto_aporte, 2, ',', '.') }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">
                                        {{ $trabajo->fecha_ingreso_trabajo?->format('d/m/Y') }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">
                                        {{ $trabajo->fecha_retiro?->format('d/m/Y') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </section>

    {{-- INFORMACIÓN ACADÉMICA --}}
    <section>
        <div
            class="mb-4 flex flex-col gap-3
                   sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h3 class="m-0 text-lg font-semibold text-gray-900">
                    Información académica
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Postgrados y grados académicos registrados.
                </p>
            </div>

            {{-- REGISTRAR INFORMACIÓN ACADÉMICA --}}
            <button
                type="button"
                @click="
                    modoAcademica = 'crear';
                    academicaId = null;
                    postgradoId = '';
                    gradoAcademicoId = '';
                    institucionAcademica = '';
                    numeroTitulo = '';
                    fechaTitulo = '';
                    numeroCertificado = '';
                    academicaModal = true;
                "
                class="inline-flex items-center justify-center
                       rounded-lg
                       bg-[#1a34c9]
                       px-4 py-2.5
                       text-sm font-semibold text-white
                       transition
                       hover:bg-[#1429a3]"
            >
                <span class="mr-1 text-lg">
                    +
                </span>

                Registrar información académica
            </button>
        </div>

        @if ($afiliado->afiliadosPostgrados->isEmpty())
            <div
                class="rounded-xl border border-gray-200
                       bg-gray-50 px-5 py-4"
            >
                <p class="m-0 text-sm text-gray-600">
                    Este afiliado no tiene información académica registrada.
                </p>
            </div>
        @else
            <div class="overflow-hidden rounded-xl border border-gray-200">
                <table class="w-full">
                    <thead class="bg-[#f1f3f8]">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">
                                Postgrado
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">
                                Grado académico
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">
                                Institución
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">
                                N.º título
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">
                                Fecha título
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">
                                N.º certificado
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">
                                Acciones
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @foreach ($afiliado->afiliadosPostgrados as $academica)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-4 text-sm font-medium text-gray-900">
                                   {{ $academica->postgrado->nombre }}
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-700">
                                    {{ $academica->gradoAcademico->nombre }}
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-700">
                                    {{ $academica->institucion }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">
                                    {{ $academica->numero_titulo }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">
                                    {{ $academica->fecha_titulo?->format('d/m/Y') }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">
                                    {{ $academica->numero_certificado }}
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-2">
                                        {{-- EDITAR --}}
                                        <button
                                            type="button"
                                            @click="
                                                modoAcademica = 'editar';
                                                academicaId = {{ $academica->id }};
                                                postgradoId = '{{ $academica->postgrado_id }}';
                                                gradoAcademicoId = '{{ $academica->grado_academico_id }}';
                                                institucionAcademica = @js($academica->institucion);
                                                numeroTitulo = @js($academica->numero_titulo);
                                                fechaTitulo = '{{ $academica->fecha_titulo?->format('Y-m-d') }}';
                                                numeroCertificado = @js($academica->numero_certificado);
                                                academicaModal = true;
                                            "
                                            class="rounded-lg
                                                   border border-gray-300
                                                   bg-white
                                                   px-3 py-2
                                                   text-xs font-semibold
                                                   text-gray-700
                                                   transition
                                                   hover:bg-gray-50
                                                   hover:text-[#1a34c9]"
                                        >
                                            Editar
                                        </button>

                                        {{-- ELIMINAR --}}
                                        <form
                                            method="POST"
                                            action="{{ route('afiliado-postgrado.destroy', $academica) }}"
                                            class="inline"
                                            onsubmit="return confirm(
                                                '¿Está seguro de eliminar esta información académica?'
                                            );"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="rounded-lg
                                                       border border-red-200
                                                       bg-white
                                                       px-3 py-2
                                                       text-xs font-semibold
                                                       text-red-600
                                                       transition
                                                       hover:bg-red-50"
                                            >
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- MODAL LABORAL --}}
    <template x-teleport="body">
        <div
            x-show="laboralModal"
            x-cloak
            x-transition.opacity.duration.200ms
            class="fixed inset-0 z-[10000] flex items-center justify-center
                   bg-gray-900/55 px-4 py-6
                   backdrop-blur-[3px]"
        >
            <div
                @click.outside="laboralModal = false"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="scale-95 opacity-0"
                x-transition:enter-end="scale-100 opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="scale-100 opacity-100"
                x-transition:leave-end="scale-95 opacity-0"
                class="w-full max-w-2xl
                       max-h-[90vh]
                       overflow-hidden
                       rounded-2xl
                       border border-gray-200
                       border-t-[4px] border-t-[#1a34c9]
                       bg-white
                       shadow-[0_25px_60px_rgba(0,0,0,0.25)]"
            >
                <div
                    class="flex items-center justify-between
                           border-b border-gray-200
                           px-8 py-6"
                >
                    <h2 class="m-0 text-2xl font-semibold text-gray-900">
                        <span x-show="modoLaboral === 'crear'">
                            Registrar información laboral
                        </span>
                        <span x-show="modoLaboral === 'editar'">
                            Editar información laboral
                        </span>
                    </h2>
                    <button
                        type="button"
                        @click="laboralModal = false"
                        class="text-3xl font-light text-gray-500
                               hover:text-gray-800"
                    >
                        ×
                    </button>
                </div>

                <div class="max-h-[calc(90vh-100px)] overflow-y-auto px-8 py-7">
                    <form
                        method="POST"
                        action="{{ route('informacion-laboral.store', $afiliado) }}"
                        x-bind:action="modoLaboral === 'editar'
                            ? '{{ $trabajoActivo ? route('informacion-laboral.update', $trabajoActivo) : '' }}'
                            : '{{ route('informacion-laboral.store', $afiliado) }}'"
                        class="space-y-5"
                    >
                    <input
                        type="hidden"
                        name="_method"
                        :value="modoLaboral === 'editar' ? 'PUT' : 'POST'"
                    >
                        @csrf
                        <div>
                            <label
                                for="institucion_{{ $afiliado->id }}"
                                class="mb-2 block text-sm font-medium text-gray-800"
                            >
                                Institución
                            </label>
                            <input
                                type="text"
                                id="institucion_{{ $afiliado->id }}"
                                name="institucion"
                                x-model="institucion"
                                maxlength="150"
                                required
                                class="w-full rounded-lg border border-gray-300
                                       px-4 py-3 text-sm outline-none
                                       focus:border-[#1a34c9]
                                       focus:ring-2
                                       focus:ring-[#1a34c9]/15"
                            >
                        </div>

                        <div>
                            <label
                                for="tipo_contrato_{{ $afiliado->id }}"
                                class="mb-2 block text-sm font-medium text-gray-800"
                            >
                                Tipo de contrato
                            </label>
                            <input
                                type="text"
                                id="tipo_contrato_{{ $afiliado->id }}"
                                name="tipo_contrato"
                                x-model="tipoContrato"
                                maxlength="100"
                                required
                                class="w-full rounded-lg border border-gray-300
                                       px-4 py-3 text-sm outline-none
                                       focus:border-[#1a34c9]
                                       focus:ring-2
                                       focus:ring-[#1a34c9]/15"
                            >
                        </div>

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label
                                    for="haber_basico_{{ $afiliado->id }}"
                                    class="mb-2 block text-sm font-medium text-gray-800"
                                >
                                    Haber básico
                                </label>
                                <input
                                    type="number"
                                    id="haber_basico_{{ $afiliado->id }}"
                                    name="haber_basico"
                                    x-model="haberBasico"
                                    min="0.01"
                                    step="0.01"
                                    required
                                    class="w-full rounded-lg border border-gray-300
                                           px-4 py-3 text-sm outline-none
                                           focus:border-[#1a34c9]
                                           focus:ring-2
                                           focus:ring-[#1a34c9]/15"
                                >
                            </div>

                            <div>
                                <label
                                    for="fecha_ingreso_trabajo_{{ $afiliado->id }}"
                                    class="mb-2 block text-sm font-medium text-gray-800"
                                >
                                    Fecha de ingreso
                                </label>
                                <input
                                    type="date"
                                    id="fecha_ingreso_trabajo_{{ $afiliado->id }}"
                                    name="fecha_ingreso_trabajo"
                                    x-model="fechaIngreso"
                                    required
                                    class="w-full rounded-lg border border-gray-300
                                           px-4 py-3 text-sm outline-none
                                           focus:border-[#1a34c9]
                                           focus:ring-2
                                           focus:ring-[#1a34c9]/15"
                                >
                            </div>
                        </div>

                        <div
                            x-show="modoLaboral === 'editar'"
                            x-cloak
                        >
                            <label
                                class="flex items-center gap-3 text-sm font-medium text-gray-800"
                            >
                                <input
                                    type="hidden"
                                    name="culminado"
                                    :value="culminado ? 1 : 0"
                                >
                                <input
                                    type="checkbox"
                                    x-model="culminado"
                                    class="h-4 w-4 rounded border-gray-300
                                           text-[#1a34c9]
                                           focus:ring-[#1a34c9]"
                                >

                                Trabajo culminado
                            </label>
                        </div>

                        <div
                            x-show="modoLaboral === 'editar' && culminado"
                            x-cloak
                        >

                            <label
                                for="fecha_retiro_{{ $afiliado->id }}"
                                class="mb-2 block text-sm font-medium text-gray-800"
                            >
                                Fecha de retiro
                            </label>

                            <input
                                type="date"
                                id="fecha_retiro_{{ $afiliado->id }}"
                                name="fecha_retiro"
                                x-model="fechaRetiro"
                                :required="modoLaboral === 'editar' && culminado"
                                class="w-full rounded-lg border border-gray-300
                                       px-4 py-3 text-sm outline-none
                                       focus:border-[#1a34c9]
                                       focus:ring-2
                                       focus:ring-[#1a34c9]/15"
                            >

                        </div>

                        <div
                            class="flex justify-end gap-3
                                   border-t border-gray-200
                                   pt-5"
                        >
                            <button
                                type="button"
                                @click="laboralModal = false"
                                class="rounded-lg
                                       border border-gray-300
                                       bg-white
                                       px-5 py-2.5
                                       text-sm font-semibold
                                       text-gray-700
                                       hover:bg-gray-50"
                            >
                                Cancelar
                            </button>

                            <button
                                type="submit"
                                class="rounded-lg
                                       bg-[#1a34c9]
                                       px-5 py-2.5
                                       text-sm font-semibold
                                       text-white
                                       hover:bg-[#1429a3]"
                            >

                                <span x-show="modoLaboral === 'crear'">
                                    Registrar
                                </span>

                                <span x-show="modoLaboral === 'editar'">
                                    Guardar cambios
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>


    {{-- ====================================================== --}}
    {{-- MODAL ACADÉMICO --}}
    {{-- ====================================================== --}}

    <template x-teleport="body">

        <div
            x-show="academicaModal"
            x-cloak
            x-transition.opacity.duration.200ms
            class="fixed inset-0 z-[10000] flex items-center justify-center
                   bg-gray-900/55 px-4 py-6
                   backdrop-blur-[3px]"
        >

            <div
                @click.outside="academicaModal = false"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="scale-95 opacity-0"
                x-transition:enter-end="scale-100 opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="scale-100 opacity-100"
                x-transition:leave-end="scale-95 opacity-0"
                class="w-full max-w-2xl
                       max-h-[90vh]
                       overflow-hidden
                       rounded-2xl
                       border border-gray-200
                       border-t-[4px] border-t-[#1a34c9]
                       bg-white
                       shadow-[0_25px_60px_rgba(0,0,0,0.25)]"
            >

                <div
                    class="flex items-center justify-between
                           border-b border-gray-200
                           px-8 py-6"
                >

                    <h2
                        class="m-0 text-2xl font-semibold text-gray-900"
                        x-text="
                            modoAcademica === 'crear'
                                ? 'Registrar información académica'
                                : 'Editar información académica'
                        "
                    ></h2>

                    <button
                        type="button"
                        @click="academicaModal = false"
                        class="text-3xl font-light text-gray-500
                               hover:text-gray-800"
                    >
                        ×
                    </button>

                </div>


                <div class="max-h-[calc(90vh-100px)] overflow-y-auto px-8 py-7">

                    <form
                        method="POST"
                        :action="
                            modoAcademica === 'crear'
                            ? '{{ route('afiliado-postgrado.store', $afiliado) }}'
                            : '/afiliado-postgrado/' + academicaId
                        "
                        class="space-y-5"
                    >

                        @csrf

                        <template x-if="modoAcademica === 'editar'">

                            <input
                                type="hidden"
                                name="_method"
                                value="PUT"
                            >

                        </template>


                        <div>

                            <label
                                for="postgrado_id_{{ $afiliado->id }}"
                                class="mb-2 block text-sm font-medium text-gray-800"
                            >
                                Postgrado
                            </label>

                            <select
                                id="postgrado_id_{{ $afiliado->id }}"
                                name="postgrado_id"
                                x-model="postgradoId"
                                required
                                class="w-full rounded-lg border border-gray-300
                                       bg-white px-4 py-3 text-sm
                                       outline-none
                                       focus:border-[#1a34c9]
                                       focus:ring-2
                                       focus:ring-[#1a34c9]/15"
                            >

                                <option value="">
                                    Seleccione un postgrado
                                </option>

                                @foreach ($postgrados as $postgrado)

                                    <option value="{{ $postgrado->id }}">
                                        {{ $postgrado->nombre }}
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        <div>

                            <label
                                for="grado_academico_id_{{ $afiliado->id }}"
                                class="mb-2 block text-sm font-medium text-gray-800"
                            >
                                Grado académico
                            </label>

                            <select
                                id="grado_academico_id_{{ $afiliado->id }}"
                                name="grado_academico_id"
                                x-model="gradoAcademicoId"
                                required
                                class="w-full rounded-lg border border-gray-300
                                       bg-white px-4 py-3 text-sm
                                       outline-none
                                       focus:border-[#1a34c9]
                                       focus:ring-2
                                       focus:ring-[#1a34c9]/15"
                            >

                                <option value="">
                                    Seleccione un grado académico
                                </option>

                                @foreach ($gradosAcademicos as $grado)

                                    <option value="{{ $grado->id }}">
                                        {{ $grado->nombre }}
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        <div>

                            <label
                                for="institucion_academica_{{ $afiliado->id }}"
                                class="mb-2 block text-sm font-medium text-gray-800"
                            >
                                Institución
                            </label>

                            <input
                                type="text"
                                id="institucion_academica_{{ $afiliado->id }}"
                                name="institucion"
                                x-model="institucionAcademica"
                                maxlength="150"
                                required
                                class="w-full rounded-lg border border-gray-300
                                       px-4 py-3 text-sm outline-none
                                       focus:border-[#1a34c9]
                                       focus:ring-2
                                       focus:ring-[#1a34c9]/15"
                            >

                        </div>


                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                            <div>

                                <label
                                    for="numero_titulo_{{ $afiliado->id }}"
                                    class="mb-2 block text-sm font-medium text-gray-800"
                                >
                                    Número de título
                                </label>

                                <input
                                    type="text"
                                    id="numero_titulo_{{ $afiliado->id }}"
                                    name="numero_titulo"
                                    x-model="numeroTitulo"
                                    maxlength="50"
                                    required
                                    class="w-full rounded-lg border border-gray-300
                                           px-4 py-3 text-sm outline-none
                                           focus:border-[#1a34c9]
                                           focus:ring-2
                                           focus:ring-[#1a34c9]/15"
                                >

                            </div>


                            <div>

                                <label
                                    for="fecha_titulo_{{ $afiliado->id }}"
                                    class="mb-2 block text-sm font-medium text-gray-800"
                                >
                                    Fecha de título
                                </label>

                                <input
                                    type="date"
                                    id="fecha_titulo_{{ $afiliado->id }}"
                                    name="fecha_titulo"
                                    x-model="fechaTitulo"
                                    required
                                    class="w-full rounded-lg border border-gray-300
                                           px-4 py-3 text-sm outline-none
                                           focus:border-[#1a34c9]
                                           focus:ring-2
                                           focus:ring-[#1a34c9]/15"
                                >

                            </div>

                        </div>


                        <div>

                            <label
                                for="numero_certificado_{{ $afiliado->id }}"
                                class="mb-2 block text-sm font-medium text-gray-800"
                            >
                                Número de certificado
                            </label>

                            <input
                                type="text"
                                id="numero_certificado_{{ $afiliado->id }}"
                                name="numero_certificado"
                                x-model="numeroCertificado"
                                maxlength="50"
                                required
                                class="w-full rounded-lg border border-gray-300
                                       px-4 py-3 text-sm outline-none
                                       focus:border-[#1a34c9]
                                       focus:ring-2
                                       focus:ring-[#1a34c9]/15"
                            >

                        </div>


                        <div
                            class="flex justify-end gap-3
                                   border-t border-gray-200
                                   pt-5"
                        >

                            <button
                                type="button"
                                @click="academicaModal = false"
                                class="rounded-lg
                                       border border-gray-300
                                       bg-white
                                       px-5 py-2.5
                                       text-sm font-semibold
                                       text-gray-700
                                       hover:bg-gray-50"
                            >
                                Cancelar
                            </button>

                            <button
                                type="submit"
                                class="rounded-lg
                                       bg-[#1a34c9]
                                       px-5 py-2.5
                                       text-sm font-semibold
                                       text-white
                                       hover:bg-[#1429a3]"
                            >

                                <span
                                    x-text="
                                        modoAcademica === 'crear'
                                            ? 'Registrar'
                                            : 'Guardar cambios'
                                    "
                                ></span>

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </template>

</div>