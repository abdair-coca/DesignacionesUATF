<?php

namespace App\Exceptions;

use UnexpectedValueException;

final class InvalidDesignacionesResponse extends UnexpectedValueException
{
    public function __construct()
    {
        parent::__construct('La respuesta de designaciones no tiene el formato esperado.');
    }
}
