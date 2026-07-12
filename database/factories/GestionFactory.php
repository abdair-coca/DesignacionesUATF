<?php

namespace Database\Factories;

use App\Models\Gestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gestion>
 */
class GestionFactory extends Factory
{
    protected $model = Gestion::class;

    public function definition(): array
    {
        return [
            'nombre' => (string) fake()->unique()->numberBetween(2000, 2100),
        ];
    }
}
