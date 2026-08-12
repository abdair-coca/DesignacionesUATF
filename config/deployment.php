<?php

return [
    'health' => [
        // Los detalles no contienen secretos, pero el endpoint solo expone estados.
        'include_details' => filter_var(env('HEALTH_INCLUDE_DETAILS', false), FILTER_VALIDATE_BOOL),
    ],
];
