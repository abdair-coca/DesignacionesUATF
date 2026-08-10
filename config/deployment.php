<?php

return [
    'institutional' => [
        // Se activa unicamente cuando exista un adaptador autorizado.
        'enabled' => filter_var(env('INSTITUTIONAL_ENABLED', false), FILTER_VALIDATE_BOOL),
    ],

    'health' => [
        // Los detalles no contienen secretos, pero el endpoint solo expone estados.
        'include_details' => filter_var(env('HEALTH_INCLUDE_DETAILS', false), FILTER_VALIDATE_BOOL),
    ],
];
