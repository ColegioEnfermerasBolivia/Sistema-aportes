<?php

namespace App\Services;

use App\Models\Auditoria;

class AuditoriaService
{
    public function registrar(
        int $usuarioId,
        string $accion,
        string $tablaAfectada,
        ?int $registroId = null,
        ?array $datosAnteriores = null,
        ?array $datosNuevos = null
    ): Auditoria {
        return Auditoria::create([
            'usuario_id' => $usuarioId,
            'accion' => $accion,
            'tabla_afectada' => $tablaAfectada,
            'registro_id' => $registroId,
            'datos_anteriores' => $datosAnteriores,
            'datos_nuevos' => $datosNuevos,
            'fecha_registro' => now(),
        ]);
    }
}