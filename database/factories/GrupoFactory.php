<?php

namespace Database\Factories;

use App\Models\Grupo;
use App\Models\MallaCurricular;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Grupo>
 */
class GrupoFactory extends Factory
{
    protected $model = Grupo::class;

    public function definition(): array
    {
        return [
            'malla_curricular_id' => MallaCurricular::factory(),
            'materia_id' => fn (array $attributes) => MallaCurricular::query()
                ->findOrFail($attributes['malla_curricular_id'])
                ->materia_id,
            'codigo' => (string) fake()->unique()->numberBetween(1, 999),
            'estado' => 'habilitado',
        ];
    }
}
