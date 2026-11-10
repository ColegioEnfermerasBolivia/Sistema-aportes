
@if ($paginator->hasPages())
    <nav role="navigation"
         aria-label="Paginación"
         class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        {{-- Información de registros --}}
        <div class="text-sm text-gray-500">
            Mostrando
            <span class="font-semibold text-gray-800">
                {{ $paginator->firstItem() ?? 0 }}
            </span>
            a
            <span class="font-semibold text-gray-800">
                {{ $paginator->lastItem() ?? 0 }}
            </span>
            de
            <span class="font-semibold text-gray-800">
                {{ $paginator->total() }}
            </span>
            registros
        </div>

        {{-- Paginación móvil --}}
        <div class="flex items-center justify-between gap-3 sm:hidden">

            @if ($paginator->onFirstPage())
                <span class="px-4 py-2 text-sm font-medium text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed">
                    Anterior
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}"
                   rel="prev"
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-blue-50 hover:text-[#1a34c9] transition">
                    Anterior
                </a>
            @endif

            <span class="text-sm font-medium text-gray-600">
                Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}
            </span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}"
                   rel="next"
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-blue-50 hover:text-[#1a34c9] transition">
                    Siguiente
                </a>
            @else
                <span class="px-4 py-2 text-sm font-medium text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed">
                    Siguiente
                </span>
            @endif

        </div>

        {{-- Paginación escritorio --}}
        <div class="hidden sm:flex items-center gap-1.5">

            {{-- Anterior --}}
            @if ($paginator->onFirstPage())
                <span class="flex items-center justify-center w-9 h-9 text-gray-300 rounded-lg cursor-not-allowed">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}"
                   rel="prev"
                   aria-label="Página anterior"
                   class="flex items-center justify-center w-9 h-9 text-gray-600 rounded-lg hover:bg-blue-50 hover:text-[#1a34c9] transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
            @endif

            {{-- Números de página --}}
            @foreach ($elements as $element)

                {{-- Separador de páginas --}}
                @if (is_string($element))
                    <span class="flex items-center justify-center w-9 h-9 text-sm text-gray-400">
                        {{ $element }}
                    </span>
                @endif

                {{-- Enlaces --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)

                        @if ($page == $paginator->currentPage())
                            <span aria-current="page"
                                  class="flex items-center justify-center w-9 h-9 text-sm font-semibold text-white bg-[#1a34c9] rounded-lg shadow-sm">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}"
                               class="flex items-center justify-center w-9 h-9 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 hover:text-[#1a34c9] transition">
                                {{ $page }}
                            </a>
                        @endif

                    @endforeach
                @endif

            @endforeach

            {{-- Siguiente --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}"
                   rel="next"
                   aria-label="Página siguiente"
                   class="flex items-center justify-center w-9 h-9 text-gray-600 rounded-lg hover:bg-blue-50 hover:text-[#1a34c9] transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            @else
                <span class="flex items-center justify-center w-9 h-9 text-gray-300 rounded-lg cursor-not-allowed">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </span>
            @endif

        </div>
    </nav>
@endif
