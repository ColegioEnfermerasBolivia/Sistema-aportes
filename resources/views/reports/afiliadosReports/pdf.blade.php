<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Informe de Afiliadas</title>
    <style>
        @page {
            size: letter landscape;
            margin: 35px 30px 35px 30px;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #222;
        }

        /*Encabezado institucional*/

        .encabezado {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .encabezado td {
            border: none;
            vertical-align: middle;
        }

        .logo {
            width: 80px;
            text-align: left;
        }

        .logo img {
            width: 65px;
        }

        .titulo {
            text-align: center;
        }

        .titulo-principal {
            font-size: 16px;
            font-weight: bold;
            color: #1F3F5C;
        }

        .titulo-secundario {
            font-size: 12px;
            font-weight: bold;
            color: #2E5B8A;
            margin-top: 5px;
        }

        /*Información del reporte*/

        .informacion {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .informacion td {
            border: none;
            text-align: center;
            font-size: 8px;
            color: #555;
            padding: 2px;
        }

        /*Tabla principal */

        .tabla {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .tabla th {
            background-color: #2E5B8A;
            color: #FFFFFF;
            border: 1px solid #1F3F5C;
            padding: 5px 3px;
            text-align: center;
            vertical-align: middle;
            font-size: 7.5px;
            font-weight: bold;
        }

        .tabla td {
            border: 1px solid #AEB8C2;
            padding: 4px 3px;
            vertical-align: middle;
            font-size: 7.5px;
            word-wrap: break-word;
        }

        /*Filas alternas*/
        .tabla tbody tr:nth-child(even) {
            background-color: #F2F6FA;
        }

        /*Alineaciones*/
        .centrado {
            text-align: center;
        }

        .izquierda {
            text-align: left;
        }

        /*Anchos de columnas*/
        .col-numero{
            width: 4%;
        }

        .col-ci {
            width: 7%;
        }
        .col-matricula-profesional {
            width: 11%;
        }
        .col-matricula-departamental {
            width: 11%;
        }
        .col-apellidos {
            width: 14%;
        }
        .col-nombres {
            width: 14%;
        }
        .col-fecha {
            width: 9%;
        }
        .col-colegio {
            width: 14%;
        }
        .col-especialidad {
            width: 19%;
        }

        /*Pie de página */

        .pie-pagina {
            position: fixed;
            bottom: -20px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 7px;
            color: #777;
        }
    </style>
</head>
<body>

    {{-- ENCABEZADO INSTITUCIONAL --}}

    <table class="encabezado">
        <tr>
            <td class="logo">
                @php
                    $logoPath = public_path('images/logo.png');

                    $logoBase64 = '';

                    if (file_exists($logoPath)) {
                        $logoBase64 = base64_encode(
                            file_get_contents($logoPath)
                        );
                    }
                @endphp

                @if ($logoBase64)
                    <img
                        src="data:image/png;base64,{{ $logoBase64 }}"
                        alt="Logo"
                    >
                @endif
            </td>
            <td class="titulo">
                <div class="titulo-principal">
                    COLEGIO DE ENFERMERAS DE BOLIVIA
                </div>
                <div class="titulo-secundario">
                    INFORME DE AFILIADAS
                </div>
            </td>
            <td class="logo">
                {{-- Espacio equivalente para mantener centrado el título --}}
            </td>
        </tr>
    </table>

    {{-- INFORMACIÓN DEL REPORTE --}}
    <table class="informacion">
        <tr>
            <td>
                <strong>Generado por:</strong>
                {{ $informacionReporte['usuario'] ?? 'No disponible' }}
            </td>
            <td>
                <strong>Fecha:</strong>
                {{ $informacionReporte['fecha'] ?? '' }}
            </td>
            <td>
                <strong>Colegio:</strong>
                {{ $informacionReporte['colegio'] ?? 'Todos los colegios' }}
            </td>
        </tr>
    </table>

    {{-- TABLA DE AFILIADAS --}}

    <table class="tabla">
        <thead>
            <tr>
                <th class="col-numero">N°</th>
                <th class="col-ci">CI</th>
                <th class="col-matricula-profesional">Matrícula profesional</th>
                <th class="col-matricula-departamental">Matrícula departamental</th>
                <th class="col-apellidos">Apellidos</th>
                <th class="col-nombres">Nombres</th>
                <th class="col-fecha">Fecha de afiliación</th>
                <th class="col-colegio">Colegio</th>
                <th class="col-especialidad">Especialidad</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($afiliados as $afiliado)
                @php
                    $especialidades = $afiliado->afiliadosPostgrados
                        ->map(function ($afiliadoPostgrado) {
                            return $afiliadoPostgrado->postgrado?->nombre;
                        })
                        ->filter()
                        ->implode(', ');
                @endphp
                <tr>
                    <td class="centrado">{{ $loop->iteration }}</td>
                    <td class="centrado">{{ $afiliado->ci }}</td>
                    <td class="centrado">{{ $afiliado->matricula_profesional ?? '' }}</td>
                    <td class="centrado">{{ $afiliado->registro_departamental ?? '' }}</td>
                    <td class="izquierda">{{ $afiliado->apellidos }}</td>
                    <td class="izquierda">{{ $afiliado->nombres }}</td>
                    <td class="centrado">
                        {{ $afiliado->fecha_afiliacion?->format('d/m/Y') }}
                    </td>
                    <td class="izquierda">
                        {{ $afiliado->colegio?->nombre ?? '' }}
                    </td>
                    <td class="izquierda">
                        {{ $especialidades ?: 'Sin especialidad' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td
                        colspan="9"
                        class="centrado"
                    >
                        No se encontraron afiliadas con los criterios seleccionados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- PIE DE PÁGINA --}}

    <div class="pie-pagina">
        Colegio de Enfermeras de Bolivia — Informe de Afiliadas
    </div>
</body>
</html>