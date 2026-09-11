@extends('layouts.app')

@section('content')

<div class="mx-auto max-w-6xl px-6 py-8">

    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">
            Detalle del lote
        </h1>

        <p class="mt-1 text-sm text-gray-500">
            Información del lote de verificación.
        </p>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

            <div>
                <p class="text-sm text-gray-500">
                    Código del lote
                </p>

                <p class="mt-1 font-medium text-gray-900">
                    {{ $lote->codigo_lote }}
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-500">
                    Colegio
                </p>

                <p class="mt-1 font-medium text-gray-900">
                    {{ $lote->colegio->nombre }}
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-500">
                    Período
                </p>

                <p class="mt-1 font-medium text-gray-900">
                    {{ str_pad($lote->mes, 2, '0', STR_PAD_LEFT) }}/{{ $lote->year }}
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-500">
                    Estado
                </p>

                <p class="mt-1 font-medium text-gray-900">
                    {{ $lote->estado }}
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-500">
                    Cantidad de aportes
                </p>

                <p class="mt-1 font-medium text-gray-900">
                    {{ $lote->cantidad_aportes }}
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-500">
                    Monto total
                </p>

                <p class="mt-1 font-medium text-gray-900">
                    Bs. {{ number_format($lote->monto_total, 2, ',', '.') }}
                </p>
            </div>

        </div>

    </div>

</div>

@endsection