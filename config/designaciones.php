<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mínimo de horas por docente
    |--------------------------------------------------------------------------
    |
    | Número mínimo de horas académicas semanales que debe tener asignadas
    | un docente en una misma gestión y periodo. No existe límite máximo.
    |
    */
    'minimo_horas' => (int) env('DESIGNACIONES_MINIMO_HORAS', 6),
];
