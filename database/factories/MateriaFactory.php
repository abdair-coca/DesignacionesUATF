<?php

namespace Database\Factories;

use App\Models\Carrera;
use App\Models\MallaCurricular;
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

    public function configure(): static
    {
        return $this
            ->afterMaking(function (Materia $materia): void {
                if ($materia->getAttribute('carrera_id') !== null) {
                    $materia->setRelation('carreraParaMalla', Carrera::findOrFail($materia->getAttribute('carrera_id')));
                    $materia->offsetUnset('carrera_id');
                }
            })
            ->afterCreating(function (Materia $materia): void {
                if (! $materia->relationLoaded('carreraParaMalla')) {
                    return;
                }

                $carrera = $materia->getRelation('carreraParaMalla');
                MallaCurricular::firstOrCreate([
                    'carrera_id' => $carrera->id,
                    'materia_id' => $materia->id,
                ]);
                $materia->unsetRelation('carreraParaMalla');
            });
    }
}
