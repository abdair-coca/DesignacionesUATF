<?php

namespace Database\Factories;

use App\Models\Designacion;
use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Periodo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Designacion>
 */
class DesignacionFactory extends Factory
{
    protected $model = Designacion::class;

    public function definition(): array
    {
        return [
            'Id_docente' => Docente::factory(),
            'Id_materia' => Materia::factory(),
            'Id_grupo' => Grupo::factory(),
            'Id_gestion' => Gestion::factory(),
            'Id_periodo' => Periodo::factory(),
            'estado' => 'propuesta',
            'creado_por' => null,
            'aprobado_por' => null,
        ];
    }
}
