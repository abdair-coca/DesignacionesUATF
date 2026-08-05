<?php

namespace Database\Factories;

use App\Models\Docente;
use App\Models\Grupo;
use App\Models\MallaCurricular;
use App\Models\Materia;
use App\Models\PropuestaVersion;
use App\Models\PropuestaVersionDesignacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PropuestaVersionDesignacion> */
class PropuestaVersionDesignacionFactory extends Factory
{
    protected $model = PropuestaVersionDesignacion::class;

    public function definition(): array
    {
        return [
            'propuesta_version_id' => PropuestaVersion::factory(),
            'docente_id' => fn (array $attributes): int => self::context($attributes)['docente']->id,
            'docente_nombre' => fn (array $attributes): string => self::context($attributes)['docente']->nombre,
            'materia_id' => fn (array $attributes): int => self::context($attributes)['materia']->id,
            'materia_sigla' => fn (array $attributes): string => self::context($attributes)['materia']->sigla,
            'materia_nombre' => fn (array $attributes): string => self::context($attributes)['materia']->nombre,
            'materia_horas' => fn (array $attributes): int => (int) self::context($attributes)['materia']->horas,
            'horas_pagadas' => fn (array $attributes): int => (int) self::context($attributes)['materia']->horas,
            'horas_no_pagadas' => 0,
            'observacion_remuneracion' => null,
            'carrera_id' => fn (array $attributes): int => self::context($attributes)['carrera']->id,
            'carrera_sigla' => fn (array $attributes): string => self::context($attributes)['carrera']->sigla,
            'carrera_nombre' => fn (array $attributes): string => self::context($attributes)['carrera']->nombre,
            'grupo_id' => fn (array $attributes): int => self::context($attributes)['grupo']->id,
            'grupo_codigo' => fn (array $attributes): string => (string) self::context($attributes)['grupo']->codigo,
            'malla_curricular_id' => fn (array $attributes): int => self::context($attributes)['malla']->id,
            'gestion_id' => fn (array $attributes): int => self::context($attributes)['propuesta']->gestion_id,
            'gestion_nombre' => fn (array $attributes): string => self::context($attributes)['propuesta']->gestion->nombre,
            'periodo_id' => fn (array $attributes): int => self::context($attributes)['propuesta']->periodo_id,
            'periodo_nombre' => fn (array $attributes): string => self::context($attributes)['propuesta']->periodo->nombre,
            'estado' => 'propuesta',
            'decision' => null,
            'observacion' => null,
        ];
    }

    public function aprobada(): static
    {
        return $this->state(['decision' => 'aprobada']);
    }

    public function observada(string $observacion = 'Observación sintética'): static
    {
        return $this->state([
            'decision' => 'observada',
            'observacion' => $observacion,
        ]);
    }

    /** @return array{propuesta: object, carrera: object, materia: Materia, malla: MallaCurricular, grupo: Grupo, docente: Docente} */
    private static function context(array $attributes): array
    {
        $version = PropuestaVersion::query()->with('propuesta')->findOrFail($attributes['propuesta_version_id']);
        $propuesta = $version->propuesta;
        $carrera = $propuesta->carrera;
        $malla = MallaCurricular::query()->where('carrera_id', $carrera->id)->first();

        if (! $malla) {
            $materia = Materia::factory()->create();
            $malla = MallaCurricular::create([
                'carrera_id' => $carrera->id,
                'materia_id' => $materia->id,
            ]);
        }

        $grupo = Grupo::query()->where('malla_curricular_id', $malla->id)->first()
            ?? Grupo::factory()->create(['malla_curricular_id' => $malla->id]);
        $docente = Docente::query()->where('carrera_origen_id', $carrera->id)->first()
            ?? Docente::factory()->forCarrera($carrera->id)->create();

        return [
            'propuesta' => $propuesta,
            'carrera' => $carrera,
            'materia' => $malla->materia,
            'malla' => $malla,
            'grupo' => $grupo,
            'docente' => $docente,
        ];
    }
}
