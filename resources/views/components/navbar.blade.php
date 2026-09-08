<aside class="flex h-screen w-[280px] shrink-0 flex-col border-r border-gray-200 bg-white px-5 py-[30px] font-sans">

    {{-- HEADER --}}
    <div class="mb-[30px] text-center">

        <div class="mx-auto mb-3.5 flex h-16 w-16 items-center justify-center rounded-full border-[3px] border-[#1a34c9] text-[26px] text-[#1a34c9]">
            ⚕
        </div>

        <h2 class="m-0 text-xl font-extrabold leading-tight text-[#0d1b6e]">
            Colegio de Enfermeras
        </h2>

        <span class="mt-1 block text-[11px] font-bold uppercase tracking-[2px] text-gray-600">
            Sistema de Gestión
        </span>

    </div>


    {{-- NAVEGACIÓN --}}
    <nav class="flex flex-1 flex-col gap-2.5 overflow-y-auto">

        {{-- Dashboard --}}
        <a
            href="{{ route('dashboard') }}"
            class="{{ request()->routeIs('dashboard')
                ? 'bg-[#1a34c9] text-white font-semibold shadow-[0_6px_16px_rgba(26,52,201,0.35)]'
                : 'text-[#2b2b2b] hover:bg-[#f7f7f9]'
            }} flex items-center gap-3.5 rounded-xl px-[18px] py-3.5 text-[15px] font-medium no-underline transition"
        >
            <span class="flex h-5 w-5 shrink-0 items-center justify-center text-base">
                ⌂
            </span>

            <span class="whitespace-nowrap">
                Dashboard
            </span>
        </a>


        {{-- Afiliados --}}
        <a
            href="{{ route('afiliados.index') }}"
            class="{{ request()->routeIs('afiliados.*')
                ? 'bg-[#1a34c9] text-white font-semibold shadow-[0_6px_16px_rgba(26,52,201,0.35)]'
                : 'text-[#2b2b2b] hover:bg-[#f7f7f9]'
            }} flex items-center gap-3.5 rounded-xl px-[18px] py-3.5 text-[15px] font-medium no-underline transition"
        >
            <span class="flex h-5 w-5 shrink-0 items-center justify-center text-base">
                ♙
            </span>

            <span class="whitespace-nowrap">
                Afiliados
            </span>
        </a>


        {{-- Usuarios --}}
        @if (auth()->user()->rol->nombre !== 'USUARIO')

            <a
                href="{{ route('usuarios.index') }}"
                class="{{ request()->routeIs('usuarios.*')
                    ? 'bg-[#1a34c9] text-white font-semibold shadow-[0_6px_16px_rgba(26,52,201,0.35)]'
                    : 'text-[#2b2b2b] hover:bg-[#f7f7f9]'
                }} flex items-center gap-3.5 rounded-xl px-[18px] py-3.5 text-[15px] font-medium no-underline transition"
            >
                <span class="flex h-5 w-5 shrink-0 items-center justify-center text-base">
                    ♟
                </span>

                <span class="whitespace-nowrap">
                    Usuarios
                </span>
            </a>

        @endif

    </nav>


    {{-- FOOTER --}}
    <div class="mt-5 border-t border-gray-200 pt-4">

        <div class="mb-3 px-2">

            <p class="m-0 text-sm font-semibold text-gray-800">
                {{ auth()->user()->nombres }}
                {{ auth()->user()->apellidos }}
            </p>

            <p class="mt-1 text-xs text-gray-500">
                {{ auth()->user()->rol->nombre }}
            </p>

            @if (auth()->user()->colegio)
                <p class="mt-1 text-xs text-gray-500">
                    {{ auth()->user()->colegio->nombre }}
                </p>
            @endif

        </div>


        {{-- Cerrar sesión --}}
        <form
            method="POST"
            action="{{ route('logout') }}"
        >
            @csrf

            <button
                type="submit"
                class="flex w-full cursor-pointer items-center justify-center gap-2.5 rounded-xl border-0 bg-[#f7f7f9] px-[18px] py-[13px] text-[15px] font-semibold text-[#d64545] transition hover:bg-[#fbe4e4]"
            >
                <span class="flex h-5 w-5 items-center justify-center text-base">
                    ↩
                </span>

                Cerrar sesión
            </button>

        </form>

    </div>

</aside>