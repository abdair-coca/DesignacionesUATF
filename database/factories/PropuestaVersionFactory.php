<?php

namespace Database\Factories;

use App\Models\Propuesta;
use App\Models\PropuestaVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PropuestaVersion> */
class PropuestaVersionFactory extends Factory
{
    protected $model = PropuestaVersion::class;

    public function definition(): array
    {
        return [
            'propuesta_id' => Propuesta::factory(),
            'numero' => 1,
            'estado' => 'pendiente',
            'enviado_por' => fn (array $attributes) => User::factory()->director(
                Propuesta::query()->findOrFail($attributes['propuesta_id'])->carrera_id,
            ),
            'enviado_en' => now(),
            'retirado_por' => null,
            'retirado_en' => null,
            'revisado_por' => null,
            'revisado_en' => null,
            'observaciones' => null,
        ];
    }

    public function retirada(): static
    {
        return $this->state([
            'estado' => 'retirada',
            'retirado_por' => fn (array $attributes): int => $attributes['enviado_por'],
            'retirado_en' => now(),
        ]);
    }

    public function observada(): static
    {
        return $this->state([
            'estado' => 'observada',
            'revisado_por' => User::factory()->vicerrectorado(),
            'revisado_en' => now(),
        ]);
    }

    public function aprobada(): static
    {
        return $this->state([
            'estado' => 'aprobada',
            'revisado_por' => User::factory()->vicerrectorado(),
            'revisado_en' => now(),
        ]);
    }
}
