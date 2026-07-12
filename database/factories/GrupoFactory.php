<?php

namespace Database\Factories;

use App\Models\Grupo;
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
            'codigo' => strtoupper(fake()->unique()->lexify('G?')),
            'estado' => 'habilitado',
        ];
    }
}
