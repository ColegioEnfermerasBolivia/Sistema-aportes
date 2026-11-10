<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Informe de Aportes</title>
    <style>
        @page {
            margin: 35px 30px 45px 30px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #333333;
        }

        .encabezado {
            width: 100%;
            text-align: center;
            margin-bottom: 12px;
        }

        .logo {
            width: 65px;
            margin-bottom: 4px;
        }

        .institucion {
            font-size: 15px;
            font-weight: bold;
            color: #1F3F5C;
        }

        .titulo {
            font-size: 12px;
            font-weight: bold;
            color: #2E5B8A;
            margin-top: 3px;
        }

        .informacion {
            margin-top: 10px;
            font-size: 8px;
            color: #555555;
        }

        .afiliada-info {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0 16px 0;
        }

        .afiliada-info th {
            background: #2E5B8A;
            color: white;
            padding: 6px;
            text-align: left;
            font-size: 9px;
        }

        .afiliada-info td {
            border: 1px solid #D9D9D9;
            padding: 6px;
            font-size: 9px;
        }

        .gestion {
            font-size: 11px;
            font-weight: bold;
            color: #1F3F5C;
            text-align: center;
            margin-top: 14px;
            margin-bottom: 6px;
        }

        table.aportes {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 16px;
        }

        table.aportes th {
            background: #2E5B8A;
            color: white;
            border: 1px solid #1F3F5C;
            padding: 5px 3px;
            font-size: 7px;
            text-align: center;
        }

        table.aportes td {
            border: 1px solid #D9D9D9;
            padding: 5px 3px;
            font-size: 7px;
            text-align: center;
        }

        table.aportes td.afiliada {
            text-align: left;
        }

        .sin-aportes {
            text-align: center;
            font-style: italic;
            color: #666666;
            margin: 20px 0;
        }

        .page-break {
            page-break-before: always;
        }

        .pie-pagina {
            position: fixed;
            bottom: -20px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 7px;
            color: #777;
        }
        table.aportes td.gestion-celda {
            font-weight: bold;
            color: #1F3F5C;
        }

        table.aportes tr.fila-par td {
            background: #F3F6FA;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>

    {{-- ENCABEZADO --}}

    <div class="encabezado">
        <img
            src="{{ public_path('images/logo.png') }}"
            class="logo"
        >
        <div class="institucion">COLEGIO DE ENFERMERAS DE BOLIVIA</div>
        <div class="titulo">INFORME DE APORTES</div>
        <div class="informacion">
            Generado por:
            {{ $informacionReporte['usuario'] ?? 'No disponible' }}
            &nbsp;&nbsp;|&nbsp;&nbsp;

            Fecha:
            {{ $informacionReporte['fecha'] ?? '' }}
            &nbsp;&nbsp;|&nbsp;&nbsp;

            Gestión:
            {{ $informacionReporte['year'] ?? 'Todos los años' }}
            &nbsp;&nbsp;|&nbsp;&nbsp;

            Colegio:
            {{ $informacionReporte['colegio'] ?? 'Todos los colegios' }}
            &nbsp;&nbsp;|&nbsp;&nbsp;

            Búsqueda:
            {{ $informacionReporte['buscar'] ?? 'Sin búsqueda' }}
        </div>
    </div>

    {{-- CI + TODOS LOS AÑOS --}}

    @if ($aportesPorGestion->isEmpty())
        <div class="sin-aportes">No registra aportes verificados.</div>
    @else
        <div class="gestion">APORTES POR GESTIÓN</div>

        <table class="aportes">
            <thead>
                <tr>
                    <th style="width: 8%;">Gestión</th>
                    @foreach ($meses as $mes)
                        <th>{{ mb_substr($mes, 0, 3) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($aportesPorGestion as $gestion => $aportes)
                    <tr class="{{ $loop->even ? 'fila-par' : '' }}">
                        <td class="gestion-celda">{{ $gestion }}</td>
                        @for ($mes = 1; $mes <= 12; $mes++)
                            <td>
                                @if (isset($aportes[$mes]))
                                    {{ number_format((float) $aportes[$mes]->monto, 2, '.', '') }}
                                @else
                                    —
                                @endif
                            </td>
                        @endfor
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- PIE --}}
    <div class="pie-pagina">Colegio de Enfermeras de Bolivia - Informe de Aportes</div>
</body>
</html>