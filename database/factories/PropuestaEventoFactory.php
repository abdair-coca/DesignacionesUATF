<?php

namespace Database\Factories;

use App\Models\Propuesta;
use App\Models\PropuestaEvento;
use App\Models\PropuestaVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PropuestaEvento> */
class PropuestaEventoFactory extends Factory
{
    protected $model = PropuestaEvento::class;

    public function definition(): array
    {
        return [
            'propuesta_id' => Propuesta::factory(),
            'propuesta_version_id' => null,
            'usuario_id' => fn (array $attributes) => User::factory()->director(
                Propuesta::query()->findOrFail($attributes['propuesta_id'])->carrera_id,
            ),
            'tipo' => 'enviada',
            'datos' => [],
            'ocurrio_en' => now(),
        ];
    }

    public function forVersion(PropuestaVersion|int $version): static
    {
        return $this->state(function () use ($version): array {
            $versionId = $version instanceof PropuestaVersion ? $version->id : $version;
            $versionModel = $version instanceof PropuestaVersion
                ? $version
                : PropuestaVersion::query()->findOrFail($versionId);

            return [
                'propuesta_id' => $versionModel->propuesta_id,
                'propuesta_version_id' => $versionId,
            ];
        });
    }
}
