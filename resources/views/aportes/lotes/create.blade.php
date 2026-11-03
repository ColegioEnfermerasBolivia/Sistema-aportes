@extends('layouts.app')

@section('title', 'Crear lote de aportes')

@section('content')
    <div class="mx-auto max-w-3xl p-6">
        <a
            href="{{ route('lotes.index') }}"
            class="mb-6 inline-flex text-sm font-medium text-[#1a34c9] hover:underline"
        >
            Volver a lotes
        </a>

        <div x-data="{ abierto: true }" class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            @include('aportes.lotes.partials.lote-crear')
        </div>
    </div>
@endsection
