@extends('layouts.app')

@section('title', 'Editar pago devengado')

@section('content')
<div class="fixed inset-0 z-[9998] overflow-y-auto bg-gray-900/60 p-3 sm:p-6">
    <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="editar-pago-devengado-titulo"
        class="mx-auto my-2 flex max-h-[calc(100vh-2rem)] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
    >
        <div class="flex shrink-0 items-center justify-between border-b border-gray-200 px-5 py-4 sm:px-7">
            <h1 id="editar-pago-devengado-titulo" class="text-xl font-bold text-[#0d1b6e]">
                Editar pago devengado
            </h1>
            <a
                href="{{ route('devengados.show', $pago->devengado_lote_id) }}"
                aria-label="Cerrar edición"
                class="rounded-full px-3 py-1 text-2xl leading-none text-gray-500 hover:bg-gray-100 hover:text-gray-900"
            >×</a>
        </div>
        <div class="min-h-0 overflow-y-auto px-5 py-5 sm:px-7 sm:py-6">
            @include('devengados.pagos.form', [
                'modoEdicion' => true,
                'lotesDisponibles' => $lotesDisponibles,
            ])
        </div>
    </div>
</div>
@endsection
