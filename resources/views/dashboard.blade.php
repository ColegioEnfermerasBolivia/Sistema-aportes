@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')

<header class="h-17.5 bg-white border-b border-gray-200 flex items-center justify-between px-[25px]">
    <div>
        <h1 class="m-0 text-xl text-gray-800">Sistema de Gestion</h1>

        @if ($esAdministrador)
            <p class="mt-0.5 text-xs text-gray-500">Tienes acceso a todos los colegios.</p>
        @else
            <p class="mt-0.5 text-xs text-gray-500">Colegio: {{ $usuario->colegio->nombre }}</p>
        @endif
    </div>

    <div class="flex items-center">
        <div class="flex items-center gap-2.5">
            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-blue-600 text-white font-bold">
                {{ mb_strtoupper(mb_substr($usuario->nombres, 0, 1, 'UTF-8')) }}
            </div>
            <div class="flex flex-col">
                <span class="text-sm font-semibold text-gray-900">
                    {{ $usuario->nombres }} {{ $usuario->apellidos }}
                </span>
                <span class="mt-0.5 text-xs text-gray-500">
                    Rol: {{ $usuario->rol->nombre }}
                </span>
            </div>
        </div>
    </div>
</header>
<div class="p-4 space-y-4">
    {{-- FILA 1: INDICADORES --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        {{-- Afiliadas activas --}}
        <div class="bg-white rounded-xl border border-gray-200 border-l-4 border-l-[#1a34c9] px-5 py-4">
            <p class="text-sm text-gray-500">Afiliadas activas</p>
            <p class="mt-2 text-3xl font-bold text-gray-900">
                {{ number_format($afiliadasActivas) }}
            </p>
        </div>

        {{-- Afiliadas con especialidad --}}
        <div class="bg-white rounded-xl border border-gray-200 border-l-4 border-l-[#0f766e] px-5 py-4">
            <p class="text-sm text-gray-500">Afiliadas con especialidad</p>
            <p class="mt-2 text-3xl font-bold text-gray-900">
                {{ number_format($afiliadasConEspecialidad) }}
            </p>
        </div>
    </div>

    {{-- FILA 2: APORTES POR MES --}}
    <div class="bg-white rounded-xl border border-gray-200 px-4 py-4">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">
                    Aportes verificados por mes
                </h2>
                <p class="text-sm text-gray-500">
                    Gestión {{ $year }}
                </p>
            </div>
        </div>
        <div class="h-58.75">
            <canvas id="graficoAportesMensuales"></canvas>
        </div>
    </div>

    {{-- ROW 3 --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- Afiliadas por colegio --}}
        <div class="bg-white rounded-2xl border border-gray-200 px-5 py-4">

            <div class="mb-2">
                <h2 class="text-base font-semibold text-gray-800">
                    Afiliadas por colegio
                </h2>

                <p class="text-sm text-gray-500 mt-1">
                    Distribución de afiliadas registradas por colegio.
                </p>
            </div>

            <div class="relative h-61.25">
                <canvas id="graficoAfiliadasColegio"></canvas>
            </div>
        </div>

        {{-- Aportes por colegio --}}
        <div class="bg-white rounded-2xl border border-gray-200 px-5 py-4">

            <div class="mb-2">
                <h2 class="text-base font-semibold text-gray-800">
                    Aportes totales por colegio
                </h2>

                <p class="text-sm text-gray-500 mt-1">
                    Aportes verificados de la gestión {{ $year }}.
                </p>
            </div>

            <div class="relative h-61.25">
                <canvas id="graficoAportesColegio"></canvas>
            </div>

        </div>

    </div>
</div>
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const aportesPorMes = @json($aportesPorMes);
        const nombresMeses = [
            'ENE',
            'FEB',
            'MAR',
            'ABR',
            'MAY',
            'JUN',
            'JUL',
            'AGO',
            'SEP',
            'OCT',
            'NOV',
            'DIC'
        ];

        const totalesPorMes = nombresMeses.map((mes, indice) => {
            const numeroMes = indice + 1;
            return aportesPorMes[numeroMes]
                ? Number(aportesPorMes[numeroMes].total)
                : 0;
        });

        const contexto = document
            .getElementById('graficoAportesMensuales')
            .getContext('2d');
        new Chart(contexto, {
            type: 'bar',
            data: {
                labels: nombresMeses,
                datasets: [
                    {
                        label: 'Aportes verificados',
                        data: totalesPorMes,
                        backgroundColor: '#2563eb',
                        borderColor: '#2563eb',
                        borderWidth: 1,
                        borderRadius: 4
                    }
                ]
            },

            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (valor) {
                                return 'Bs. ' + valor.toLocaleString('es-BO');
                            }
                        }
                    }
                },

                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function (contexto) {
                                return 'Bs. ' +
                                    Number(contexto.raw)
                                        .toLocaleString('es-BO', {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        });
                            }
                        }
                    }
                }
            }
        });
    </script>

    <script>
        /* Afiliadas por colegio*/
        const afiliadasPorColegio = @json($afiliadasPorColegio);
        const nombresColegiosAfiliadas = afiliadasPorColegio.map(
            item => item.colegio?.nombre ?? 'Sin colegio');
        const cantidadesAfiliadas = afiliadasPorColegio.map(
            item => Number(item.total));
        const contextoAfiliadasColegio = document
            .getElementById('graficoAfiliadasColegio')
            .getContext('2d');

        new Chart(contextoAfiliadasColegio, {
            type: 'bar',
            data: {
                labels: nombresColegiosAfiliadas,
                datasets: [
                    {
                        label: 'Afiliadas',
                        data: cantidadesAfiliadas,
                        backgroundColor: '#7c3aed',
                        borderColor: '#7c3aed',
                        borderWidth: 1,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    },
                    y: {
                        ticks: {
                            font:{size: 10}
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function (contexto) {
                                return Number(contexto.raw)
                                    .toLocaleString('es-BO')
                                    + ' afiliadas';
                            }
                        }
                    }
                }
            }
        });

        /*Aportes totales por colegio*/

        const aportesPorColegio = @json($aportesPorColegio);
        const nombresColegiosAportes = aportesPorColegio.map(
            item => item.colegio ?? 'Sin colegio');
        const totalesAportesColegio = aportesPorColegio.map(
            item => Number(item.total));
        const contextoAportesColegio = document
            .getElementById('graficoAportesColegio')
            .getContext('2d');

        new Chart(contextoAportesColegio, {
            type: 'bar',
            data: {
                labels: nombresColegiosAportes,
                datasets: [
                    {
                        label: 'Aportes verificados',
                        data: totalesAportesColegio,
                        backgroundColor: '#ea580c',
                        borderColor: '#ea580c',
                        borderWidth: 1,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (valor) {
                                return 'Bs. ' +
                                    Number(valor)
                                        .toLocaleString('es-BO');
                            }
                        }
                    }
                },
                y: {
                    ticks: {
                        font: { size: 10 }
                    }
                },
                plugins: {
                    tooltip: {
                        legend: {
                            display: false
                        },

                        tooltip: {
                            callbacks: {
                                label: function (contexto) {
                                    return 'Bs. ' +
                                        Number(contexto.raw)
                                            .toLocaleString('es-BO', {
                                                minimumFractionDigits: 2,
                                                maximumFractionDigits: 2
                                            });
                                }
                            }
                        }
                    }
                }
            }
        });
    </script>
@endpush
@endsection