<?php

namespace Database\Factories;

use App\Models\Designacion;
use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Periodo;
use App\Models\User;
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
            'Id_grupo' => Grupo::factory(),
            'Id_materia' => fn (array $attributes) => Grupo::query()
                ->findOrFail($attributes['Id_grupo'])
                ->mallaCurricular
                ->materia_id,
            'malla_curricular_id' => fn (array $attributes) => Grupo::query()
                ->findOrFail($attributes['Id_grupo'])
                ->malla_curricular_id,
            'Id_gestion' => Gestion::factory(),
            'Id_periodo' => Periodo::factory(),
            'estado' => 'propuesta',
            'creado_por' => null,
            'aprobado_por' => null,
        ];
    }

    public function aprobada(): static
    {
        return $this->state([
            'estado' => 'aprobada',
            'aprobado_por' => fn (): ?int => User::query()
                ->where('rol', User::ROL_VICERRECTORADO)
                ->value('id'),
        ]);
    }

    public function rechazada(string $motivo = 'Observación sintética de testing'): static
    {
        return $this->state([
            'estado' => 'rechazada',
            'motivo_rechazo' => $motivo,
        ]);
    }
}
