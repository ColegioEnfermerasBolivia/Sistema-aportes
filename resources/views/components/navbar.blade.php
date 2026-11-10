<aside class="flex h-screen w-70 shrink-0 flex-col border-r border-gray-200 bg-white px-5 py-7.5 font-sans">

    {{-- HEADER --}}
    <div class="mb-7.5 text-center">
        <div class="mx-auto mb-3.5 flex h-26 w-26 items-center justify-center text-[#1a34c9]">
            <img
                        src="{{ asset('images/logo.png') }}"
                        alt="CNEB"
                        class="mx-auto mb-3 h-25 w-25"
                    >
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
            }} flex items-center gap-3.5 rounded-xl px-4.5 py-3.5 text-[15px] font-medium no-underline transition"
        >
            <span class="flex h-5 w-5 shrink-0 items-center justify-center text-base">
                <x-icon name="dashboard" size="35"/>
            </span>

            <span class="whitespace-nowrap">
                Dashboard
            </span>
        </a>

        {{-- Usuarios --}}
        @if (auth()->user()->rol->nombre !== 'USUARIO')

            <a
                href="{{ route('usuarios.index') }}"
                class="{{ request()->routeIs('usuarios.*')
                    ? 'bg-[#1a34c9] text-white font-semibold shadow-[0_6px_16px_rgba(26,52,201,0.35)]'
                    : 'text-[#2b2b2b] hover:bg-[#f7f7f9]'
                }} flex items-center gap-2.5 rounded-xl px-4.5 py-3.5 text-[15px] font-medium no-underline transition"
            >
                <span class="flex h-5 w-5 shrink-0 items-center justify-center text-base">
                    <x-icon name="users" size="35" />
                </span>

                <span class="whitespace-nowrap">
                    Usuarios
                </span>
            </a>
        @endif

        {{-- Afiliados --}}
        <a
            href="{{ route('afiliados.index') }}"
            class="{{ request()->routeIs('afiliados.*')
                ? 'bg-[#1a34c9] text-white font-semibold shadow-[0_6px_16px_rgba(26,52,201,0.35)]'
                : 'text-[#2b2b2b] hover:bg-[#f7f7f9]'
            }} flex items-center gap-3.5 rounded-xl px-4.5 py-3.5 text-[15px] font-medium no-underline transition"
        >
            <span class="flex h-5 w-5 shrink-0 items-center justify-center text-base">
                <x-icon name="afiliados" size="35" />
            </span>
            <span class="whitespace-nowrap">
                Afiliados
            </span>
        </a>

        {{-- Aportes --}}
        <details
            class="group"
            {{ request()->routeIs('aportes.*', 'lotes.*') ? 'open' : '' }}
        >
            <summary
                class="{{ request()->routeIs('aportes.*', 'lotes.*')
                    ? 'bg-[#1a34c9] text-white font-semibold shadow-[0_6px_16px_rgba(26,52,201,0.35)]'
                    : 'text-[#2b2b2b] hover:bg-[#f7f7f9]'
                }} flex cursor-pointer list-none items-center gap-3.5 rounded-xl px-4.5 py-3.5 text-[15px] font-medium transition"
            >
                <span class="flex h-5 w-5 shrink-0 items-center justify-center text-base">
                    <x-icon name="aporte" size="35" />
                </span>

                <span class="flex-1 whitespace-nowrap">
                    Aportes
                </span>

                <span class="text-xs transition-transform group-open:rotate-180">
                    ▼
                </span>
            </summary>

            <div class="mt-1.5 flex flex-col gap-1 pl-11">

                {{-- Ver aportes --}}
                <a
                    href={{ route('aportes.index') }}
                    class="{{ request()->routeIs('aportes.index')
                        ? 'font-semibold text-[#1a34c9]'
                        : 'text-[#555] hover:text-[#1a34c9]'
                    }} rounded-lg px-3 py-2 text-sm no-underline transition flex gap-1.5"
                >
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center text-base">
                        <x-icon name="money" size="35"/>
                    </span>
                    Ver aportes
                </a>

                {{-- Ver lotes --}}
                <a
                    href="{{ route('lotes.index') }}"
                    class="{{ request()->routeIs('lotes.index')
                        ? 'font-semibold text-[#1a34c9]'
                        : 'text-[#555] hover:text-[#1a34c9]'
                    }} rounded-lg px-3 py-2 text-sm no-underline transition flex gap-0.5"
                >
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center text-base">
                        <x-icon name="lote" size="35"/>
                    </span>
                    Ver lotes activos
                </a>

                {{-- Ver Historial de lotes --}}
                <a 
                    href="{{ route('lotes.historial') }}"
                    class="{{ request()->routeIs('lotes.historial')
                        ? 'font-semibold text-[#1a34c9]'
                        : 'text-[#555] hover:text-[#1a34c9]'
                    }} rounded-lg px-3 py-2 text-sm no-underline transition flex gap-0.5"
                >
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center text-base">
                        <x-icon name="history" size="25"/>
                    </span>
                    Ver historial de lotes
                </a>
            </div>
        </details>
        {{-- Informes --}}
        <details
            class="group"
            {{ request()->routeIs('afiliadosReports.*', 'aportesReports.*', 'lotesReports.*') ? 'open' : '' }}
        >
            <summary
                class="{{request()->routeIs('reports.*', 'afiliadosReports.*', 'aportesReports.*', 'lotesReports.*')
                    ? 'bg-[#1a34c9] text-white font-semibold shadow-[0_6px_16px_rgba(26,52,201,0.35)]'
                    : 'text-[#2b2b2b] hover:bg-[#f7f7f9]'
                }} flex cursor-pointer list-none items-center gap-3.5 rounded-xl px-4.5 py-3.5 text-[15px] font-medium transition"
            >
                <span class="flex h-5 w-5 shrink-0 items-center justify-center text-base">
                    <x-icon name="report" size="35" />
                </span>

                <span class="flex-1 whitespace-nowrap">
                    Reportes
                </span>

                <span class="text-xs transition-transform group-open:rotate-180">
                    ▼
                </span>
            </summary>

            <div class="mt-1.5 flex flex-col gap-1 pl-11">

                {{-- Ver informe de afiliados --}}
                <a
                    href="{{ route('afiliadosReports.index') }}"
                    class="{{request()->routeIs('afiliadosReports.index')
                        ? 'font-semibold text-[#1a34c9]'
                        : 'text-[#555] hover:text-[#1a34c9]'
                    }} rounded-lg px-3 py-2 text-sm no-underline transition flex gap-1.5"
                >
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center text-base">
                        <x-icon name="userInforme" size="35"/>
                    </span>
                    Informes de Afiliados
                </a>

                {{-- Ver informacion de aportes --}}
                <a
                    href="{{ route('aportesReports.index') }}"
                    class="{{ request()->routeIs('aportesReports.index')
                        ? 'font-semibold text-[#1a34c9]'
                        : 'text-[#555] hover:text-[#1a34c9]'
                    }} rounded-lg px-3 py-2 text-sm no-underline transition flex gap-0.5"
                >
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center text-base">
                        <x-icon name="userAportes" size="35"/>
                    </span>
                    Informes de Aportes
                </a>

                {{-- Reporte de pagos por lote --}}
                <a
                    href="{{ route('lotesReports.index') }}"
                    class="{{request()->routeIs('lotesReports.*')
                        ? 'font-semibold text-[#1a34c9]'
                        : 'text-[#555] hover:text-[#1a34c9]'
                    }} rounded-lg px-3 py-2 text-sm no-underline transition flex gap-1.5"
                >
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center text-base">
                        <x-icon name="report" size="30"/>
                    </span>
                    Pagos por lote
                </a>
            </div>
        </details>
        {{-- Carga de aportes --}}
        @if (auth()->user()->rol->nombre == 'SUPER ADMIN')

            <a
                href="{{ route('carga-datos.index') }}"
                class="{{ request()->routeIs('carga-datos.*')
                    ? 'bg-[#1a34c9] text-white font-semibold shadow-[0_6px_16px_rgba(26,52,201,0.35)]'
                    : 'text-[#2b2b2b] hover:bg-[#f7f7f9]'
                }} flex items-center gap-2.5 rounded-xl px-4.5 py-3.5 text-[15px] font-medium no-underline transition"
            >
                <span class="flex h-5 w-5 shrink-0 items-center justify-center text-base">
                    <x-icon name="upload" size="35" />
                </span>

                <span class="whitespace-nowrap">
                    Cargar Datos
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
                class="flex w-full cursor-pointer items-center justify-center gap-2.5 rounded-xl border-0 bg-[#f7f7f9] px-4.5 py-3.25 text-[15px] font-semibold text-[#d64545] transition hover:bg-[#fbe4e4]"
            >
                <span class="flex h-5 w-5 items-center justify-center text-base">
                    <x-icon name="logout" size="25"/>
                </span>
                Cerrar sesión
            </button>
        </form>
    </div>
</aside>