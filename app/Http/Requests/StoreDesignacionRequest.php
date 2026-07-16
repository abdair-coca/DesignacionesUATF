<?php

namespace App\Http\Requests;

use App\Models\Designacion;
use Illuminate\Foundation\Http\FormRequest;

class StoreDesignacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'Id_docente' => ['required', 'exists:docentes,id'],
            'Id_materia' => ['required', 'exists:materias,id'],
            'Id_grupo' => ['required', 'exists:grupos,id'],
            'Id_gestion' => ['required', 'exists:gestiones,id'],
            'Id_periodo' => ['required', 'exists:periodos,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Forzar estado — solo un superior puede aprobar
            $this->merge(['estado' => 'propuesta']);

            $existe = Designacion::where('Id_docente', $this->Id_docente)
                ->where('Id_materia', $this->Id_materia)
                ->where('Id_grupo', $this->Id_grupo)
                ->where('Id_gestion', $this->Id_gestion)
                ->where('Id_periodo', $this->Id_periodo)
                ->exists();

            if ($existe) {
                $validator->errors()->add('Id_docente', 'Esta designación ya existe.');
            }
        });
    }
}
