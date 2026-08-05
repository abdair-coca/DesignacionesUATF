<?php

namespace Database\Factories;

use App\Models\PropuestaVersionDecision;
use App\Models\PropuestaVersionDesignacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PropuestaVersionDecision> */
class PropuestaVersionDecisionFactory extends Factory
{
    protected $model = PropuestaVersionDecision::class;

    public function definition(): array
    {
        return [
            'propuesta_version_designacion_id' => PropuestaVersionDesignacion::factory(),
            'decision' => 'aprobada',
            'observacion' => null,
            'decidido_por' => User::factory()->vicerrectorado(),
            'decidido_en' => now(),
        ];
    }

    public function observada(string $observacion = 'Observación sintética'): static
    {
        return $this->state([
            'decision' => 'observada',
            'observacion' => $observacion,
        ]);
    }
}
