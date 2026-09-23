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

        .footer {
            position: fixed;
            bottom: -25px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #777777;
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

    @if ($modo === 'afiliada_todos')
        <table class="afiliada-info">
            <tr>
                <th colspan="2">
                    AFILIADA
                </th>
            </tr>
            <tr>
                <td>
                    <strong>
                        Nombre completo
                    </strong>
                </td>
                <td>
                    {{ $afiliada->apellidos }},
                    {{ $afiliada->nombres }}
                </td>
            </tr>
            <tr>
                <td>
                    <strong>CI</strong>
                </td>
                <td>
                    {{ $afiliada->ci }}
                </td>
            </tr>
            <tr>
                <td>
                    <strong>Colegio</strong>
                </td>
                <td>
                    {{ $afiliada->colegio?->nombre ?? 'Sin colegio' }}
                </td>
            </tr>
        </table>

        @if ($aportesPorGestion->isEmpty())
            <div class="sin-aportes">No registra aportes verificados.</div>
        @else
            @foreach (
                $aportesPorGestion as $gestion => $aportes
            )
               <div class="gestion">APORTES DEL AÑO: {{ $gestion }}</div>
                <table class="aportes">
                    <thead>
                        <tr>
                            <th>N.º</th>
                            <th style="width: 22%;">Afiliada</th>
                            <th>CI</th>

                            @foreach ($meses as $mes)
                                <th>{{ $mes }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td class="afiliada">
                                {{ $afiliada->apellidos }},
                                {{ $afiliada->nombres }}
                            </td>
                            <td>{{ $afiliada->ci }}</td>
                            @for ($mes = 1; $mes <= 12; $mes++)
                                <td>
                                    @if (isset($aportes[$mes]))
                                        {{ number_format(
                                            (float) $aportes[$mes]->monto,
                                            2,
                                            '.',
                                            ''
                                        ) }}
                                    @else
                                        —
                                    @endif
                                </td>
                            @endfor
                        </tr>
                    </tbody>
                </table>
            @endforeach
        @endif

    {{-- INFORME GENERAL --}}
    @else
        <div class="gestion">APORTES DEL AÑO: {{ $year }}</div>
        @if ($afiliados->isEmpty())
            <div class="sin-aportes">No se encontraron afiliadas.</div>
        @else
            <table class="aportes">
                <thead>
                    <tr>
                        <th>N.º</th>
                        <th style="width: 22%;">Afiliada</th>
                        <th>CI</th>
                        @foreach ($meses as $mes)
                            <th>{{ $mes }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach (
                        $afiliados as $indice => $afiliado
                    )
                        <tr>
                            <td>{{ $indice + 1 }}</td>
                            <td class="afiliada">
                                {{ $afiliado->apellidos }},
                                {{ $afiliado->nombres }}
                            </td>
                            <td>{{ $afiliado->ci }}</td>
                            @for (
                                $mes = 1;
                                $mes <= 12;
                                $mes++
                            )
                                <td>
                                    @if (isset($afiliado->aportesDelYear[$mes]))
                                        {{
                                            number_format(
                                                (float)
                                                $afiliado
                                                    ->aportesDelYear[$mes]
                                                    ->monto,
                                                2,
                                                '.',
                                                ''
                                            )
                                        }}
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
    @endif

    {{-- PIE --}}
    <div class="pie-pagina">Colegio de Enfermeras de Bolivia - Informe de Aportes</div>
</body>
</html>