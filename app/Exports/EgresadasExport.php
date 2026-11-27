<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EgresadasExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        private Builder $query
    ) {}

    public function query(): Builder
    {
        return $this->query
            ->with([
                'formaciones.universidad',
                'formaciones.departamento',
                'afiliado.colegio',
            ])
            ->orderBy('apellidos')
            ->orderBy('nombres');
    }

    public function headings(): array
    {
        return [
            'CI',
            'Nombres',
            'Apellidos',
            'Celular',
            'Estado de incorporación',
            'Registro departamental',
            'Matrícula profesional',
            'Colegio asignado',
            'Universidad',
            'Departamento de formación',
            'Carrera',
            'Fecha de egreso',
            'Estado de titulación',
            'Número de título',
        ];
    }

    public function map($egresada): array
    {
        $afiliado = $egresada->afiliado;
        $convertida = $afiliado !== null || $egresada->estado === 'AFILIADA';
        $formaciones = $egresada->formaciones;

        if ($formaciones->isEmpty()) {
            $formaciones = collect([null]);
        }

        return $formaciones
            ->map(fn ($formacion): array => [
                $egresada->ci,
                $egresada->nombres,
                $egresada->apellidos,
                $egresada->celular,
                $convertida ? 'AFILIADA' : 'PENDIENTE',
                $afiliado?->registro_departamental,
                $afiliado?->matricula_profesional,
                $afiliado?->colegio?->nombre,
                $formacion?->universidad?->nombre,
                $formacion?->departamento?->nombre,
                $formacion?->carrera,
                $formacion?->fecha_egreso?->format('d/m/Y'),
                $formacion?->estado_titulacion,
                $formacion?->numero_titulo,
            ])
            ->all();
    }
}
