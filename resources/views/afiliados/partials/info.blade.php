<div>
    <h3>Datos del afiliado</h3>
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
    <p>
        <strong>Creado en:</strong>
        {{ $afiliado->creado_en?->format('d/m/Y H:i') }}
    </p>
    <p>
        <strong>Actualizado en:</strong>
        {{ $afiliado->actualizado_en?->format('d/m/Y H:i') }}
    </p>
</div>