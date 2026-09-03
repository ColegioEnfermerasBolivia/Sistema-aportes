<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar contraseña</title>
</head>
<body>

    <h1>Cambiar contraseña</h1>

    <p>Por seguridad, debes cambiar tu contraseña antes de continuar.</p>

    @if ($errors->any())
        <div>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="/cambiar-password">

        @csrf

        <div>
            <label for="password_actual">Contraseña actual:</label>
            <input
                type="password"
                id="password_actual"
                name="password_actual"
                required
            >
        </div>

        <div>
            <label for="password">Nueva contraseña:</label>
            <input
                type="password"
                id="password"
                name="password"
                required
            >
        </div>

        <div>
            <label for="password_confirmation">Confirmar nueva contraseña:</label>
            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                required
            >
        </div>

        <button type="submit">Cambiar contraseña</button>

    </form>

</body>
</html>