@props([
    'titulo',
    'tituloAlpine' => null,
    'abiertoInicial' => false,
    'ancho' => 'max-w-2xl',
])

<div
    x-data="{ abierto: @js($abiertoInicial) }"
    x-modelable="abierto"
    {{ $attributes }}
    @keydown.escape.window="abierto = false"
>
    {{-- TRIGGER --}}
    @isset($trigger)

        <div @click="abierto = true">
            {{ $trigger }}
        </div>

    @endisset

    {{-- MODAL TELETRANSPORTADO AL BODY --}}
    <template x-teleport="body">
        <div
            x-show="abierto"
            x-cloak
            x-transition.opacity.duration.200ms
            class="fixed inset-0 z-[9999] flex items-center justify-center
                   bg-gray-900/55 px-4 py-6
                   backdrop-blur-[3px]"
        >

            {{-- CONTENEDOR DEL MODAL --}}
            <div
                @click.outside="abierto = false"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="scale-95 opacity-0"
                x-transition:enter-end="scale-100 opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="scale-100 opacity-100"
                x-transition:leave-end="scale-95 opacity-0"
                class="flex w-full {{ $ancho }} max-h-[90vh] flex-col
                       overflow-hidden rounded-2xl
                       border border-gray-200
                       border-t-[4px] border-t-[#1a34c9]
                       bg-white
                       shadow-[0_25px_60px_rgba(0,0,0,0.25)]"
            >

                {{-- HEADER --}}
                <div
                    class="flex shrink-0 items-center justify-between
                           border-b border-gray-200
                           bg-white px-8 py-6"
                >
                    <h2
                        class="m-0 text-2xl font-semibold tracking-tight text-gray-900"
                    >
                        @if ($tituloAlpine)
                            <span x-text="{{ $tituloAlpine }}">{{ $titulo }}</span>
                        @else
                            {{ $titulo }}
                        @endif
                    </h2>
                    <button
                        type="button"
                        @click="abierto = false"
                        class="flex h-9 w-9 items-center justify-center
                               rounded-full
                               text-3xl font-light leading-none
                               text-gray-500
                               transition
                               hover:bg-gray-100
                               hover:text-gray-800
                               focus:outline-none
                               focus:ring-2
                               focus:ring-[#1a34c9]/30 hover:cursor-pointer"
                        aria-label="Cerrar"
                    >
                        <x-icon name="x" size="35"/>
                    </button>
                </div>

                {{-- BODY --}}
                <div
                    class="min-h-0 overflow-y-auto bg-white px-8 py-7"
                >
                    {{ $slot }}
                </div>
            </div>
        </div>
    </template>
</div>