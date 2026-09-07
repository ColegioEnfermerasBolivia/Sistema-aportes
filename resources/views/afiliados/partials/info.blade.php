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
```
<h3>Datos del afiliado</h3>
```
```
<p>
    <strong>Registro departamental:</strong>
    {{ $afiliado->registro_departamental }}
</p>
<p>
    <strong>Matrícula profesional:</strong>
    {{ $afiliado->matricula_profesional }}
</p>
<p>
    <strong>CI:</strong>
    {{ $afiliado->ci }}
</p>
<p>
    <strong>Nombres:</strong>
    {{ $afiliado->nombres }}
</p>
<p>
    <strong>Apellidos:</strong>
    {{ $afiliado->apellidos }}
</p>
<p>
    <strong>Colegio:</strong>
    {{ $afiliado->colegio?->nombre ?? 'Sin colegio' }}
</p>
<p>
    <strong>Celular:</strong>
    {{ $afiliado->celular }}
</p>
<p>
    <strong>Estado:</strong>
    {{ $afiliado->estado }}
</p>
<p>
    <strong>Fecha de afiliación:</strong>
    {{ $afiliado->fecha_afiliacion?->format('d/m/Y') }}
</p>
<hr>
@php
    $trabajoActivo = $afiliado->informacionesLaborales
        ->firstWhere('culminado', false);

    $historialLaboral = $afiliado->informacionesLaborales
        ->where('culminado', true);
@endphp
<div>
    <h3>Información laboral</h3>

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
        >
    + Registrar trabajo
</button>
    @endif
</div>
@if ($trabajoActivo)
    <h4>Trabajo actual</h4>
    <div>
        <p>
            <strong>Institución:</strong>
            {{ $trabajoActivo->institucion }}
        </p>
        <p>
            <strong>Tipo de contrato:</strong>
            {{ $trabajoActivo->tipo_contrato }}
        </p>
        <p>
            <strong>Haber básico:</strong>
            Bs {{ number_format($trabajoActivo->haber_basico, 2, ',', '.') }}
        </p>
        <p>
            <strong>Aporte referencial:</strong>
            Bs {{ number_format($trabajoActivo->monto_aporte, 2, ',', '.') }}
        </p>
        <p>
            <strong>Fecha de ingreso:</strong>
            {{ $trabajoActivo->fecha_ingreso_trabajo?->format('d/m/Y') }}
        </p>
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
        >
            Editar trabajo
        </button>
    </div>
@else
    <p>
        Este afiliado no tiene un trabajo activo.
    </p>
@endif

@if ($historialLaboral->isNotEmpty())
    <hr>
    <h4>Historial laboral</h4>
    @foreach ($historialLaboral as $trabajo)
        <div>
            <p>
                <strong>Institución:</strong>
                {{ $trabajo->institucion }}
            </p>
            <p>
                <strong>Tipo de contrato:</strong>
                {{ $trabajo->tipo_contrato }}
            </p>
            <p>
                <strong>Haber básico:</strong>
                Bs {{ number_format($trabajo->haber_basico, 2, ',', '.') }}
            </p>
            <p>
                <strong>Aporte referencial:</strong>
                Bs {{ number_format($trabajo->monto_aporte, 2, ',', '.') }}
            </p>
            <p>
                <strong>Fecha de ingreso:</strong>
                {{ $trabajo->fecha_ingreso_trabajo?->format('d/m/Y') }}
            </p>
            <p>
                <strong>Fecha de retiro:</strong>
                {{ $trabajo->fecha_retiro?->format('d/m/Y') }}
            </p>
        </div>
        @if (!$loop->last)
            <hr>
        @endif
    @endforeach
@endif
{{-- Información académica --}}
<hr>
<h3>Información académica</h3>
@if ($afiliado->afiliadosPostgrados->isEmpty())

```
<p>
    Este afiliado no tiene información académica registrada.
</p>
```
@else

```
@foreach ($afiliado->afiliadosPostgrados as $academica)

    <div>

        <p>
            <strong>Postgrado:</strong>
            {{ $academica->postgrado->nombre }}
        </p>

        <p>
            <strong>Grado académico:</strong>
            {{ $academica->gradoAcademico->nombre }}
        </p>

        <p>
            <strong>Institución:</strong>
            {{ $academica->institucion }}
        </p>

        <p>
            <strong>Número de título:</strong>
            {{ $academica->numero_titulo }}
        </p>

        <p>
            <strong>Fecha de título:</strong>
            {{ $academica->fecha_titulo?->format('d/m/Y') }}
        </p>

        <p>
            <strong>Número de certificado:</strong>
            {{ $academica->numero_certificado }}
        </p>

        <div>

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
            >
                Editar
            </button>

            <form
                method="POST"
                action="{{ route('afiliado-postgrado.destroy', $academica) }}"
                style="display: inline;"
                onsubmit="return confirm('¿Está seguro de eliminar esta información académica?');"
            >
                @csrf
                @method('DELETE')

                <button type="submit">
                    Eliminar
                </button>
            </form>

        </div>

    </div>

    @if (!$loop->last)
        <hr>
    @endif

@endforeach
```
@endif
<div>
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
    >
        + Registrar información académica
    </button>
</div>

{{-- Modal para registrar información académica --}}
<div
    x-show="academicaModal"
    x-cloak
    class="modal-overlay"
>
    <div
        @click.outside="academicaModal = false"
        class="modal"
    >
        <div class="modal-header">
            <h2
                x-text="
                    modoAcademica === 'crear'
                        ? 'Registrar información académica'
                        : 'Editar información académica'
                "
            ></h2>
            <button
                type="button"
                @click="academicaModal = false"
            >
                ×
            </button>
        </div>
        <div class="modal-body">
            <form
                method="POST"
                :action="
                    modoAcademica === 'crear'
                    ? '{{ route('afiliado-postgrado.store', $afiliado) }}'
                    : '/afiliado-postgrado/' + academicaId
                "
            >
                @csrf

                <template x-if="modoAcademica === 'editar'">
                    @method('PUT')
                </template>
                <div>
                    <label for="postgrado_id">
                        Postgrado
                    </label>

                    <select
                        id="postgrado_id"
                        name="postgrado_id"
                        x-model="postgradoId"
                        required
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
                    <label for="grado_academico_id">
                        Grado académico
                    </label>

                    <select
                        id="grado_academico_id"
                        name="grado_academico_id"
                        x-model="gradoAcademicoId"
                        required
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
                    <label for="institucion_academica">
                        Institución
                    </label>

                    <input
                        type="text"
                        id="institucion_academica"
                        name="institucion"
                        x-model="institucionAcademica"
                        maxlength="150"
                        required
                    >
                </div>

                <div>
                    <label for="numero_titulo">
                        Número de título
                    </label>

                    <input
                        type="text"
                        id="numero_titulo"
                        name="numero_titulo"
                        x-model="numeroTitulo"
                        maxlength="50"
                        required
                    >
                </div>

                <div>
                    <label for="fecha_titulo">
                        Fecha de título
                    </label>

                    <input
                        type="date"
                        id="fecha_titulo"
                        name="fecha_titulo"
                        x-model="fechaTitulo"
                        required
                    >
                </div>

                <div>
                    <label for="numero_certificado">
                        Número de certificado
                    </label>

                    <input
                        type="text"
                        id="numero_certificado"
                        name="numero_certificado"
                        x-model="numeroCertificado"
                        maxlength="50"
                        required
                    >
                </div>

                <div>

                    <button
                        type="button"
                        @click="academicaModal = false"
                    >
                        Cancelar
                    </button>

                    <button type="submit">
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

{{-- Modal para registrar / editar información laboral --}}
<div
    x-show="laboralModal"
    x-cloak
    class="modal-overlay"
>
    <div
        @click.outside="laboralModal = false"
        class="modal"
    >
        <div class="modal-header">
            <h2>
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
            >
                ×
            </button>
        </div>
        <div class="modal-body">
            <form
                method="POST"
                action="{{ route('informacion-laboral.store', $afiliado) }}"
                x-bind:action="modoLaboral === 'editar'
                    ? '{{ $trabajoActivo ? route('informacion-laboral.update', $trabajoActivo) : '' }}'
                    : '{{ route('informacion-laboral.store', $afiliado) }}'"
            >
                @csrf

                <template x-if="modoLaboral === 'editar'">
                    <input
                        type="hidden"
                        name="_method"
                        value="PUT"
                    >
                </template>
                <div>
                    <label for="institucion">
                        Institución
                    </label>

                    <input
                        type="text"
                        id="institucion"
                        name="institucion"
                        x-model="institucion"
                        maxlength="150"
                        required
                    >
                </div>
                <div>
                    <label for="tipo_contrato">
                        Tipo de contrato
                    </label>

                    <input
                        type="text"
                        id="tipo_contrato"
                        name="tipo_contrato"
                        x-model="tipoContrato"
                        maxlength="100"
                        required
                    >
                </div>
                <div>
                    <label for="haber_basico">
                        Haber básico
                    </label>

                    <input
                        type="number"
                        id="haber_basico"
                        name="haber_basico"
                        x-model="haberBasico"
                        min="0.01"
                        step="0.01"
                        required
                    >
                </div>
                <div>
                    <label for="fecha_ingreso_trabajo">
                        Fecha de ingreso
                    </label>
                    <input
                        type="date"
                        id="fecha_ingreso_trabajo"
                        name="fecha_ingreso_trabajo"
                        x-model="fechaIngreso"
                        required
                    >
                </div>
                <div x-show="modoLaboral === 'editar'">
                    <label>
                        <input
                            type="hidden"
                            name="culminado"
                            :value="culminado ? 1 : 0"
                        >
                        <input
                            type="checkbox"
                            x-model="culminado"
                        >
                        Trabajo culminado
                    </label>
                </div>

                <div
                    x-show="modoLaboral === 'editar' && culminado"
                >
                    <label for="fecha_retiro">
                        Fecha de retiro
                    </label>

                    <input
                        type="date"
                        id="fecha_retiro"
                        name="fecha_retiro"
                        x-model="fechaRetiro"
                        :required="modoLaboral === 'editar' && culminado"
                    >
                </div>
                <div>

                    <button
                        type="button"
                        @click="laboralModal = false"
                    >
                        Cancelar
                    </button>

                    <button type="submit">
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
```
</div>
