@extends('layouts.app')

@section('title', 'Editar usuario')

@section('content')
    <div class="mx-auto max-w-3xl p-6">
        <h1 class="mb-6 text-2xl font-semibold text-gray-900">
            Editar usuario
        </h1>

        @include('usuarios.partials.edit-form', [
            'usuario' => $usuario,
            'colegios' => $colegios,
            'roles' => $roles,
        ])
    </div>
@endsection
