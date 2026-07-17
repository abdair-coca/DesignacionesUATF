<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Límite de horas por docente
    |--------------------------------------------------------------------------
    |
    | Número máximo de horas académicas que un docente puede tener asignadas
    | en una misma gestión y periodo. Se puede ajustar por entorno.
    |
    */
    'limite_horas' => (int) env('DESIGNACIONES_LIMITE_HORAS', 6),
];
