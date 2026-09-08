@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')

<header class="h-[70px] bg-white border-b border-gray-200 flex items-center justify-between px-[25px]">
    <div>
        <h1 class="m-0 text-xl text-gray-800">Sistema de Gestion</h1>
        @if ($esAdministrador)
            <p class="mt-0.5 text-xs text-gray-500">Tienes acceso a todos los colegios.</p>
        @else
            <p class="mt-0.5 text-xs text-gray-500">Colegio: {{ $usuario->colegio->nombre }}</p>
        @endif
    </div>
    <div class="flex items-center">
        <div class="flex items-center gap-2.5">
            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-blue-600 text-white font-bold">
                {{ mb_strtoupper(mb_substr($usuario->nombres, 0, 1, 'UTF-8')) }}
            </div>

            <div class="flex flex-col">
                <span class="text-sm font-semibold text-gray-900">
                    {{ $usuario->nombres }} {{ $usuario->apellidos }}
                </span>
                <span class="mt-0.5 text-xs text-gray-500">
                    Rol: {{ $usuario->rol->nombre }}
                </span>
            </div>
        </div>
    </div>
</header>

@endsection
