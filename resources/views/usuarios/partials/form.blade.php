<form
    method="POST"
    action="{{ route('usuarios.store') }}"
    class="space-y-6"
>
    @csrf

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
        
        {{-- CI --}}
        <div>
            <label
                for="ci"
                class="mb-2 block text-sm font-medium text-gray-800"
            >
                CI
            </label>
    
            <input
                type="text"
                id="ci"
                name="ci"
                value="{{ old('ci') }}"
                required
                class="w-full rounded-lg border border-gray-300
                       bg-white px-4 py-3
                       text-sm text-gray-900
                       outline-none
                       transition
                       placeholder:text-gray-400
                       focus:border-[#1a34c9]
                       focus:ring-2
                       focus:ring-[#1a34c9]/15"
            >
    
            @error('ci')
                <p class="mt-1.5 text-xs text-red-600">
                    {{ $message }}
                </p>
            @enderror
        </div>

        {{-- NOMBRES --}}
        <div>
    
            <label
                for="nombres"
                class="mb-2 block text-sm font-medium text-gray-800"
            >
                Nombres
            </label>
    
            <input
                type="text"
                id="nombres"
                name="nombres"
                value="{{ old('nombres') }}"
                required
                class="w-full rounded-lg border border-gray-300
                       bg-white px-4 py-3
                       text-sm text-gray-900
                       outline-none
                       transition
                       placeholder:text-gray-400
                       focus:border-[#1a34c9]
                       focus:ring-2
                       focus:ring-[#1a34c9]/15"
            >
    
            @error('nombres')
                <p class="mt-1.5 text-xs text-red-600">
                    {{ $message }}
                </p>
            @enderror
    
        </div>
        
        {{-- APELLIDOS --}}
        <div>
    
            <label
                for="apellidos"
                class="mb-2 block text-sm font-medium text-gray-800"
            >
                Apellidos
            </label>
    
            <input
                type="text"
                id="apellidos"
                name="apellidos"
                value="{{ old('apellidos') }}"
                required
                class="w-full rounded-lg border border-gray-300
                       bg-white px-4 py-3
                       text-sm text-gray-900
                       outline-none
                       transition
                       placeholder:text-gray-400
                       focus:border-[#1a34c9]
                       focus:ring-2
                       focus:ring-[#1a34c9]/15"
            >
    
            @error('apellidos')
                <p class="mt-1.5 text-xs text-red-600">
                    {{ $message }}
                </p>
            @enderror
    
        </div>
        
        {{-- NOMBRE DE USUARIO --}}
        <div>
    
            <label
                for="username"
                class="mb-2 block text-sm font-medium text-gray-800"
            >
                Nombre de usuario
            </label>
    
            <input
                type="text"
                id="username"
                name="username"
                value="{{ old('username') }}"
                required
                class="w-full rounded-lg border border-gray-300
                       bg-white px-4 py-3
                       text-sm text-gray-900
                       outline-none
                       transition
                       placeholder:text-gray-400
                       focus:border-[#1a34c9]
                       focus:ring-2
                       focus:ring-[#1a34c9]/15"
            >
    
            @error('username')
                <p class="mt-1.5 text-xs text-red-600">
                    {{ $message }}
                </p>
            @enderror
    
        </div>
    
        {{-- CELULAR --}}
        <div>
    
            <label
                for="cel"
                class="mb-2 block text-sm font-medium text-gray-800"
            >
                Celular
            </label>
    
            <input
                type="text"
                id="cel"
                name="cel"
                value="{{ old('cel') }}"
                required
                class="w-full rounded-lg border border-gray-300
                       bg-white px-4 py-3
                       text-sm text-gray-900
                       outline-none
                       transition
                       placeholder:text-gray-400
                       focus:border-[#1a34c9]
                       focus:ring-2
                       focus:ring-[#1a34c9]/15"
            >
    
            @error('cel')
                <p class="mt-1.5 text-xs text-red-600">
                    {{ $message }}
                </p>
            @enderror
    
        </div>
    
        
    </div>
    
    {{-- ROL --}}
    <div
        x-data="{ rol: '{{ old('rol_id') }}' }"
        class="pt-1"
    >    
        <fieldset>

            <legend class="mb-3 text-sm font-medium text-gray-800">
                Rol
            </legend>

            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">

                @if (auth()->user()->rol?->nombre === 'SUPER ADMIN')
                <label
                    class="flex cursor-pointer items-center gap-3
                           rounded-lg border border-gray-300
                           bg-white px-4 py-3
                           transition
                           hover:bg-gray-50"
                    :class="rol === '1'
                        ? 'border-[#1a34c9] bg-[#f4f6ff] ring-1 ring-[#1a34c9]/20'
                        : ''"
                >

                    <input
                        type="radio"
                        name="rol_id"
                        value="1"
                        x-model="rol"
                        class="h-4 w-4 accent-[#1a34c9]"
                    >

                    <span class="text-sm text-gray-800">
                        SUPER ADMIN
                    </span>

                </label>
                @endif


                {{-- ADMIN --}}
                <label
                    class="flex cursor-pointer items-center gap-3
                           rounded-lg border border-gray-300
                           bg-white px-4 py-3
                           transition
                           hover:bg-gray-50"
                    :class="rol === '2'
                        ? 'border-[#1a34c9] bg-[#f4f6ff] ring-1 ring-[#1a34c9]/20'
                        : ''"
                >

                    <input
                        type="radio"
                        name="rol_id"
                        value="2"
                        x-model="rol"
                        class="h-4 w-4 accent-[#1a34c9]"
                    >

                    <span class="text-sm text-gray-800">
                        ADMIN
                    </span>

                </label>


                {{-- USUARIO --}}
                <label
                    class="flex cursor-pointer items-center gap-3
                           rounded-lg border border-gray-300
                           bg-white px-4 py-3
                           transition
                           hover:bg-gray-50"
                    :class="rol === '3'
                        ? 'border-[#1a34c9] bg-[#f4f6ff] ring-1 ring-[#1a34c9]/20'
                        : ''"
                >

                    <input
                        type="radio"
                        name="rol_id"
                        value="3"
                        x-model="rol"
                        class="h-4 w-4 accent-[#1a34c9]"
                    >

                    <span class="text-sm text-gray-800">
                        USUARIO
                    </span>

                </label>

            </div>

        </fieldset>


        {{-- COLEGIO --}}
        <div
            x-show="rol === '3'"
            x-cloak
            x-transition
            class="mt-5"
        >

            <label
                for="colegio_id"
                class="mb-2 block text-sm font-medium text-gray-800"
            >
                Colegio
            </label>

            <select
                id="colegio_id"
                name="colegio_id"
                :required="rol === '3'"
                class="w-full rounded-lg border border-gray-300
                       bg-white px-4 py-3
                       text-sm text-gray-900
                       outline-none
                       transition
                       focus:border-[#1a34c9]
                       focus:ring-2
                       focus:ring-[#1a34c9]/15"
            >

                <option value="">
                    Seleccione un colegio
                </option>

                @foreach ($colegios as $colegio)

                    <option
                        value="{{ $colegio->id }}"
                        {{ old('colegio_id') == $colegio->id ? 'selected' : '' }}
                    >
                        {{ $colegio->nombre }}
                    </option>

                @endforeach

            </select>

            @error('colegio_id')
                <p class="mt-1.5 text-xs text-red-600">
                    {{ $message }}
                </p>
            @enderror

        </div>

    </div>



    {{-- BOTONES --}}
    <div
        class="flex flex-col-reverse gap-3
               border-t border-gray-200
               pt-6
               sm:flex-row sm:justify-end"
    >

        <button
            type="button"
            @click="abierto = false"
            class="rounded-lg border-none
                   bg-red-500 px-7 py-3
                   text-sm font-semibold text-white
                   hover:cursor-pointer"
        >
            <span class="text-xl leading-none">
                <x-icon name="cancel" class="text-white" size="20"/>
            </span>
            Cancelar
        </button>

        <button
            type="submit"
            class="rounded-lg bg-[#1a34c9]
                   px-7 py-3
                   text-sm font-semibold text-white
                   shadow-[0_4px_12px_rgba(26,52,201,0.25)]
                   transition
                   hover:bg-[#1429a3]
                   focus:outline-none
                   focus:ring-2
                   focus:ring-[#1a34c9]/30
                   hover:cursor-pointer
                   focus:ring-offset-2"
        >
            <span class="text-xl leading-none">
                <x-icon name="createUser" class="text-white" size="20"/>
            </span>
            crear usuario
        </button>

    </div>

</form>