<?php

namespace App\Http\Controllers;

use App\Models\Afiliado;
use App\Models\Auditoria;
use App\Models\Colegio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CargaDatosController extends Controller
{
    public function index()
    {
        $datosValidacion = session('carga_datos.afiliados');

        $mostrarPrevisualizacion = session()->has(
            'mostrar_previsualizacion'
        );

        return view('carga-datos.index', [
            'datosValidacion' => $datosValidacion,
            'mostrarPrevisualizacion' => $mostrarPrevisualizacion,
        ]);
    }

    private function normalizarFecha(?string $fecha): ?string
    {
        if ($fecha === null || trim($fecha) === '') {
            return null;
        }

        $fecha = trim($fecha);

        $formatos = [
            'Y-m-d',
            'd/m/Y',
            'd-m-Y',
        ];

        foreach ($formatos as $formato) {

            $fechaValida = \DateTime::createFromFormat(
                $formato,
                $fecha
            );

            $errores = \DateTime::getLastErrors();

            if (
                $fechaValida !== false &&
                (
                    $errores === false ||
                    (
                        $errores['warning_count'] === 0 &&
                        $errores['error_count'] === 0
                    )
                ) &&
                $fechaValida->format($formato) === $fecha
            ) {
                return $fechaValida->format('Y-m-d');
            }
        }

        return null;
    }

    private function normalizarTexto(?string $valor): string
    {
        if ($valor === null) {
            return '';
        }

        $valor = trim($valor);

        // Convierte múltiples espacios consecutivos en uno solo
        $valor = preg_replace('/\s+/', ' ', $valor);

        return mb_strtolower($valor);
    }

    public function validarAfiliados(Request $request)
    {
        $request->validate([
            'archivo_afiliadas' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:10240',
            ],
        ]);

        $archivo = $request->file('archivo_afiliadas');

        $handle = fopen($archivo->getRealPath(), 'r');

        if ($handle === false) {
            return back()->withErrors([
                'archivo_afiliadas' => 'No se pudo leer el archivo.',
            ]);
        }

        try {
            $encabezados = $this->leerEncabezadosCsv($handle);

            $errorEncabezados = $this->validarEncabezados($encabezados);

            if ($errorEncabezados !== null) {
                return back()->withErrors([
                    'archivo_afiliadas' => $errorEncabezados,
                ]);
            }

            $filas = $this->leerFilasCsv(
                $handle,
                $encabezados
            );

            $colegios = $this->obtenerColegiosActivos();

            $datosExistentes = $this->obtenerDatosExistentes();

            $resultados = $this->validarFilas(
                $filas,
                $colegios,
                $datosExistentes
            );

            $datosValidacion = [
                'total' => count($resultados),

                'validas' => collect($resultados)
                    ->where('estado', 'VALIDA')
                    ->count(),

                'errores' => collect($resultados)
                    ->where('estado', 'ERROR')
                    ->count(),

                'filas' => $resultados,
            ];

            session()->put(
                'carga_datos.afiliados',
                $datosValidacion
            );

            return redirect()
                ->route('carga-datos.index')
                ->with('mostrar_previsualizacion', true);

        } finally {
            fclose($handle);
        }
    }

    private function leerEncabezadosCsv($handle): array
    {
        $encabezados = fgetcsv($handle);

        if ($encabezados === false) {
            throw new \RuntimeException(
                'El archivo está vacío o no se pudo leer.'
            );
        }

        // Eliminar BOM UTF-8 del primer encabezado.
        $encabezados[0] = preg_replace(
            '/^\xEF\xBB\xBF/',
            '',
            $encabezados[0]
        );

        return array_map(
            fn ($encabezado) => trim($encabezado),
            $encabezados
        );
    }

    private function validarEncabezados(array $encabezados): ?string
    {
        $encabezadosEsperados = [
            'registro_departamental',
            'matricula_profesional',
            'ci',
            'colegio',
            'nombres',
            'apellidos',
            'celular',
            'fecha_afiliacion',
        ];

        $encabezadosFaltantes = array_diff(
            $encabezadosEsperados,
            $encabezados
        );

        if (!empty($encabezadosFaltantes)) {
            return 'Faltan las siguientes columnas: ' .
                implode(', ', $encabezadosFaltantes);
        }

        $encabezadosDesconocidos = array_diff(
            $encabezados,
            $encabezadosEsperados
        );

        if (!empty($encabezadosDesconocidos)) {
            return 'Las siguientes columnas no son válidas: ' .
                implode(', ', $encabezadosDesconocidos);
        }

        $encabezadosDuplicados = array_diff_assoc(
            $encabezados,
            array_unique($encabezados)
        );

        if (!empty($encabezadosDuplicados)) {
            return 'El archivo contiene columnas duplicadas: ' .
                implode(
                    ', ',
                    array_unique($encabezadosDuplicados)
                );
        }

        return null;
    }

    private function leerFilasCsv($handle, array $encabezados): array
    {
        $filas = [];

        $numeroFila = 1;

        while (($datos = fgetcsv($handle)) !== false) {

            $numeroFila++;

            // Ignorar filas completamente vacías.
            if (
                count($datos) === 1 &&
                trim($datos[0]) === ''
            ) {
                continue;
            }

            $fila = [];

            foreach ($encabezados as $indice => $encabezado) {
                $fila[$encabezado] = isset($datos[$indice])
                    ? trim($datos[$indice])
                    : '';
            }

            $fila['_numero_fila'] = $numeroFila;

            $filas[] = $fila;
        }

        return $filas;
    }

    private function obtenerColegiosActivos()
    {
        return Colegio::query()
            ->where('estado', 'ACTIVO')
            ->get()
            ->keyBy(function ($colegio) {
                return $this->normalizarTexto(
                    $colegio->nombre
                );
            });
    }

    private function obtenerDatosExistentes(): array
    {
        return [
            'registros' => Afiliado::query()
                ->pluck('registro_departamental')
                ->map(fn ($valor) => $this->normalizarTexto($valor))
                ->flip(),

            'matriculas' => Afiliado::query()
                ->pluck('matricula_profesional')
                ->map(fn ($valor) => $this->normalizarTexto($valor))
                ->flip(),

            'cis' => Afiliado::query()
                ->pluck('ci')
                ->map(fn ($valor) => $this->normalizarTexto($valor))
                ->flip(),
        ];
    }

    private function validarFilas(
        array $filas,
        $colegios,
        array $datosExistentes
    ): array {
        $resultados = [];

        $registrosCsv = [];
        $matriculasCsv = [];
        $cisCsv = [];

        foreach ($filas as $fila) {

            $resultados[] = $this->validarFila(
                $fila,
                $colegios,
                $datosExistentes,
                $registrosCsv,
                $matriculasCsv,
                $cisCsv
            );
        }

        return $resultados;
    }

    private function validarFila(
        array $fila,
        $colegios,
        array $datosExistentes,
        array &$registrosCsv,
        array &$matriculasCsv,
        array &$cisCsv
    ): array {
        $errores = [];

        $registro = $this->normalizarTexto(
            $fila['registro_departamental']
        );

        $matricula = $this->normalizarTexto(
            $fila['matricula_profesional']
        );

        $ci = $this->normalizarTexto(
            $fila['ci']
        );

        $nombreColegio = $this->normalizarTexto(
            $fila['colegio']
        );

        // 1. Campos obligatorios.
        $this->validarCamposObligatorios(
            $fila,
            $errores
        );

        // 2. Colegio.
        $colegio = $this->validarColegio(
            $fila,
            $nombreColegio,
            $colegios,
            $errores
        );

        // 3. Duplicados contra la base de datos.
        $this->validarDuplicadosBaseDatos(
            $fila,
            $registro,
            $matricula,
            $ci,
            $datosExistentes,
            $errores
        );

        // 4. Duplicados dentro del CSV.
        $this->validarDuplicadosCsv(
            $fila,
            $registro,
            $matricula,
            $ci,
            $registrosCsv,
            $matriculasCsv,
            $cisCsv,
            $errores
        );

        // 5. Longitudes máximas.
        $this->validarLongitudes(
            $fila,
            $errores
        );

        // 6. Fecha.
        $fechaNormalizada = $this->validarFechaAfiliacion(
            $fila['fecha_afiliacion'],
            $errores
        );

        return [
            'fila' => $fila['_numero_fila'],
            'registro_departamental' => $fila['registro_departamental'],
            'matricula_profesional' => $fila['matricula_profesional'],
            'ci' => $fila['ci'],
            'colegio_id' => $colegio?->id,
            'colegio' => $fila['colegio'],
            'nombres' => $fila['nombres'],
            'apellidos' => $fila['apellidos'],
            'celular' => $fila['celular'],
            'fecha_afiliacion' => $fechaNormalizada,
            'estado' => empty($errores)
                ? 'VALIDA'
                : 'ERROR',
            'errores' => $errores,
        ];
    }

    private function validarCamposObligatorios(
        array $fila,
        array &$errores
    ): void {
        $camposObligatorios = [
            'registro_departamental' => 'Registro departamental',
            'matricula_profesional' => 'Matrícula profesional',
            'ci' => 'CI',
            'colegio' => 'Colegio',
            'nombres' => 'Nombres',
            'apellidos' => 'Apellidos',
            'celular' => 'Celular',
            'fecha_afiliacion' => 'Fecha de afiliación',
        ];

        foreach ($camposObligatorios as $campo => $nombre) {

            if ($fila[$campo] === '') {
                $errores[] = "{$nombre} es obligatorio.";
            }
        }
    }

    private function validarColegio(
        array $fila,
        string $nombreColegio,
        $colegios,
        array &$errores
    ) {
        $colegio = null;

        if ($fila['colegio'] !== '') {

            $colegio = $colegios->get(
                $nombreColegio
            );

            if (!$colegio) {
                $errores[] =
                    'El colegio no existe o no está activo en el sistema.';
            }
        }

        return $colegio;
    }

    private function validarDuplicadosBaseDatos(
        array $fila,
        string $registro,
        string $matricula,
        string $ci,
        array $datosExistentes,
        array &$errores
    ): void {
        if (
            $fila['registro_departamental'] !== '' &&
            $datosExistentes['registros']->has($registro)
        ) {
            $errores[] =
                'El registro departamental ya está registrado en el sistema.';
        }

        if (
            $fila['matricula_profesional'] !== '' &&
            $datosExistentes['matriculas']->has($matricula)
        ) {
            $errores[] =
                'La matrícula profesional ya está registrada en el sistema.';
        }

        if (
            $fila['ci'] !== '' &&
            $datosExistentes['cis']->has($ci)
        ) {
            $errores[] =
                'El CI ya está registrado en el sistema.';
        }
    }

    private function validarDuplicadosCsv(
        array $fila,
        string $registro,
        string $matricula,
        string $ci,
        array &$registrosCsv,
        array &$matriculasCsv,
        array &$cisCsv,
        array &$errores
    ): void {
        if ($fila['registro_departamental'] !== '') {

            if (isset($registrosCsv[$registro])) {

                $errores[] =
                    'El registro departamental está duplicado dentro del archivo.';

            } else {

                $registrosCsv[$registro] =
                    $fila['_numero_fila'];
            }
        }

        if ($fila['matricula_profesional'] !== '') {

            if (isset($matriculasCsv[$matricula])) {

                $errores[] =
                    'La matrícula profesional está duplicada dentro del archivo.';

            } else {

                $matriculasCsv[$matricula] =
                    $fila['_numero_fila'];
            }
        }

        if ($fila['ci'] !== '') {

            if (isset($cisCsv[$ci])) {

                $errores[] =
                    'El CI está duplicado dentro del archivo.';

            } else {

                $cisCsv[$ci] =
                    $fila['_numero_fila'];
            }
        }
    }

    private function validarLongitudes(
        array $fila,
        array &$errores
    ): void {
        $longitudes = [
            'registro_departamental' => 30,
            'matricula_profesional' => 30,
            'ci' => 20,
            'nombres' => 100,
            'apellidos' => 100,
            'celular' => 20,
        ];

        foreach ($longitudes as $campo => $maximo) {

            if (
                $fila[$campo] !== '' &&
                mb_strlen($fila[$campo]) > $maximo
            ) {
                $nombreCampo = ucfirst(
                    str_replace('_', ' ', $campo)
                );

                $errores[] =
                    "{$nombreCampo} no puede superar los {$maximo} caracteres.";
            }
        }
    }

    private function validarFechaAfiliacion(
        string $fecha,
        array &$errores
    ): ?string {
        $fechaNormalizada = $this->normalizarFecha(
            $fecha
        );

        if (
            $fecha !== '' &&
            $fechaNormalizada === null
        ) {
            $errores[] =
                'La fecha de afiliación no tiene un formato válido.';
        }

        return $fechaNormalizada;
    }

    public function confirmarAfiliados()
    {
        $datosValidacion = session('carga_datos.afiliados');

        if (!$datosValidacion) {
            return redirect()
                ->route('carga-datos.index')
                ->withErrors([
                    'archivo_afiliadas' =>
                        'No existe una validación pendiente de confirmar.'
                ]);
        }

        $filasValidas = collect($datosValidacion['filas'])
            ->where('estado', 'VALIDA')
            ->values();

        if ($filasValidas->isEmpty()) {
            return redirect()
                ->route('carga-datos.index')
                ->withErrors([
                    'archivo_afiliadas' =>
                        'No existen afiliadas válidas para cargar.'
                ]);
        }

        try {

            DB::transaction(function () use ($filasValidas, $datosValidacion) {

                foreach ($filasValidas as $fila) {

                    Afiliado::create([
                        'registro_departamental' => $fila['registro_departamental'],
                        'matricula_profesional' => $fila['matricula_profesional'],
                        'ci' => $fila['ci'],
                        'colegio_id' => $fila['colegio_id'],
                        'nombres' => $fila['nombres'],
                        'apellidos' => $fila['apellidos'],
                        'celular' => $fila['celular'],
                        'estado' => 'ACTIVO',
                        'fecha_afiliacion' => $fila['fecha_afiliacion'],
                    ]);
                }

                Auditoria::create([
                    'usuario_id' => auth()->id(),
                    'accion' => 'CARGA_DATOS',
                    'tabla_afectada' => 'afiliados',
                    'registro_id' => null,
                    'datos_anteriores' => null,
                    'datos_nuevos' => [
                        'tipo' => 'CARGA_MASIVA',
                        'total_filas' => $datosValidacion['total'],
                        'afiliadas_cargadas' => $filasValidas->count(),
                        'filas_con_error' => $datosValidacion['errores'],
                    ],
                    'fecha_registro' => now(),
                ]);
            });

            session()->forget('carga_datos.afiliados');

            return redirect()
                ->route('carga-datos.index')
                ->with('success',
                    'Se cargaron correctamente ' .
                    $filasValidas->count() .
                    ' afiliadas.'
                );

        } catch (\Throwable $e) {

            return redirect()
                ->route('carga-datos.index')
                ->withErrors([
                    'archivo_afiliadas' =>
                        'Ocurrió un error durante la carga de las afiliadas.'
                ]);
        }
    }
}
