<?php

namespace Database\Factories;

use App\Models\Carrera;
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
            'codigo' => (string) fake()->unique()->numberBetween(1, 999),
            'estado' => 'habilitado',
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Grupo $grupo): void {
            if ($grupo->getAttribute('materia_id') === null) {
                return;
            }

            $malla = MallaCurricular::find($grupo->malla_curricular_id);

            if ($malla?->materia_id !== (int) $grupo->getAttribute('materia_id')) {
                $malla = MallaCurricular::firstOrCreate(
                    ['materia_id' => $grupo->getAttribute('materia_id')],
                    ['carrera_id' => Carrera::factory()->create()->id],
                );
            }

            $grupo->malla_curricular_id = $malla->id;
            $grupo->offsetUnset('materia_id');
        });
    }
}
