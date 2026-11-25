<div class="space-y-6">
    <div>
        <h3 class="text-lg font-semibold text-gray-900">Nuevo lote devengado</h3>
        <p class="mt-1 text-sm text-gray-500">
            Selecciona el colegio y el período del lote para regularizar aportes de gestiones pasadas.
        </p>
    </div>

    <form method="POST" action="{{ route('devengados.store') }}" class="space-y-6">
        @csrf

        <div>
            <label for="devengado_colegio_id" class="mb-2 block text-sm font-medium text-gray-700">
                Colegio
            </label>
            @if ($colegiosDisponibles->count() > 1)
                <select
                    name="colegio_id"
                    id="devengado_colegio_id"
                    required
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-[#1a34c9] focus:outline-none focus:ring-2 focus:ring-[#1a34c9]/20"
                >
                    <option value="">Selecciona un colegio</option>
                    @foreach ($colegiosDisponibles as $colegio)
                        <option value="{{ $colegio->id }}" @selected(old('colegio_id') == $colegio->id)>
                            {{ $colegio->nombre }}
                        </option>
                    @endforeach
                </select>
            @else
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                    <p class="text-sm font-medium text-gray-900">{{ $colegiosDisponibles->first()?->nombre }}</p>
                    <p class="mt-1 text-xs text-gray-500">El colegio se asigna automáticamente según tu usuario.</p>
                </div>
            @endif
            @error('colegio_id')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            @error('lote')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <p class="mb-2 text-sm font-medium text-gray-700">Año</p>
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                <p class="text-sm font-medium text-gray-900">{{ now()->year }}</p>
                <p class="mt-1 text-xs text-gray-500">El año se asigna automáticamente.</p>
            </div>
        </div>

        <div>
            <label for="devengado_mes" class="mb-2 block text-sm font-medium text-gray-700">
                Mes
            </label>
            <select
                name="mes"
                id="devengado_mes"
                required
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-[#1a34c9] focus:outline-none focus:ring-2 focus:ring-[#1a34c9]/20"
            >
                <option value="">Selecciona un mes</option>
                @foreach ([1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'] as $numero => $nombre)
                    <option value="{{ $numero }}" @selected(old('mes') == $numero)>
                        {{ $nombre }} {{ now()->year }}
                    </option>
                @endforeach
            </select>
            @error('mes')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="devengado_observaciones" class="mb-2 block text-sm font-medium text-gray-700">
                Observaciones (opcional)
            </label>
            <textarea
                name="observaciones"
                id="devengado_observaciones"
                rows="3"
                maxlength="2000"
                class="block w-full rounded-lg border border-gray-300 text-sm focus:border-[#1a34c9] focus:outline-none focus:ring-2 focus:ring-[#1a34c9]/20"
            >{{ old('observaciones') }}</textarea>
            @error('observaciones')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="rounded-xl border border-blue-200 bg-blue-50 px-5 py-4">
            <p class="text-sm font-medium text-blue-800">Importante</p>
            <p class="mt-1 text-sm text-blue-700">
                Una vez creado el lote podrás registrar los pagos de los afiliados correspondientes a los períodos anteriores.
            </p>
        </div>

        <div class="flex justify-end gap-3 border-t border-gray-200 pt-5">
            @if ($enModal)
                <button
                    type="button"
                    @click="abierto = false"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-100 hover:cursor-pointer"
                >
                    <x-icon name="cancel" class="text-gray-700" size="20"/>
                    Cancelar
                </button>
            @else
                <a
                    href="{{ route('devengados.index') }}"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-100"
                >
                    <x-icon name="cancel" class="text-gray-700" size="20"/>
                    Cancelar
                </a>
            @endif
            <button
                type="submit"
                class="inline-flex items-center gap-1.5 rounded-lg bg-[#16A34A] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#4cb874] hover:cursor-pointer"
            >
                <x-icon name="register" class="text-white" size="20"/>
                Crear lote
            </button>
        </div>
    </form>
</div>
