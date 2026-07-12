<?php

namespace Database\Factories;

use App\Models\Docente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Docente>
 */
class DocenteFactory extends Factory
{
    protected $model = Docente::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'ci' => fake()->unique()->numerify('#######'),
            'carrera_origen_id' => null,
        ];
    }
}
