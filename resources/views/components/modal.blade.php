@props([
    'titulo',
    'abiertoInicial' => false,
])

<div x-data="{ abierto: {{ $abiertoInicial ? 'true' : 'false' }} }">

    @isset($trigger)
        <div @click="abierto = true">
            {{ $trigger }}
        </div>
    @endisset

    <div
        x-show="abierto"
        x-cloak
        class="modal-overlay"
    >
        <div
            @click.outside="abierto = false"
            class="modal"
        >
            <div class="modal-header">
                <h2>{{ $titulo }}</h2>

                <button
                    type="button"
                    @click="abierto = false"
                >
                    ×
                </button>
            </div>

            <div class="modal-body">
                {{ $slot }}
            </div>
        </div>
    </div>

</div>