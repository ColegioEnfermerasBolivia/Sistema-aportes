@extends('layouts.app')

@section('title', 'Información laboral')

@section('content')
    <div class="mx-auto max-w-5xl p-6">
        <a
            href="{{ route('afiliados.index') }}"
            class="mb-6 inline-flex text-sm font-medium text-[#1a34c9] hover:underline"
        >
            Volver a afiliados
        </a>

        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">
                Información laboral
            </h1>
            <p class="mt-1 text-sm text-gray-600">
                {{ $afiliado->nombres }} {{ $afiliado->apellidos }}
            </p>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
            @forelse ($informacionesLaborales as $informacionLaboral)
                <article class="border-b border-gray-200 p-5 last:border-b-0">
                    <h2 class="font-semibold text-gray-900">
                        {{ $informacionLaboral->institucion }}
                    </h2>
                    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-gray-500">Tipo de contrato</dt>
                            <dd class="text-gray-900">{{ $informacionLaboral->tipo_contrato }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Haber básico</dt>
                            <dd class="text-gray-900">{{ number_format((float) $informacionLaboral->haber_basico, 2) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Fecha de ingreso</dt>
                            <dd class="text-gray-900">{{ $informacionLaboral->fecha_ingreso_trabajo }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Estado</dt>
                            <dd class="text-gray-900">
                                {{ $informacionLaboral->culminado ? 'Culminado' : 'Activo' }}
                            </dd>
                        </div>
                    </dl>
                </article>
            @empty
                <p class="p-6 text-sm text-gray-600">
                    No hay información laboral registrada.
                </p>
            @endforelse
        </div>
    </div>
@endsection
