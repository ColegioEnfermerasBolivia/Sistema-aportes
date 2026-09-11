<!DOCTYPE html>
<html lang="es">
<head>

<meta charset="UTF-8">
<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>
<title>
    @yield('title', 'Sistema de Aportes')
</title>
@vite(['resources/css/app.css', 'resources/js/app.js'])
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
    <body class="min-h-screen bg-gray-100">
        <div class="flex min-h-screen">
            {{-- Barra lateral --}}
            <x-navbar />
            {{-- Contenido --}}
            <main class="min-w-0 flex-1">
                @yield('content')
            </main>
        </div>
        @stack('scripts')
    </body>
</html>
