<?php

namespace App\Http\Requests;

use App\Models\Designacion;
use App\Models\Grupo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDesignacionRequest extends FormRequest
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
            'estado' => ['required', Rule::in(['propuesta', 'aprobada', 'rechazada'])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $excludeId = $this->route('designacion')?->id;

            $query = Designacion::where('Id_docente', $this->Id_docente)
                ->where('Id_materia', $this->Id_materia)
                ->where('Id_grupo', $this->Id_grupo)
                ->where('Id_gestion', $this->Id_gestion)
                ->where('Id_periodo', $this->Id_periodo);

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if ($query->exists()) {
                $validator->errors()->add('Id_docente', 'Esta designación ya existe.');
            }
            $grupo = Grupo::with('mallaCurricular')->find($this->Id_grupo);

            if ($grupo?->mallaCurricular?->materia_id !== (int) $this->Id_materia) {
                $validator->errors()->add('Id_grupo', 'El grupo no corresponde a la materia seleccionada.');
            }

            $grupoOcupado = Designacion::activas()
                ->where('Id_grupo', $this->Id_grupo)
                ->where('Id_gestion', $this->Id_gestion)
                ->where('Id_periodo', $this->Id_periodo)
                ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
                ->exists();

            if ($grupoOcupado) {
                $validator->errors()->add('Id_docente', 'El grupo ya tiene una designacion activa.');
            }
        });
    }
}
