<?php

namespace Database\Factories;

use App\Models\Docente;
use App\Models\Grupo;
use App\Models\MallaCurricular;
use App\Models\Materia;
use App\Models\Propuesta;
use App\Models\PropuestaDesignacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PropuestaDesignacion> */
class PropuestaDesignacionFactory extends Factory
{
    protected $model = PropuestaDesignacion::class;

    public function definition(): array
    {
        return [
            'propuesta_id' => Propuesta::factory(),
            'docente_id' => Docente::factory(),
            'materia_id' => Materia::factory(),
            'grupo_id' => Grupo::factory(),
            'malla_curricular_id' => MallaCurricular::factory(),
            'estado' => 'propuesta',
            'horas_pagadas' => fn (array $attributes): int => (int) Materia::query()
                ->findOrFail($attributes['materia_id'])->horas,
            'horas_no_pagadas' => 0,
            'observacion_remuneracion' => null,
        ];
    }

    public function aprobadaPreviamente(): static
    {
        return $this->state(['estado' => 'aprobada_previamente']);
    }

    public function oficial(): static
    {
        return $this->state(['estado' => 'oficial']);
    }

    public function configure(): static
    {
        return $this->afterCreating(function (PropuestaDesignacion $fila): void {
            $propuesta = Propuesta::query()->findOrFail($fila->propuesta_id);
            $malla = MallaCurricular::query()
                ->where('carrera_id', $propuesta->carrera_id)
                ->first();

            if (! $malla) {
                $materia = Materia::factory()->create();
                $malla = MallaCurricular::create([
                    'carrera_id' => $propuesta->carrera_id,
                    'materia_id' => $materia->id,
                ]);
            }

            $grupo = Grupo::query()->where('malla_curricular_id', $malla->id)->first()
                ?? Grupo::factory()->create(['malla_curricular_id' => $malla->id]);
            $docente = Docente::query()->where('carrera_origen_id', $propuesta->carrera_id)->first()
                ?? Docente::factory()->forCarrera($propuesta->carrera_id)->create();

            $fila->update([
                'docente_id' => $docente->id,
                'materia_id' => $malla->materia_id,
                'grupo_id' => $grupo->id,
                'malla_curricular_id' => $malla->id,
                'horas_pagadas' => $malla->materia->horas,
            ]);
        });
    }
}
