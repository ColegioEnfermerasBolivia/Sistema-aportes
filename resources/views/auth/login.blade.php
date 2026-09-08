<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans">

    <div class="flex min-h-screen">

        {{-- Columna izquierda: imagen --}}
        <div class="relative hidden w-1/2 lg:block">
            <img
                src="{{ asset('images/enfermera.webp') }}"
                alt="Sistema de Aportes"
                class="h-full w-full object-cover"
            >

            {{-- Overlay para oscurecer un poco la imagen --}}
            <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-black/0 to-black/0"></div>

            {{-- Tarjeta de bienvenida --}}
            <div class="absolute bottom-10 left-10 right-10 rounded-2xl border-l-4 border-blue-800 bg-white/90 p-6 backdrop-blur-sm">
                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-blue-800 text-white">
                    🛡
                </div>

                <h2 class="text-2xl font-extrabold leading-tight text-gray-900">
                    Bienvenidos al Nuevo
                    <span class="block text-blue-800">Sistema de Aportes</span>
                </h2>

                <p class="mt-2 text-sm text-gray-700">
                    Innovación para el cuidado de quienes cuidan. Una plataforma precisa, empática y progresiva.
                </p>
            </div>
        </div>

        {{-- Columna derecha: formulario --}}
        <div class="flex w-full items-center justify-center bg-gray-50 px-6 py-12 lg:w-1/2">
            <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl">

                {{-- Logo --}}
                <div class="mb-6 text-center">
                    <img
                        src="{{ asset('images/logo.png') }}"
                        alt="CNEB"
                        class="mx-auto mb-3 h-20 w-20"
                    >
                    <h1 class="text-2xl font-extrabold text-blue-900">ColEnf Bolivia</h1>
                    <p class="text-sm text-gray-600">Portal de Gestión Profesional</p>
                </div>

                {{-- Aviso de credenciales incorrectas --}}
                @if ($errors->any())
                    <div class="mb-5 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <span class="mt-0.5">⚠</span>
                        <div>
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form method="POST" action="/login" class="space-y-5">
                    @csrf

                    {{-- Usuario --}}
                    <div>
                        <label for="username" class="mb-1 block text-sm font-semibold text-gray-700">
                            Usuario
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400">
                                🪪
                            </span>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                placeholder="usuario"
                                required
                                class="w-full rounded-lg border border-gray-300 bg-gray-50 py-3 pl-10 pr-4 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600/20"
                            >
                        </div>
                    </div>

                    {{-- Contraseña --}}
                    <div>
                        <label for="password" class="mb-1 block text-sm font-semibold text-gray-700">
                            Contraseña
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400">
                                🔒
                            </span>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="••••••••"
                                required
                                class="w-full rounded-lg border border-gray-300 bg-gray-50 py-3 pl-10 pr-4 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600/20"
                            >
                        </div>
                    </div>

                    {{-- Recordar datos / Olvidaste tu contraseña --}}
                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center gap-2 text-gray-600">
                            <input
                                type="checkbox"
                                name="remember"
                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-600"
                            >
                            Recordar datos
                        </label>

                        <a href="#" class="font-medium text-blue-700 hover:underline">
                            ¿Olvidaste tu contraseña?
                        </a>
                    </div>

                    {{-- Botón --}}
                    <button
                        type="submit"
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-blue-700 to-blue-500 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/30 transition hover:from-blue-800 hover:to-blue-600"
                    >
                        Ingresar al Portal →
                    </button>

                </form>

            </div>
        </div>

    </div>

</body>
</html>