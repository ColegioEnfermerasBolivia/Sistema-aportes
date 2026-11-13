<form
    method="POST"
    action="{{ route('usuarios.update', $usuario) }}"
    class="space-y-6"
>
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
        
        {{-- CI --}}
        <div>
    
            <label
                for="edit_ci_{{ $usuario->id }}"
                class="mb-2 block text-sm font-medium text-gray-800"
            >
                CI
            </label>
    
            <input
                type="text"
                id="edit_ci_{{ $usuario->id }}"
                name="ci"
                value="{{ old('ci', $usuario->ci) }}"
                required
                class="w-full rounded-lg border border-gray-300
                       bg-white px-4 py-3
                       text-sm text-gray-900
                       outline-none transition
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
                for="edit_nombres_{{ $usuario->id }}"
                class="mb-2 block text-sm font-medium text-gray-800"
            >
                Nombres
            </label>
    
            <input
                type="text"
                id="edit_nombres_{{ $usuario->id }}"
                name="nombres"
                value="{{ old('nombres', $usuario->nombres) }}"
                required
                class="w-full rounded-lg border border-gray-300
                       bg-white px-4 py-3
                       text-sm text-gray-900
                       outline-none transition
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
                for="edit_apellidos_{{ $usuario->id }}"
                class="mb-2 block text-sm font-medium text-gray-800"
            >
                Apellidos
            </label>
    
            <input
                type="text"
                id="edit_apellidos_{{ $usuario->id }}"
                name="apellidos"
                value="{{ old('apellidos', $usuario->apellidos) }}"
                required
                class="w-full rounded-lg border border-gray-300
                       bg-white px-4 py-3
                       text-sm text-gray-900
                       outline-none transition
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
                for="edit_username_{{ $usuario->id }}"
                class="mb-2 block text-sm font-medium text-gray-800"
            >
                Nombre de usuario
            </label>
    
            <input
                type="text"
                id="edit_username_{{ $usuario->id }}"
                name="username"
                value="{{ old('username', $usuario->username) }}"
                required
                class="w-full rounded-lg border border-gray-300
                       bg-white px-4 py-3
                       text-sm text-gray-900
                       outline-none transition
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
                for="edit_cel_{{ $usuario->id }}"
                class="mb-2 block text-sm font-medium text-gray-800"
            >
                Celular
            </label>
    
            <input
                type="text"
                id="edit_cel_{{ $usuario->id }}"
                name="cel"
                value="{{ old('cel', $usuario->cel) }}"
                required
                class="w-full rounded-lg border border-gray-300
                       bg-white px-4 py-3
                       text-sm text-gray-900
                       outline-none transition
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
    @if (auth()->id() === $usuario->id)

        <div class="rounded-lg bg-gray-50 px-4 py-3">

            <p class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500">
                Rol
            </p>

            <p class="text-sm font-semibold text-gray-800">
                {{ $usuario->rol?->nombre }}
            </p>

            <input
                type="hidden"
                name="rol_id"
                value="{{ $usuario->rol_id }}"
            >

        </div>

    @else

        <div>

            <fieldset>

                <legend class="mb-3 text-sm font-medium text-gray-800">
                    Rol
                </legend>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">

                    @foreach ($roles as $rol)

                        <label
                            class="flex cursor-pointer items-center gap-3
                                   rounded-lg border border-gray-300
                                   bg-white px-4 py-3
                                   transition hover:bg-gray-50"
                        >

                            <input
                                type="radio"
                                name="rol_id"
                                value="{{ $rol->id }}"
                                {{ old('rol_id', $usuario->rol_id) == $rol->id ? 'checked' : '' }}
                                class="h-4 w-4 accent-[#1a34c9]"
                            >

                            <span class="text-sm text-gray-800">
                                {{ $rol->nombre }}
                            </span>

                        </label>

                    @endforeach

                </div>

            </fieldset>

            @error('rol_id')
                <p class="mt-1.5 text-xs text-red-600">
                    {{ $message }}
                </p>
            @enderror

        </div>

    @endif


    {{-- COLEGIO --}}
    @if (auth()->user()->rol?->nombre === 'SUPER ADMIN')

        <div>

            <label
                for="edit_colegio_{{ $usuario->id }}"
                class="mb-2 block text-sm font-medium text-gray-800"
            >
                Colegio
            </label>

            <select
                id="edit_colegio_{{ $usuario->id }}"
                name="colegio_id"
                class="w-full rounded-lg border border-gray-300
                       bg-white px-4 py-3
                       text-sm text-gray-900
                       outline-none transition
                       focus:border-[#1a34c9]
                       focus:ring-2
                       focus:ring-[#1a34c9]/15"
            >

                <option value="">
                    Sin colegio
                </option>

                @foreach ($colegios as $colegio)

                    <option
                        value="{{ $colegio->id }}"
                        {{ old('colegio_id', $usuario->colegio_id) == $colegio->id ? 'selected' : '' }}
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

    @else

        <div class="rounded-lg bg-gray-50 px-4 py-3">

            <p class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500">
                Colegio
            </p>

            <p class="text-sm font-semibold text-gray-800">
                {{ $usuario->colegio?->nombre ?? 'Sin colegio' }}
            </p>

            <input
                type="hidden"
                name="colegio_id"
                value="{{ $usuario->colegio_id }}"
            >

        </div>

    @endif


    {{-- ESTADO --}}
    <div>

        <label
            for="edit_estado_{{ $usuario->id }}"
            class="mb-2 block text-sm font-medium text-gray-800"
        >
            Estado
        </label>

        @if (auth()->id() === $usuario->id)

            <div class="rounded-lg bg-gray-50 px-4 py-3">

                <p class="text-sm font-semibold text-gray-800">
                    {{ $usuario->estado }}
                </p>

                <input
                    type="hidden"
                    name="estado"
                    value="{{ $usuario->estado }}"
                >

            </div>

        @else

            <select
                id="edit_estado_{{ $usuario->id }}"
                name="estado"
                class="w-full rounded-lg border border-gray-300
                       bg-white px-4 py-3
                       text-sm text-gray-900
                       outline-none transition
                       focus:border-[#1a34c9]
                       focus:ring-2
                       focus:ring-[#1a34c9]/15"
            >

                <option
                    value="ACTIVO"
                    {{ old('estado', $usuario->estado) === 'ACTIVO' ? 'selected' : '' }}
                >
                    ACTIVO
                </option>

                <option
                    value="INACTIVO"
                    {{ old('estado', $usuario->estado) === 'INACTIVO' ? 'selected' : '' }}
                >
                    INACTIVO
                </option>

            </select>

        @endif

        @error('estado')
            <p class="mt-1.5 text-xs text-red-600">
                {{ $message }}
            </p>
        @enderror

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
            class="rounded-lg border border-[#6B7280]
                    px-7 py-3
                   text-sm font-semibold text-[#6B7280]
                   transition
                   hover:bg-[#dcdde0] flex gap-1.5
                   hover:cursor-pointer
                   "
        >
            <span class="flex h-5 w-5 shrink-0 items-center justify-center text-base">
                <x-icon name="cancel" class="text-[#6B7280]" size="35"/>
            </span>
            Cancelar
        </button>

        <button
            type="submit"
            class="rounded-lg bg-[#16A34A]
                   px-7 py-3
                   text-sm font-semibold text-white
                   shadow-[0_4px_12px_rgba(26,52,201,0.25)]
                   transition
                   hover:bg-[#046d04]
                   hover:cursor-pointer
                   focus:outline-none
                   focus:ring-2
                   focus:ring-[#1a34c9]/30
                   focus:ring-offset-2 flex gap-1.5"
        >
        <span class="flex h-5 w-5 shrink-0 items-center justify-center text-base">
            <x-icon name="save" class="text-white" size="35"/>
        </span>
            Guardar cambios
        </button>

    </div>

</form>