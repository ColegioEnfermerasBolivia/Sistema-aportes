@extends('layouts.app')

@section('title', 'Registrar pago devengado')

@section('content')
<div class="mx-auto max-w-5xl px-6 py-8 lg:px-10">
    <div class="mb-6">
        <a href="{{ route('devengados.show', $periodoAncla->devengado_lote_id) }}" class="text-sm font-semibold text-[#1a34c9] hover:underline">← Volver al lote</a>
        <h1 class="mt-3 text-2xl font-bold text-[#0d1b6e]">Registrar pago de devengados</h1>
        <p class="mt-1 text-sm text-gray-600">{{ $afiliado->apellidos }}, {{ $afiliado->nombres }} · CI {{ $afiliado->ci }}</p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('devengados.pagos.store', $periodoAncla) }}" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        @csrf
        <input type="hidden" name="clave_idempotencia" value="{{ $claveIdempotencia }}">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Período / lote</th>
                        <th class="px-5 py-3">Importe total</th>
                        <th class="px-5 py-3">Pagado</th>
                        <th class="px-5 py-3">Exonerado</th>
                        <th class="px-5 py-3">Saldo pendiente</th>
                        <th class="px-5 py-3">Abono (Bs)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($periodos as $item)
                        @php
                            $pagado = $devengadoService->montoPagado($item);
                            $exonerado = $devengadoService->montoExonerado($item);
                            $pendiente = $devengadoService->saldoPendiente($item);
                        @endphp
                        <tr>
                            <td class="px-5 py-4">
                                <div class="font-semibold">{{ str_pad((string) $item->mes, 2, '0', STR_PAD_LEFT) }}/{{ $item->year }}</div>
                                <div class="text-xs text-gray-500">{{ $item->lote->codigo_lote }}</div>
                            </td>
                            <td class="px-5 py-4">Bs {{ number_format((float) $item->monto, 2, ',', '.') }}</td>
                            <td class="px-5 py-4">Bs {{ number_format($pagado / 100, 2, ',', '.') }}</td>
                            <td class="px-5 py-4">Bs {{ number_format($exonerado / 100, 2, ',', '.') }}</td>
                            <td class="px-5 py-4 font-semibold">Bs {{ number_format($pendiente / 100, 2, ',', '.') }}</td>
                            <td class="px-5 py-4">
                                <input name="asignaciones[{{ $item->id }}]" type="number" min="0.01"
                                    max="{{ number_format($pendiente / 100, 2, '.', '') }}" step="0.01" placeholder="0.00"
                                    class="w-32 rounded-lg border-gray-300 text-sm">
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-gray-500">No hay períodos con saldo pendiente.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($periodos->isNotEmpty())
            <div class="space-y-4 border-t border-gray-100 p-5">
                <label class="block text-sm font-medium text-gray-700">Observaciones
                    <textarea name="observaciones" maxlength="1000" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">{{ old('observaciones') }}</textarea>
                </label>
                <button type="submit" class="rounded-lg bg-[#1a34c9] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#1429a5]">Registrar distribución de pago</button>
            </div>
        @endif
    </form>
</div>
@endsection
