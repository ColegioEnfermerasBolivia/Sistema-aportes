<form method="POST" action="{{ route('usuarios.store') }}">
    @csrf

    <div>
        <label for="ci">CI:</label>

        <input
            type="text"
            id="ci"
            name="ci"
            value="{{ old('ci') }}"
            required
        >

        @error('ci')
            <div>{{ $message }}</div>
        @enderror
    </div>

    <br>

    <div>
        <label for="nombres">Nombres:</label>

        <input
            type="text"
            id="nombres"
            name="nombres"
            value="{{ old('nombres') }}"
            required
        >

        @error('nombres')
            <div>{{ $message }}</div>
        @enderror
    </div>

    <br>

    <div>
        <label for="apellidos">Apellidos:</label>

        <input
            type="text"
            id="apellidos"
            name="apellidos"
            value="{{ old('apellidos') }}"
            required
        >

        @error('apellidos')
            <div>{{ $message }}</div>
        @enderror
    </div>

    <br>

    <div>
        <label for="username">Nombre de usuario:</label>

        <input
            type="text"
            id="username"
            name="username"
            value="{{ old('username') }}"
            required
        >

        @error('username')
            <div>{{ $message }}</div>
        @enderror
    </div>

    <br>

    <div>
        <label for="cel">Celular:</label>

        <input
            type="text"
            id="cel"
            name="cel"
            value="{{ old('cel') }}"
            required
        >

        @error('cel')
            <div>{{ $message }}</div>
        @enderror
    </div>

    <br>

    <div x-data="{ rol: '{{ old('rol_id') }}' }">

        <fieldset>
            <legend>Rol</legend>

            <div>
                <label>
                    <input
                        type="radio"
                        name="rol_id"
                        value="1"
                        x-model="rol"
                    >
                    SUPER ADMIN
                </label>
            </div>

            <div>
                <label>
                    <input
                        type="radio"
                        name="rol_id"
                        value="2"
                        x-model="rol"
                    >
                    ADMIN
                </label>
            </div>

            <div>
                <label>
                    <input
                        type="radio"
                        name="rol_id"
                        value="3"
                        x-model="rol"
                    >
                    USUARIO
                </label>
            </div>
        </fieldset>

        <br>

        <div
            x-show="rol === '3'"
            x-cloak
        >
            <label for="colegio_id">Colegio:</label>

            <select
                id="colegio_id"
                name="colegio_id"
                :required="rol === '3'"
            >
                <option value="">Seleccione un colegio</option>

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
                <div>{{ $message }}</div>
            @enderror
        </div>

    </div>

    <br>

    <button type="submit">
        Crear usuario
    </button>
</form>