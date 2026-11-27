<div class="border-b border-gray-200 bg-white px-6 py-5">
    <form
        method="GET"
        action="{{ route('egresadas.index') }}"
        class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4"
    >
        <div>
            <label for="ci" class="mb-2 block text-sm font-medium text-gray-800">CI</label>
            <input
                id="ci"
                name="ci"
                value="{{ $filtros['ci'] ?? '' }}"
                class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm"
                placeholder="Buscar por CI"
            >
        </div>
        <div>
            <label for="buscar" class="mb-2 block text-sm font-medium text-gray-800">Nombres y apellidos</label>
            <input
                id="buscar"
                name="buscar"
                value="{{ $filtros['buscar'] ?? '' }}"
                class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm"
                placeholder="Nombre o apellido"
            >
        </div>
        <div>
            <label for="universidad_id" class="mb-2 block text-sm font-medium text-gray-800">Universidad</label>
            <select id="universidad_id" name="universidad_id" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm">
                <option value="">Todas las universidades</option>
                @foreach ($universidades as $universidad)
                    <option value="{{ $universidad->id }}" @selected(($filtros['universidad_id'] ?? '') == $universidad->id)>
                        {{ $universidad->nombre }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="carrera" class="mb-2 block text-sm font-medium text-gray-800">Carrera</label>
            <input
                id="carrera"
                name="carrera"
                value="{{ $filtros['carrera'] ?? '' }}"
                class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm"
                placeholder="Buscar por carrera"
            >
        </div>
        <div>
            <label for="departamento_id" class="mb-2 block text-sm font-medium text-gray-800">Departamento de formación</label>
            <select id="departamento_id" name="departamento_id" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm">
                <option value="">Todos los departamentos</option>
                @foreach ($departamentos as $departamento)
                    <option value="{{ $departamento->id }}" @selected(($filtros['departamento_id'] ?? '') == $departamento->id)>
                        {{ $departamento->nombre }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="anio_egreso" class="mb-2 block text-sm font-medium text-gray-800">Año de egreso</label>
            <input
                id="anio_egreso"
                name="anio_egreso"
                type="number"
                min="1900"
                max="{{ now()->year }}"
                value="{{ $filtros['anio_egreso'] ?? '' }}"
                class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm"
            >
        </div>
        <div>
            <label for="estado_titulacion" class="mb-2 block text-sm font-medium text-gray-800">Estado de titulación</label>
            <select id="estado_titulacion" name="estado_titulacion" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm">
                <option value="">Todos</option>
                @foreach (\App\Models\EgresadaFormacion::ESTADOS_TITULACION as $estado)
                    <option value="{{ $estado }}" @selected(($filtros['estado_titulacion'] ?? '') === $estado)>
                        {{ ucfirst(strtolower($estado)) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="estado_incorporacion" class="mb-2 block text-sm font-medium text-gray-800">Estado de incorporación</label>
            <select id="estado_incorporacion" name="estado_incorporacion" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm">
                <option value="">Todos</option>
                <option value="PENDIENTE" @selected(($filtros['estado_incorporacion'] ?? '') === 'PENDIENTE')>Pendiente</option>
                <option value="AFILIADA" @selected(($filtros['estado_incorporacion'] ?? '') === 'AFILIADA')>Convertida en afiliada</option>
            </select>
        </div>
        <div class="flex flex-wrap items-end gap-3 xl:col-span-4">
            <button type="submit" class="rounded-lg bg-[#1a34c9] px-6 py-3 text-sm font-semibold text-white hover:bg-[#1429a3] hover:cursor-pointer">
                Buscar
            </button>
            <button
                type="submit"
                formaction="{{ route('egresadas.exportar.excel') }}"
                class="rounded-lg border border-green-600 bg-white px-6 py-3 text-sm font-semibold text-green-700 hover:bg-green-50 hover:cursor-pointer"
            >
                Descargar Excel
            </button>
            <a href="{{ route('egresadas.index') }}" class="rounded-lg border border-gray-300 bg-white px-6 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:cursor-pointer">
                Limpiar
            </a>
        </div>
    </form>
</div>
