<?php

namespace App\Http\Requests;

class InstitutionalDesignacionesConsultaRequest extends InstitutionalDesignacionesRequest
{
    public function rules(): array
    {
        return $this->parameterRules(false);
    }
}
