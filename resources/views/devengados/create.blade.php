@extends('layouts.app')

@section('title', 'Crear lote devengado')

@section('content')
<div class="min-h-screen bg-[#f7f7f9] px-6 py-8 lg:px-12">
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('devengados.index') }}" class="text-sm font-semibold text-[#1a34c9] hover:underline">
            ← Volver a aportes devengados
        </a>

        @if ($errors->any())
            <div class="mt-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-5 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            @include('devengados.partials.lote-crear', [
                'colegiosDisponibles' => $colegiosDisponibles,
                'enModal' => false,
            ])
        </div>
    </div>
</div>
@endsection
