<x-modal
    titulo="Convertir en afiliada"
    ancho="max-w-3xl"
    x-model="modalConversionAbierto"
>
    <form method="POST" :action="accionConversion" class="space-y-6">
        @csrf
        <input type="hidden" name="_form_type" value="conversion">
        <input type="hidden" name="_egresada_id" x-model="egresadaConversion.id">

        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
            <h3 class="mb-3 text-base font-semibold text-[#0d1b6e]">Datos existentes de la egresada</h3>
            <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-gray-500">CI</dt>
                    <dd class="font-medium text-gray-900" x-text="egresadaConversion.ci"></dd>
                    @error('ci')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <dt class="text-gray-500">Nombres</dt>
                    <dd class="font-medium text-gray-900" x-text="egresadaConversion.nombres"></dd>
                    @error('nombres')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <dt class="text-gray-500">Apellidos</dt>
                    <dd class="font-medium text-gray-900" x-text="egresadaConversion.apellidos"></dd>
                    @error('apellidos')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <dt class="text-gray-500">Formaciones</dt>
                    <dd class="font-medium text-gray-900">
                        <template x-if="!egresadaConversion.formaciones || !egresadaConversion.formaciones.length">
                            <span>Sin formaciones registradas</span>
                        </template>
                        <template x-for="(formacion, index) in (egresadaConversion.formaciones || [])" :key="formacion.id || index">
                            <span class="block" x-text="`${formacion.carrera} — ${formacion.universidad?.nombre || ''}`"></span>
                        </template>
                    </dd>
                </div>
            </dl>
        </div>

        @error('egresada')
            <p class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</p>
        @enderror

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label for="conversion_registro" class="mb-2 block text-sm font-medium text-gray-700">Registro departamental</label>
                <input id="conversion_registro" name="registro_departamental" x-model="conversion.registro_departamental" maxlength="30" required class="w-full rounded-lg border border-gray-300 px-3.5 py-3 text-sm">
                @error('registro_departamental')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="conversion_matricula" class="mb-2 block text-sm font-medium text-gray-700">Matrícula profesional</label>
                <input id="conversion_matricula" name="matricula_profesional" x-model="conversion.matricula_profesional" maxlength="30" required class="w-full rounded-lg border border-gray-300 px-3.5 py-3 text-sm">
                @error('matricula_profesional')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="conversion_celular" class="mb-2 block text-sm font-medium text-gray-700">Celular</label>
                <input id="conversion_celular" name="celular" x-model="conversion.celular" maxlength="20" required class="w-full rounded-lg border border-gray-300 px-3.5 py-3 text-sm">
                @error('celular')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="conversion_fecha" class="mb-2 block text-sm font-medium text-gray-700">Fecha de afiliación</label>
                <input id="conversion_fecha" name="fecha_afiliacion" type="date" x-model="conversion.fecha_afiliacion" required class="w-full rounded-lg border border-gray-300 px-3.5 py-3 text-sm">
                @error('fecha_afiliacion')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label for="conversion_colegio" class="mb-2 block text-sm font-medium text-gray-700">Colegio de destino</label>
                <select id="conversion_colegio" name="colegio_id" x-model="conversion.colegio_id" required class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-3 text-sm">
                    <option value="">Seleccione un colegio</option>
                    @foreach ($colegiosConvertibles as $colegio)
                        <option value="{{ $colegio->id }}">{{ $colegio->nombre }}</option>
                    @endforeach
                </select>
                @error('colegio_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex justify-end border-t border-gray-200 pt-5">
            <button type="submit" @disabled($colegiosConvertibles->isEmpty()) class="rounded-lg bg-green-700 px-6 py-3 text-sm font-semibold text-white hover:bg-green-800 disabled:cursor-not-allowed disabled:opacity-50 hover:cursor-pointer">
                Afiliar
            </button>
        </div>
    </form>
</x-modal>
