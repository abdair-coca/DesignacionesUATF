<?php

namespace App\Observers;

use App\Models\Designacion;
use App\Models\DesignacionHistorial;

class DesignacionObserver
{
    private const CAMPOS_RASTREADOS = [
        'Id_docente',
        'Id_materia',
        'Id_grupo',
        'Id_gestion',
        'Id_periodo',
        'estado',
    ];

    public function updating(Designacion $designacion): void
    {
        if (! $designacion->isDirty(self::CAMPOS_RASTREADOS)) {
            return;
        }

        foreach (self::CAMPOS_RASTREADOS as $campo) {
            if ((string) $designacion->getOriginal($campo) !== (string) $designacion->$campo) {
                DesignacionHistorial::create([
                    'designacion_id' => $designacion->id,
                    'campo' => $campo,
                    'valor_anterior' => (string) $designacion->getOriginal($campo),
                    'valor_nuevo' => (string) $designacion->$campo,
                    'fecha' => now(),
                    'usuario_id' => auth()->id(),
                ]);
            }
        }
    }
}
