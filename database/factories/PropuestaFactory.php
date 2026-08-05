<?php

namespace Database\Factories;

use App\Models\Carrera;
use App\Models\Gestion;
use App\Models\Periodo;
use App\Models\Propuesta;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Propuesta> */
class PropuestaFactory extends Factory
{
    protected $model = Propuesta::class;

    public function definition(): array
    {
        return [
            'carrera_id' => Carrera::factory(),
            'gestion_id' => fn () => Gestion::query()->where('es_actual', true)->value('id')
                ?? Gestion::factory()->actual(),
            'periodo_id' => Periodo::factory(),
            'creado_por' => fn (array $attributes) => User::factory()->director($attributes['carrera_id']),
            'descripcion' => 'Propuesta de designaciones de testing',
            'estado' => 'borrador',
        ];
    }

    public function borrador(): static
    {
        return $this->state(['estado' => 'borrador']);
    }

    public function oficial(): static
    {
        return $this->state(['estado' => 'oficial']);
    }
}
