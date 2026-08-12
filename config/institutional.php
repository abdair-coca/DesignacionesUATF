<?php

return [
    'enabled' => filter_var(env('INSTITUTIONAL_ENABLED', false), FILTER_VALIDATE_BOOL),
    // Conexión secundaria; nunca sustituye a DB_CONNECTION.
    'connection' => env('INSTITUTIONAL_DB_CONNECTION', 'institutional'),
];
