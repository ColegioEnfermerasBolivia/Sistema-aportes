<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios</title>
    <script
        defer
        src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js">
    </script>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>
<body>

    <h1>Usuarios</h1>
        <x-modal titulo="Crear usuario">
        <x-slot:trigger>
            <button type="button">
                + Agregar usuario
            </button>
        </x-slot:trigger>
        @include('usuarios.partials.form')
    </x-modal>

    @if (session('usuario_creado'))

        <x-modal
            titulo="Usuario creado correctamente"
            :abiertoInicial="true"
        >

            <div>
                <p>
                    El usuario fue creado correctamente.
                </p>

                <p>
                    <strong>Nombre de usuario:</strong>
                    {{ session('usuario_creado.username') }}
                </p>

                <p>
                    <strong>Contraseña temporal:</strong>
                    {{ session('usuario_creado.password_temporal') }}
                </p>

                <p>
                    El usuario deberá cambiar esta contraseña
                    al iniciar sesión por primera vez.
                </p>
            </div>

        </x-modal>

        @endif

        @if (session('password_restablecida'))

        <x-modal
            titulo="Contraseña restablecida"
            :abiertoInicial="true"
        >

            <div>

                <p>
                    La contraseña fue restablecida correctamente.
                </p>

                <p>
                    <strong>Nombre de usuario:</strong>
                    {{ session('password_restablecida.username') }}
                </p>

                <p>
                    <strong>Nueva contraseña temporal:</strong>
                    {{ session('password_restablecida.password_temporal') }}
                </p>

                <p>
                    El usuario deberá cambiar esta contraseña
                    al iniciar sesión nuevamente.
                </p>

            </div>

        </x-modal>

    @endif

    <table border="1">
        <thead>
            <tr>
                <th>CI</th>
                <th>Nombres</th>
                <th>Apellidos</th>
                <th>Usuario</th>
                <th>Rol</th>
                <th>Colegio</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($usuarios as $usuario)
                <tr>
                    <td>{{ $usuario->ci }}</td>
                    <td>{{ $usuario->nombres }}</td>
                    <td>{{ $usuario->apellidos }}</td>
                    <td>{{ $usuario->username }}</td>
                    <td>{{ $usuario->rol->nombre }}</td>
                    <td>{{ $usuario->colegio?->nombre ?? 'Sin colegio' }}</td>
                    <td>{{ $usuario->estado }}</td>
                    <td>
                        <x-modal titulo="Editar usuario">

                            <x-slot:trigger>
                                <button type="button">
                                    Editar
                                </button>
                            </x-slot:trigger>

                            @include('usuarios.partials.edit-form', [
                                'usuario' => $usuario
                            ])

                        </x-modal>


                        {{-- ACTIVAR / DESACTIVAR --}}

                        @if (auth()->id() !== $usuario->id)

                            <form
                                method="POST"
                                action="{{ route('usuarios.estado', $usuario) }}"
                                style="display: inline;"
                            >
                                @csrf
                                @method('PATCH')

                                <button type="submit">
                                    {{ $usuario->estado === 'ACTIVO' ? 'Desactivar' : 'Activar' }}
                                </button>
                            </form>

                        @endif
                        {{-- RESET PASSWORD --}}

                        @if (auth()->id() !== $usuario->id)

                            <form
                                method="POST"
                                action="{{ route('usuarios.reset-password', $usuario) }}"
                                style="display: inline;"
                                onsubmit="return confirm('¿Está seguro de resetear la contraseña de este usuario?');"
                            >
                                @csrf
                                @method('PATCH')
                                <button type="submit">
                                    Resetear contraseña
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>