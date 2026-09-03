<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
</head>
<body>

    <h1>Dashboard</h1>

    <p>
        Bienvenido,
        {{ $usuario->nombres }} {{ $usuario->apellidos }}
    </p>

    <p>
        Rol: {{ $usuario->rol->nombre }}
    </p>

    @if ($esAdministrador)

        <p>
            Tienes acceso a todos los colegios.
        </p>

    @else

        <p>
            Colegio: {{ $usuario->colegio->nombre }}
        </p>

        <p>
            Solo puedes consultar información de los afiliados
            pertenecientes a tu colegio.
        </p>

    @endif

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Cerrar sesión</button>
    </form>

</body>
</html>