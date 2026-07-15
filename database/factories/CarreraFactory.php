<?php

namespace Database\Factories;

use App\Models\Carrera;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Carrera>
 */
class CarreraFactory extends Factory
{
    protected $model = Carrera::class;

    public function definition(): array
    {
        return [
            'sigla' => strtoupper(fake()->unique()->lexify('???')),
            'nombre' => 'Carrera de '.fake()->unique()->words(2, true),
        ];
    }
}
