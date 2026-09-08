<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar contraseña</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans">

    <div class="flex min-h-screen items-center justify-center bg-gray-50 px-6 py-12">
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

            {{-- Aviso de seguridad --}}
            <div class="mb-5 flex items-start gap-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                <span class="mt-0.5">🛡</span>
                <p>Por seguridad, debes cambiar tu contraseña temporal antes de continuar.</p>
            </div>

            {{-- Errores de validación --}}
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

            <form method="POST" action="/cambiar-password" class="space-y-5">
                @csrf

                {{-- Contraseña actual --}}
                <div>
                    <label for="password_actual" class="mb-1 block text-sm font-semibold text-gray-700">
                        Contraseña actual
                    </label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400">
                            🔒
                        </span>
                        <input
                            type="password"
                            id="password_actual"
                            name="password_actual"
                            placeholder="••••••••"
                            required
                            class="w-full rounded-lg border border-gray-300 bg-gray-50 py-3 pl-10 pr-4 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600/20"
                        >
                    </div>
                </div>

                {{-- Nueva contraseña --}}
                <div>
                    <label for="password" class="mb-1 block text-sm font-semibold text-gray-700">
                        Nueva contraseña
                    </label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400">
                            🔑
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
                    <p class="mt-1 text-xs text-gray-500">Mínimo 8 caracteres, incluye letras y números.</p>
                </div>

                {{-- Confirmar contraseña --}}
                <div>
                    <label for="password_confirmation" class="mb-1 block text-sm font-semibold text-gray-700">
                        Confirmar nueva contraseña
                    </label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400">
                            🔑
                        </span>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            placeholder="••••••••"
                            required
                            class="w-full rounded-lg border border-gray-300 bg-gray-50 py-3 pl-10 pr-4 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600/20"
                        >
                    </div>
                </div>

                {{-- Botón --}}
                <button
                    type="submit"
                    class="flex w-full items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-blue-700 to-blue-500 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/30 transition hover:from-blue-800 hover:to-blue-600"
                >
                    Cambiar contraseña →
                </button>

            </form>

        </div>
    </div>

</body>
</html>