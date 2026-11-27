<x-modal
    titulo="Registrar egresada"
    ancho="max-w-5xl"
    titulo-alpine="tituloModalEgresada"
    x-model="modalEgresadaAbierto"
>
    <p x-show="errorEgresada" x-text="errorEgresada" class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700"></p>
    <form method="POST" :action="accionEgresada" class="space-y-6">
        @csrf
        <input type="hidden" name="_form_type" value="egresada">
        <input type="hidden" name="_form_mode" :value="editandoEgresada ? 'editar' : 'crear'">
        <input type="hidden" name="formaciones_present" value="1" :disabled="!editandoEgresada">
        <input type="hidden" name="_egresada_id" :value="egresada.id || ''" :disabled="!editandoEgresada">
        <input type="hidden" name="_method" value="PUT" :disabled="!editandoEgresada">

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label for="egresada_ci" class="mb-2 block text-sm font-medium text-gray-700">CI</label>
                <input id="egresada_ci" name="ci" type="text" maxlength="30" required x-model="egresada.ci" class="w-full rounded-lg border border-gray-300 px-3.5 py-3 text-sm">
                @error('ci')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="egresada_nombres" class="mb-2 block text-sm font-medium text-gray-700">Nombres</label>
                <input id="egresada_nombres" name="nombres" type="text" maxlength="150" required x-model="egresada.nombres" class="w-full rounded-lg border border-gray-300 px-3.5 py-3 text-sm">
                @error('nombres')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="egresada_apellidos" class="mb-2 block text-sm font-medium text-gray-700">Apellidos</label>
                <input id="egresada_apellidos" name="apellidos" type="text" maxlength="150" required x-model="egresada.apellidos" class="w-full rounded-lg border border-gray-300 px-3.5 py-3 text-sm">
                @error('apellidos')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="egresada_celular" class="mb-2 block text-sm font-medium text-gray-700">Celular</label>
                <input id="egresada_celular" name="celular" type="text" maxlength="30" x-model="egresada.celular" class="w-full rounded-lg border border-gray-300 px-3.5 py-3 text-sm">
                @error('celular')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="border-t border-gray-200 pt-5">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-base font-semibold text-[#0d1b6e]">Formaciones universitarias</h3>
                    <p class="mt-1 text-xs text-gray-500">Cada formación tiene su propio departamento y universidad.</p>
                </div>
                <button type="button" @click="formaciones.push(nuevaFormacion())" :disabled="!departamentoInicial" class="rounded-lg border border-[#1a34c9] px-4 py-2 text-sm font-semibold text-[#1a34c9] hover:bg-blue-50 disabled:cursor-not-allowed disabled:opacity-50 hover:cursor-pointer">
                    + Añadir formación
                </button>
            </div>

            @error('formaciones')<p class="mb-3 text-sm text-red-600">{{ $message }}</p>@enderror

            <template x-for="(formacion, index) in formaciones" :key="formacion.id || index">
                <div class="mb-4 rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <input type="hidden" :name="`formaciones[${index}][id]`" :value="formacion.id || ''" :disabled="!formacion.id">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Universidad</label>
                            <select :name="`formaciones[${index}][universidad_id]`" x-model="formacion.universidad_id" required class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-3 text-sm">
                                <option value="">Seleccione una universidad</option>
                                @foreach ($universidades as $universidad)
                                    <option value="{{ $universidad->id }}">{{ $universidad->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Departamento</label>
                            <select :name="`formaciones[${index}][departamento_id]`" x-model="formacion.departamento_id" required class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-3 text-sm">
                                <option value="">Seleccione un departamento</option>
                                @foreach ($departamentosOperables as $departamento)
                                    <option value="{{ $departamento->id }}">{{ $departamento->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Carrera</label>
                            <input :name="`formaciones[${index}][carrera]`" type="text" maxlength="200" required x-model="formacion.carrera" class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-3 text-sm">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Fecha de egreso</label>
                            <input :name="`formaciones[${index}][fecha_egreso]`" type="date" x-model="formacion.fecha_egreso" class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-3 text-sm">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Estado de titulación</label>
                            <select :name="`formaciones[${index}][estado_titulacion]`" x-model="formacion.estado_titulacion" class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-3 text-sm">
                                @foreach (\App\Models\EgresadaFormacion::ESTADOS_TITULACION as $estado)
                                    <option value="{{ $estado }}">{{ ucfirst(strtolower($estado)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Número de título</label>
                            <input :name="`formaciones[${index}][numero_titulo]`" type="text" maxlength="100" x-model="formacion.numero_titulo" class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-3 text-sm">
                        </div>
                    </div>
                    <button type="button" @click="formaciones.splice(index, 1)" class="mt-3 text-sm font-semibold text-red-600 hover:text-red-800 hover:cursor-pointer" x-text="formacion.id ? 'Eliminar formación' : 'Quitar formación nueva'"></button>
                </div>
            </template>
            <p x-show="editandoEgresada && formaciones.length === 0" class="text-sm text-gray-500">
                No hay formaciones que pueda editar en su departamento. Puede actualizar los datos personales.
            </p>
        </div>

        <div class="flex justify-end border-t border-gray-200 pt-5">
            <button type="submit" class="rounded-lg bg-[#1a34c9] px-6 py-3 text-sm font-semibold text-white hover:bg-[#1429a3] hover:cursor-pointer">
                <span x-text="editandoEgresada ? 'Guardar cambios' : 'Registrar egresada'"></span>
            </button>
        </div>
    </form>
</x-modal>
