@extends('layouts.app')

@section('title', 'Reporte de Pagos por Lote')

@section('content')
<div
    class="space-y-6 p-6"
    x-data="{
        previewOpen: false,
        previewLoading: false,
        previewHtml: '',
        previewError: '',
        downloadLoading: false,
        downloadError: '',
        async verReporte(url) {
            this.previewOpen = true;
            this.previewLoading = true;
            this.previewHtml = '';
            this.previewError = '';

            try {
                const response = await fetch(url, {
                    headers: { 'Accept': 'text/html' },
                });

                if (!response.ok) {
                    throw new Error('No se pudo obtener la vista previa.');
                }

                this.previewHtml = await response.text();
            } catch (error) {
                console.error(error);
                this.previewError = 'No se pudo cargar el reporte. Intente nuevamente.';
            } finally {
                this.previewLoading = false;
            }
        },
        async descargar(url, filename) {
            this.downloadLoading = true;
            this.downloadError = '';

            try {
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    },
                });

                if (!response.ok) {
                    throw new Error('No se pudo generar el archivo Excel.');
                }

                const blob = await response.blob();
                const objectUrl = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = objectUrl;
                link.download = filename;
                link.click();
                window.setTimeout(() => URL.revokeObjectURL(objectUrl), 1000);
            } catch (error) {
                console.error(error);
                this.downloadError = 'No se pudo generar el Excel. Intente nuevamente.';
            } finally {
                this.downloadLoading = false;
            }
        },
    }"
>
    <header>
        <h1 class="text-2xl font-bold text-gray-800">
            Reporte de Pagos por Lote
        </h1>
        <p class="mt-1 text-sm text-gray-500">
            Consulte los lotes registrados y genere el detalle de pagos de cada uno.
        </p>
    </header>

    <section class="rounded-xl bg-white p-5 shadow-sm">
        <form
            method="GET"
            action="{{ route('lotesReports.index') }}"
            class="grid grid-cols-1 items-end gap-4 md:grid-cols-2 lg:grid-cols-4"
        >
            <div>
                <label for="codigo_lote" class="mb-1 block text-sm font-medium text-gray-700">
                    Código de lote
                </label>
                <input
                    id="codigo_lote"
                    name="codigo_lote"
                    type="text"
                    maxlength="100"
                    value="{{ request('codigo_lote') }}"
                    placeholder="Buscar por código"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#1a34c9] focus:outline-none focus:ring-1 focus:ring-[#1a34c9]"
                >
            </div>

            <div>
                <label for="gestion" class="mb-1 block text-sm font-medium text-gray-700">
                    Gestión
                </label>
                <select
                    id="gestion"
                    name="gestion"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#1a34c9] focus:outline-none focus:ring-1 focus:ring-[#1a34c9]"
                >
                    <option value="">Todas las gestiones</option>
                    @foreach ($gestiones as $gestion)
                        <option
                            value="{{ $gestion }}"
                            @selected((string) request('gestion') === (string) $gestion)
                        >
                            {{ $gestion }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <button
                    type="submit"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-[#1a34c9] px-4 py-2 text-sm font-medium text-white transition hover:cursor-pointer hover:bg-blue-800"
                >
                    <x-icon name="search" size="20"/>
                    Buscar
                </button>
                <a
                    href="{{ route('lotesReports.index') }}"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 hover:cursor-pointer"
                >
                    <x-icon name="eraser" size="20"/>
                    Limpiar
                </a>
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-xl bg-white shadow-sm">
        @if ($lotes->isEmpty())
            <div class="px-5 py-12 text-center">
                <p class="font-medium text-gray-700">
                    No se encontraron lotes coincidentes.
                </p>
                <p class="mt-1 text-sm text-gray-500">
                    Verifique los filtros seleccionados o intente una nueva búsqueda.
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-600">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Código de lote</th>
                            <th class="px-5 py-3 font-semibold">Colegio</th>
                            <th class="px-5 py-3 font-semibold">Gestión</th>
                            <th class="px-5 py-3 font-semibold">Estado</th>
                            <th class="px-5 py-3 text-right font-semibold">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($lotes as $lote)
                            <tr>
                                <td class="px-5 py-4 font-medium text-gray-800">
                                    {{ $lote->codigo_lote }}
                                </td>
                                <td class="px-5 py-4 text-gray-600">
                                    {{ $lote->colegio?->nombre ?? '—' }}
                                </td>
                                <td class="px-5 py-4 text-gray-600">
                                    {{ $lote->year }}
                                </td>
                                <td class="px-5 py-4">
                                    <span @class([
                                        'inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium',
                                        'border-green-200 bg-green-50 text-green-700' => $lote->estado === 'VERIFICADO',
                                        'border-orange-200 bg-orange-50 text-orange-600' => $lote->estado !== 'VERIFICADO',
                                        'border-red-200 bg-red-50 text-red-700' => $lote->estado === 'ABIERTO',
                                    ])>
                                        {{ $lote->estado }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        @if ($lote->estado === 'ABIERTO')
                                            <button
                                                type="button"
                                                disabled
                                                title="El lote debe dejar de estar abierto para generar el reporte."
                                                class="cursor-not-allowed rounded-lg bg-gray-200 px-3 py-2 text-xs font-medium text-gray-500 hover:cursor-pointer"
                                            >
                                            <x-icon name="see" size="20"/>
                                                Ver
                                            </button>
                                            <button
                                                type="button"
                                                disabled
                                                title="El lote debe dejar de estar abierto para generar el reporte."
                                                class="cursor-not-allowed rounded-lg bg-gray-200 px-3 py-2 text-xs font-medium text-gray-500 hover:cursor-pointer"
                                            >
                                                Descargar Excel
                                            </button>
                                        @else
                                            <button
                                                type="button"
                                                @click="verReporte(@js(route('lotesReports.preview', $lote)))"
                                                class="rounded-lg border border-[#1a34c9] px-3 py-2 text-xs font-medium text-[#1a34c9] transition hover:bg-blue-50 hover:cursor-pointer"
                                            >
                                            <x-icon name="see" size="20"/>
                                                Ver
                                            </button>
                                            <button
                                                type="button"
                                                @click="descargar(
                                                    @js(route('lotesReports.excel', $lote)),
                                                    @js('reporte-pagos-lote-' . $lote->id . '.xlsx')
                                                )"
                                                :disabled="downloadLoading"
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-3 py-2 text-xs font-medium text-white transition hover:bg-green-700 disabled:cursor-wait disabled:opacity-70 hover:cursor-pointer"
                                            >
                                                <x-icon name="download" size="20"/>
                                                <span x-show="!downloadLoading">Descargar Excel</span>
                                                <span x-cloak x-show="downloadLoading">Generando...</span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-100 px-5 py-4">
                {{ $lotes->links() }}
            </div>
        @endif

        <p
            x-cloak
            x-show="downloadError"
            x-text="downloadError"
            role="alert"
            class="px-5 pb-4 text-sm text-red-700"
        ></p>
    </section>

    <div
        x-cloak
        x-show="previewOpen"
        @keydown.escape.window="previewOpen = false"
        class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="preview-title"
    >
        <div
            @click.outside="previewOpen = false"
            class="flex max-h-[90vh] w-full max-w-6xl flex-col rounded-xl bg-white shadow-xl"
        >
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                <h2 id="preview-title" class="text-lg font-semibold text-gray-800">
                    Vista previa del reporte
                </h2>
                <button
                    type="button"
                    @click="previewOpen = false"
                    class="rounded-lg px-3 py-1 text-xl text-gray-500 hover:bg-gray-100"
                    aria-label="Cerrar vista previa"
                >
                    &times;
                </button>
            </div>

            <div class="overflow-y-auto p-5">
                <div x-show="previewLoading" class="py-12 text-center text-sm text-gray-600">
                    Cargando vista previa...
                </div>
                <p
                    x-show="previewError"
                    x-text="previewError"
                    role="alert"
                    class="py-8 text-center text-sm text-red-700"
                ></p>
                <div x-show="!previewLoading && !previewError" x-html="previewHtml"></div>
            </div>
        </div>
    </div>
</div>
@endsection
