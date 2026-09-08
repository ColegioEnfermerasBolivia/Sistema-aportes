@extends('layouts.app')
@section('title', 'Usuarios')
@section('content')

<div class="min-h-screen bg-[#f7f7f9] px-6 py-8 lg:px-12">

    {{-- ENCABEZADO --}}
    <div class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="m-0 text-3xl font-semibold tracking-tight text-gray-900">
                Gestión de Usuarios
            </h1>

            <p class="mt-2 text-base text-gray-600">
                Administre los accesos y roles de los usuarios a nivel nacional.
            </p>
        </div>

        {{-- BOTÓN AGREGAR USUARIO --}}
        <x-modal titulo="Crear usuario">
            <x-slot:trigger>
                <button
                    type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-full
                           bg-[#1a34c9] px-7 py-3
                           text-sm font-semibold text-white
                           shadow-[0_5px_14px_rgba(26,52,201,0.25)]
                           transition
                           hover:bg-[#1429a3]
                           focus:outline-none
                           focus:ring-2
                           focus:ring-[#1a34c9]
                           focus:ring-offset-2"
                >
                    <span class="text-xl leading-none">
                        +
                    </span>

                    Agregar usuario
                </button>
            </x-slot:trigger>
            @include('usuarios.partials.form')
        </x-modal>
    </div>

    {{-- MENSAJE DE USUARIO CREADO --}}
    @if (session('usuario_creado'))
        <x-modal
            titulo="Usuario creado correctamente"
            :abiertoInicial="true"
        >
            <div class="space-y-4">
                <p class="text-sm text-gray-700">
                    El usuario fue creado correctamente.
                </p>
                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-sm text-gray-700">
                        <strong>Nombre de usuario:</strong>
                        {{ session('usuario_creado.username') }}
                    </p>

                    <p class="mt-2 text-sm text-gray-700">
                        <strong>Contraseña temporal:</strong>
                        {{ session('usuario_creado.password_temporal') }}
                    </p>
                </div>
                <p class="text-sm text-gray-600">
                    El usuario deberá cambiar esta contraseña
                    al iniciar sesión por primera vez.
                </p>
            </div>
        </x-modal>
    @endif

    {{-- MENSAJE DE CONTRASEÑA RESTABLECIDA --}}
    @if (session('password_restablecida'))

        <x-modal
            titulo="Contraseña restablecida"
            :abiertoInicial="true"
        >
            <div class="space-y-4">
                <p class="text-sm text-gray-700">
                    La contraseña fue restablecida correctamente.
                </p>
                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-sm text-gray-700">
                        <strong>Nombre de usuario:</strong>
                        {{ session('password_restablecida.username') }}
                    </p>
                    <p class="mt-2 text-sm text-gray-700">
                        <strong>Nueva contraseña temporal:</strong>
                        {{ session('password_restablecida.password_temporal') }}
                    </p>
                </div>
                <p class="text-sm text-gray-600">
                    El usuario deberá cambiar esta contraseña
                    al iniciar sesión nuevamente.
                </p>
            </div>
        </x-modal>
    @endif

    {{-- TABLA DE USUARIOS --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                {{-- CABECERA --}}
                <thead class="border-b border-gray-200 bg-[#f1f3f8]">
                    <tr>
                        <th
                            class="px-6 py-5 text-left text-sm font-semibold text-gray-800"
                        >
                            Usuario
                        </th>
                        <th
                            class="px-6 py-5 text-left text-sm font-semibold text-gray-800"
                        >
                            CI
                        </th>
                        <th
                            class="px-6 py-5 text-left text-sm font-semibold text-gray-800"
                        >
                            Colegio
                        </th>
                        <th
                            class="px-6 py-5 text-left text-sm font-semibold text-gray-800"
                        >
                            Rol
                        </th>
                        <th
                            class="px-6 py-5 text-left text-sm font-semibold text-gray-800"
                        >
                            Estado
                        </th>
                        <th
                            class="px-6 py-5 text-left text-sm font-semibold text-gray-800"
                        >
                            Acciones
                        </th>
                    </tr>
                </thead>

                {{-- CUERPO --}}
                <tbody class="divide-y divide-gray-100">
                    @forelse ($usuarios as $usuario)
                        <tr class="transition hover:bg-gray-50">
                            {{-- USUARIO --}}
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-3">
                                    {{-- Iniciales --}}
                                    <div
                                        class="flex h-11 w-11 shrink-0 items-center justify-center
                                               rounded-full
                                               bg-[#e8ecff]
                                               text-sm font-bold
                                               text-[#1a34c9]"
                                    >
                                        {{ mb_strtoupper(
                                            mb_substr($usuario->nombres, 0, 1, 'UTF-8') .
                                            mb_substr($usuario->apellidos, 0, 1, 'UTF-8'),
                                            'UTF-8'
                                        ) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-gray-900">
                                            {{ $usuario->nombres }}
                                            {{ $usuario->apellidos }}
                                        </p>
                                        <p class="mt-1 text-xs text-gray-500">
                                            {{ $usuario->username }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            {{-- CI --}}
                            <td class="whitespace-nowrap px-6 py-5 text-sm text-gray-600">
                                {{ $usuario->ci }}
                            </td>

                            {{-- COLEGIO --}}
                            <td class="px-6 py-5 text-sm text-gray-700">
                                {{ $usuario->colegio?->nombre ?? 'Sin colegio' }}
                            </td>

                            {{-- ROL --}}
                            <td class="px-6 py-5">
                                <span
                                    class="inline-flex items-center rounded-full
                                           bg-gray-100 px-3 py-1
                                           text-xs font-medium text-gray-700"
                                >
                                    {{ $usuario->rol->nombre }}
                                </span>
                            </td>

                            {{-- ESTADO --}}
                            <td class="px-6 py-5">
                                @if ($usuario->estado === 'ACTIVO')
                                    <span
                                        class="inline-flex items-center rounded-full
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
                                        class="inline-flex items-center rounded-full
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

                            {{-- ACCIONES --}}
                            <td class="px-6 py-5">
                                <div class="flex flex-wrap items-center gap-2">
                                    {{-- EDITAR --}}
                                    <x-modal titulo="Editar usuario">
                                        <x-slot:trigger>
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-lg
                                                       border border-gray-300
                                                       bg-white px-3 py-2
                                                       text-xs font-semibold
                                                       text-gray-700
                                                       transition
                                                       hover:bg-gray-50
                                                       hover:text-[#1a34c9]"
                                            >
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
                                            class="inline"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <button
                                                type="submit"
                                                class="inline-flex items-center rounded-lg
                                                       border border-gray-300
                                                       bg-white px-3 py-2
                                                       text-xs font-semibold
                                                       text-gray-700
                                                       transition
                                                       hover:bg-gray-50"
                                            >
                                                {{ $usuario->estado === 'ACTIVO'
                                                    ? 'Desactivar'
                                                    : 'Activar'
                                                }}
                                            </button>
                                        </form>
                                    @endif

                                    {{-- RESET PASSWORD --}}
                                    @if (auth()->id() !== $usuario->id)
                                        <form
                                            method="POST"
                                            action="{{ route('usuarios.reset-password', $usuario) }}"
                                            class="inline"
                                            onsubmit="return confirm('¿Está seguro de resetear la contraseña de este usuario?');"
                                        >
                                            @csrf
                                            @method('PATCH')
                                            <button
                                                type="submit"
                                                class="inline-flex items-center rounded-lg
                                                       bg-[#f7f7f9] px-3 py-2
                                                       text-xs font-semibold
                                                       text-[#1a34c9]
                                                       transition
                                                       hover:bg-[#e9edff]"
                                            >
                                                Resetear contraseña
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="6"
                                class="px-6 py-12 text-center"
                            >

                                <p class="text-sm font-medium text-gray-600">
                                    No existen usuarios registrados.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection