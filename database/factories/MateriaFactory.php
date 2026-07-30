<?php

namespace Database\Factories;

use App\Models\Materia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Materia>
 */
class MateriaFactory extends Factory
{
    protected $model = Materia::class;

    public function definition(): array
    {
        return [
            'sigla' => strtoupper(fake()->unique()->bothify('???-###')),
            'nombre' => 'Materia de '.fake()->unique()->words(3, true),
            'horas' => fake()->numberBetween(2, 8),
        ];
    }
}
