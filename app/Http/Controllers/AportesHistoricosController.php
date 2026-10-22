<?php

namespace App\Http\Controllers;

use App\Services\Importaciones\AportesHistoricos\ImportadorAportesHistoricos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AportesHistoricosController extends Controller
{
    public function __construct(
        private ImportadorAportesHistoricos $importador
    ) {
    }

    /**
     * Muestra la pantalla de carga de aportes históricos.
     */
    public function index()
    {
        return redirect()->route('carga-datos.index');
    }

    /**
     * Recibe el CSV y lo analiza.
     *
     * IMPORTANTE:
     * No se modifica la base de datos.
     * Solamente se genera la previsualización.
     */
    public function analizar(Request $request)
    {
        $request->validate([
            'archivo' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:262144',
            ],
        ]);

        $archivo = $request->file('archivo');

        /*
         * Guardamos temporalmente el CSV.
         */
        $ruta = $archivo->store(
            'importaciones/aportes-historicos'
        );

        try {

            /*
             * Analizamos el archivo.
             *
             * El importador devuelve:
             *
             * [
             *     'resumen' => [...],
             *     'registros' => [...]
             * ]
             */
            $resultado = $this->importador->analizar(
                Storage::path($ruta)
            );

            /*
             * Guardamos solamente la ruta.
             *
             * NO guardamos todos los registros en sesión porque
             * un CSV histórico puede ser bastante grande.
             */
            session([
                'aportes_historicos.archivo' => $ruta,
            ]);

            return view(
                'carga-datos.index',
                [
                    'resultado' => $resultado,
                    'datosValidacion' =>
                        session('carga_datos.afiliados'),
                    'mostrarPrevisualizacion' =>
                        session()->has('mostrar_previsualizacion'),
                ]
            );

        } catch (\Throwable $e) {

            /*
             * Si algo falla durante el análisis,
             * eliminamos el archivo temporal.
             */
            Storage::delete($ruta);

            return back()
                ->withInput()
                ->withErrors([
                    'archivo' =>
                        'No fue posible analizar el archivo: '
                        . $e->getMessage(),
                ]);
        }
    }

    /**
     * Confirma e importa definitivamente los aportes.
     */
    public function importar()
    {
        $ruta = session(
            'aportes_historicos.archivo'
        );

        /*
         * No existe una importación pendiente.
         */
        if (!$ruta) {
            return redirect()
                ->route('aportesHistoricos.index')
                ->withErrors([
                    'archivo' =>
                        'No existe una importación pendiente de confirmar.',
                ]);
        }

        /*
         * Verificamos que el archivo siga existiendo.
         */
        if (!Storage::exists($ruta)) {

            session()->forget(
                'aportes_historicos.archivo'
            );

            return redirect()
                ->route('aportesHistoricos.index')
                ->withErrors([
                    'archivo' =>
                        'El archivo temporal ya no existe. '
                        . 'Debe volver a cargarlo.',
                ]);
        }

        try {

            /*
             * Volvemos a analizar el archivo para obtener los
             * registros exactamente en la estructura que espera
             * ImportadorAportesHistoricos::importar().
             */
            $analisis = $this->importador->analizar(
                Storage::path($ruta)
            );

            /*
             * Solamente enviamos los registros analizados.
             *
             * El propio ImportadorAportesHistoricos se encarga
             * de ignorar todo lo que no tenga estado LISTO.
             */
            $resultado = $this->importador->importar(
                $analisis['registros'],
                Auth::id()
            );

            /*
             * Eliminamos el archivo temporal después de
             * una importación exitosa.
             */
            Storage::delete($ruta);

            session()->forget(
                'aportes_historicos.archivo'
            );

            return redirect()
                ->route('aportesHistoricos.index')
                ->with(
                    'success',
                    'La importación de aportes históricos '
                    . 'se realizó correctamente.'
                )
                ->with(
                    'resultado_importacion',
                    $resultado
                );

        } catch (\Throwable $e) {

            return redirect()
                ->route('aportesHistoricos.index')
                ->withErrors([
                    'archivo' =>
                        'La importación no pudo completarse: '
                        . $e->getMessage(),
                ]);
        }
    }

    /**
     * Cancela una importación pendiente.
     */
    public function cancelar()
    {
        $ruta = session(
            'aportes_historicos.archivo'
        );

        if ($ruta) {
            Storage::delete($ruta);
        }

        session()->forget(
            'aportes_historicos.archivo'
        );

        return redirect()
            ->route('aportesHistoricos.index')
            ->with(
                'success',
                'La importación fue cancelada.'
            );
    }
}