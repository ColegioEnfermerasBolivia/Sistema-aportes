<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Iniciar sesión</title>
    </head>
    <body>

        <h1>Iniciar sesión</h1>

        @if ($errors->any())
            <div>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="/login">

            @csrf

            <div>
                <label for="username">Usuario:</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    required
                >
            </div>

            <div>
                <label for="password">Contraseña:</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                >
            </div>

            <button type="submit">Iniciar sesión</button>

        </form>

    </body>
</html>