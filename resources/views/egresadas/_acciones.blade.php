<div class="flex flex-wrap gap-2">
    @if (!$egresada->estaConvertida())
        <button
            type="button"
            data-edit-url="{{ route('egresadas.edit', $egresada) }}"
            @click="editarEgresada($el.dataset.editUrl)"
            :disabled="cargandoEgresada"
            class="inline-flex items-center gap-1.5 rounded-lg border border-blue-500 bg-white px-3 py-2 text-xs font-semibold text-blue-600 hover:bg-blue-50 disabled:opacity-50 hover:cursor-pointer"
        >
            <x-icon name="edit" class="text-blue-500" size="20"/>
            Editar
        </button>
        @if ($egresada->tieneFormacionTitulada())
            <button
                type="button"
                data-egresada="{{ json_encode(
                    $egresada->loadMissing('formaciones.universidad', 'formaciones.departamento')->toArray(),
                    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
                ) }}"
                @click="abrirConversion(JSON.parse($el.dataset.egresada))"
                class="inline-flex items-center gap-1.5 rounded-lg border border-green-600 bg-white px-3 py-2 text-xs font-semibold text-green-700 hover:bg-green-50 hover:cursor-pointer"
            >
                Convertir en afiliada
            </button>
        @else
            <span
                title="{{ \App\Models\Egresada::MENSAJE_REQUIERE_TITULACION }}"
                class="rounded-lg bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700"
            >
                Requiere titulación
            </span>
        @endif
    @else
        <span class="rounded-lg bg-green-50 px-3 py-2 text-xs font-semibold text-green-700">
            Afiliada
        </span>
    @endif
</div>
