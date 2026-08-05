<?php

namespace Database\Factories;

use App\Models\Carrera;
use App\Models\MallaCurricular;
use App\Models\Materia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MallaCurricular>
 */
class MallaCurricularFactory extends Factory
{
    protected $model = MallaCurricular::class;

    public function definition(): array
    {
        return [
            'carrera_id' => Carrera::factory(),
            'materia_id' => Materia::factory(),
        ];
    }

    public function forCarrera(Carrera|int $carrera): static
    {
        return $this->state(['carrera_id' => $carrera]);
    }

    public function forMateria(Materia|int $materia): static
    {
        return $this->state(['materia_id' => $materia]);
    }
}
