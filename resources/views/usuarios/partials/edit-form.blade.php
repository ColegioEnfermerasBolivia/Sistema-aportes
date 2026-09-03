<form
    method="POST"
    action="{{ route('usuarios.update', $usuario) }}"
>
    @csrf
    @method('PUT')

    <div>
        <label for="edit_ci_{{ $usuario->id }}">
            CI:
        </label>

        <input
            type="text"
            id="edit_ci_{{ $usuario->id }}"
            name="ci"
            value="{{ old('ci', $usuario->ci) }}"
            required
        >

        @error('ci')
            <div>{{ $message }}</div>
        @enderror
    </div>

    <br>

    <div>
        <label for="edit_nombres_{{ $usuario->id }}">
            Nombres:
        </label>

        <input
            type="text"
            id="edit_nombres_{{ $usuario->id }}"
            name="nombres"
            value="{{ old('nombres', $usuario->nombres) }}"
            required
        >

        @error('nombres')
            <div>{{ $message }}</div>
        @enderror
    </div>

    <br>

    <div>
        <label for="edit_apellidos_{{ $usuario->id }}">
            Apellidos:
        </label>

        <input
            type="text"
            id="edit_apellidos_{{ $usuario->id }}"
            name="apellidos"
            value="{{ old('apellidos', $usuario->apellidos) }}"
            required
        >

        @error('apellidos')
            <div>{{ $message }}</div>
        @enderror
    </div>

    <br>

    <div>
        <label for="edit_username_{{ $usuario->id }}">
            Nombre de usuario:
        </label>

        <input
            type="text"
            id="edit_username_{{ $usuario->id }}"
            name="username"
            value="{{ old('username', $usuario->username) }}"
            required
        >

        @error('username')
            <div>{{ $message }}</div>
        @enderror
    </div>

    <br>

    <div>
        <label for="edit_cel_{{ $usuario->id }}">
            Celular:
        </label>

        <input
            type="text"
            id="edit_cel_{{ $usuario->id }}"
            name="cel"
            value="{{ old('cel', $usuario->cel) }}"
            required
        >

        @error('cel')
            <div>{{ $message }}</div>
        @enderror
    </div>

    <br>

    {{-- ROL --}}

    @if (auth()->id() === $usuario->id)

        <div>
            <strong>Rol:</strong>
            {{ $usuario->rol?->nombre }}

            <input
                type="hidden"
                name="rol_id"
                value="{{ $usuario->rol_id }}"
            >
        </div>

    @else

        <div>
            <fieldset>
                <legend>Rol</legend>

                @foreach ($roles as $rol)
                    <label>
                        <input
                            type="radio"
                            name="rol_id"
                            value="{{ $rol->id }}"
                            {{ old('rol_id', $usuario->rol_id) == $rol->id ? 'checked' : '' }}
                        >

                        {{ $rol->nombre }}
                    </label>

                    <br>
                @endforeach
            </fieldset>

            @error('rol_id')
                <div>{{ $message }}</div>
            @enderror
        </div>

    @endif

    <br>

    {{-- COLEGIO --}}

    @if (auth()->user()->rol?->nombre === 'SUPER ADMIN')

        <div>
            <label for="edit_colegio_{{ $usuario->id }}">
                Colegio:
            </label>

            <select
                id="edit_colegio_{{ $usuario->id }}"
                name="colegio_id"
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
                <div>{{ $message }}</div>
            @enderror
        </div>

    @else

        <div>
            <strong>Colegio:</strong>

            {{ $usuario->colegio?->nombre ?? 'Sin colegio' }}

            <input
                type="hidden"
                name="colegio_id"
                value="{{ $usuario->colegio_id }}"
            >
        </div>

    @endif

    <br>

    {{-- ESTADO --}}

    <div>
        <label for="edit_estado_{{ $usuario->id }}">
            Estado:
        </label>

        @if (auth()->id() === $usuario->id)

            <div>
                {{ $usuario->estado }}

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
            <div>{{ $message }}</div>
        @enderror
    </div>

    <br>

    <button type="submit">
        Guardar cambios
    </button>
</form>