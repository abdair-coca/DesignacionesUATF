<?php

namespace Database\Factories;

use App\Models\Grupo;
use App\Models\MallaCurricular;
use App\Models\Materia;
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
            'materia_id' => Materia::factory(),
            'malla_curricular_id' => function (array $attributes): int {
                $materia = Materia::query()->findOrFail($attributes['materia_id']);

                return MallaCurricular::firstOrCreate([
                    'carrera_id' => $materia->carrera_id,
                    'materia_id' => $materia->id,
                ])->id;
            },
            'codigo' => (string) fake()->unique()->numberBetween(1, 999),
            'estado' => 'habilitado',
        ];
    }
}
