<div class="space-y-6">

    {{-- INFORMACIÓN --}}
    <div>
        <h3 class="text-lg font-semibold text-gray-900">
            Nuevo lote de aportes
        </h3>

        <p class="mt-1 text-sm text-gray-500">
            Selecciona el mes correspondiente al lote. El año y el colegio
            serán asignados automáticamente por el sistema.
        </p>
    </div>

    {{-- FORMULARIO --}}
    <form
        method="POST"
        action="{{ route('lotes.store') }}"
        class="space-y-6"
    >
        @csrf
        {{-- COLEGIO --}}
        <div>
            <label class="mb-2 block text-sm font-medium text-gray-700">
                Colegio
            </label>

            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                <p class="text-sm font-medium text-gray-900">
                    {{ Auth::user()->colegio->nombre }}
                </p>

                <p class="mt-1 text-xs text-gray-500">
                    El colegio se asigna automáticamente según tu usuario.
                </p>
            </div>
        </div>

        {{-- AÑO --}}
        <div>
            <label class="mb-2 block text-sm font-medium text-gray-700">
                Año
            </label>

            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                <p class="text-sm font-medium text-gray-900">
                    {{ now()->year }}
                </p>

                <p class="mt-1 text-xs text-gray-500">
                    El año se asigna automáticamente.
                </p>
            </div>
        </div>

        {{-- MES --}}
        <div>
            <label
                for="mes"
                class="mb-2 block text-sm font-medium text-gray-700"
            >
                Mes
            </label>

            <select
                name="mes"
                id="mes"
                required
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-[#1a34c9] focus:outline-none focus:ring-2 focus:ring-[#1a34c9]/20"
            >
                <option value="">Selecciona un mes</option>

                <option value="1" {{ old('mes') == 1 ? 'selected' : '' }}>
                    Enero
                </option>

                <option value="2" {{ old('mes') == 2 ? 'selected' : '' }}>
                    Febrero
                </option>

                <option value="3" {{ old('mes') == 3 ? 'selected' : '' }}>
                    Marzo
                </option>

                <option value="4" {{ old('mes') == 4 ? 'selected' : '' }}>
                    Abril
                </option>

                <option value="5" {{ old('mes') == 5 ? 'selected' : '' }}>
                    Mayo
                </option>

                <option value="6" {{ old('mes') == 6 ? 'selected' : '' }}>
                    Junio
                </option>

                <option value="7" {{ old('mes') == 7 ? 'selected' : '' }}>
                    Julio
                </option>

                <option value="8" {{ old('mes') == 8 ? 'selected' : '' }}>
                    Agosto
                </option>

                <option value="9" {{ old('mes') == 9 ? 'selected' : '' }}>
                    Septiembre
                </option>

                <option value="10" {{ old('mes') == 10 ? 'selected' : '' }}>
                    Octubre
                </option>

                <option value="11" {{ old('mes') == 11 ? 'selected' : '' }}>
                    Noviembre
                </option>

                <option value="12" {{ old('mes') == 12 ? 'selected' : '' }}>
                    Diciembre
                </option>
            </select>

            @error('mes')
                <p class="mt-2 text-sm text-red-600">
                    {{ $message }}
                </p>
            @enderror
        </div>

        {{-- AVISO --}}
        <div class="rounded-xl border border-blue-200 bg-blue-50 px-5 py-4">
            <p class="text-sm font-medium text-blue-800">
                Importante
            </p>
            <p class="mt-1 text-sm text-blue-700">
                Una vez creado el lote podrás registrar los pagos de los
                afiliados correspondientes a este período.
            </p>
        </div>

        {{-- BOTONES --}}
        <div class="flex justify-end gap-3 border-t border-gray-200 pt-5">

            <button
                type="button"
                @click="abierto = false"
                class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-100 inline-flex items-center gap-1.5 hover:cursor-pointer"
            >
                <x-icon name="cancel" class="text-gray-700" size="20"/>
                Cancelar
            </button>
            <button
                type="submit"
                class="rounded-lg bg-[#16A34A] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#4cb874] inline-flex items-center gap-1.5 hover:cursor-pointer"
            >
                <x-icon name="register" class="text-white" size="20"/>
                Crear lote
            </button>
        </div>
    </form>
</div>
