<?php

namespace App\Http\Requests;

class GenerarCartaPersonalRequest extends GenerarCartaDesignacionRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esPersonal() ?? false;
    }
}
