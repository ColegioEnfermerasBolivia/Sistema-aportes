<?php

namespace Tests\Unit;

use App\Services\ImportacionEgresadasService;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

class ImportacionEgresadasServiceTest extends TestCase
{
    public function test_csv_requires_the_identity_and_formation_headers(): void
    {
        $ruta = tempnam(sys_get_temp_dir(), 'egresadas-');
        file_put_contents($ruta, "ci;nombres;apellidos\n");

        try {
            $this->expectException(ValidationException::class);

            (new ImportacionEgresadasService())->importar($ruta, 1);
        } finally {
            unlink($ruta);
        }
    }

    public function test_csv_rejects_unknown_headers(): void
    {
        $ruta = tempnam(sys_get_temp_dir(), 'egresadas-');
        file_put_contents(
            $ruta,
            "ci,nombres,apellidos,universidad,departamento,carrera,desconocido\n"
        );

        try {
            $this->expectException(ValidationException::class);

            (new ImportacionEgresadasService())->importar($ruta, 1);
        } finally {
            unlink($ruta);
        }
    }
}
