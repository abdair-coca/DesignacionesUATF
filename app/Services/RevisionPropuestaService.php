<?php

namespace App\Services;

use App\Models\PropuestaEvento;
use App\Models\PropuestaVersion;
use App\Models\PropuestaVersionDecision;
use App\Models\User;
use App\Notifications\PropuestaActualizadaNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RevisionPropuestaService
{
    public function decidir(
        PropuestaVersion $version,
        User $revisor,
        string $modo,
        ?string $observacionGeneral,
        array $decisiones,
    ): void {
        DB::transaction(function () use ($version, $revisor, $modo, $observacionGeneral, $decisiones) {
            $version = PropuestaVersion::with([
                'propuesta.designaciones',
                'designaciones.decision',
            ])->lockForUpdate()->findOrFail($version->id);

            if ($version->estado !== 'pendiente') {
                throw ValidationException::withMessages([
                    'version' => 'Esta versión ya recibió una decisión.',
                ]);
            }

            $filasRevisables = $version->designaciones
                ->where('estado', '!=', 'aprobada_previamente')
                ->values();

            if ($filasRevisables->isEmpty()) {
                throw ValidationException::withMessages([
                    'version' => 'No existen filas pendientes de decisión en esta versión.',
                ]);
            }

            $decisionesPorSnapshot = $this->resolverDecisiones($filasRevisables, $modo, $decisiones, $observacionGeneral);
            $hayObservaciones = $decisionesPorSnapshot->contains(fn (array $decision) => $decision['decision'] === 'observada');
            $filasObservadas = $decisionesPorSnapshot->where('decision', 'observada')->count();
            $filasAprobadas = $decisionesPorSnapshot->where('decision', 'aprobada')->count();

            foreach ($filasRevisables as $snapshot) {
                $decision = $decisionesPorSnapshot->get($snapshot->id);

                PropuestaVersionDecision::create([
                    'propuesta_version_designacion_id' => $snapshot->id,
                    'decision' => $decision['decision'],
                    'observacion' => $decision['observacion'],
                    'decidido_por' => $revisor->id,
                    'decidido_en' => now(),
                ]);

                $filaBorrador = $version->propuesta->designaciones->firstWhere('grupo_id', $snapshot->grupo_id);
                if ($filaBorrador && $decision['decision'] === 'aprobada') {
                    $filaBorrador->update([
                        'estado' => $hayObservaciones ? 'aprobada_previamente' : 'oficial',
                    ]);
                }
            }

            if ($hayObservaciones) {
                $version->update([
                    'estado' => 'observada',
                    'revisado_por' => $revisor->id,
                    'revisado_en' => now(),
                    'observaciones' => $observacionGeneral,
                ]);
            } else {
                $version->propuesta->designaciones()
                    ->where('estado', '!=', 'oficial')
                    ->update(['estado' => 'oficial']);
                $version->propuesta->update(['estado' => 'oficial']);
                $version->update([
                    'estado' => 'aprobada',
                    'revisado_por' => $revisor->id,
                    'revisado_en' => now(),
                    'observaciones' => $observacionGeneral,
                ]);
            }

            PropuestaEvento::create([
                'propuesta_id' => $version->propuesta_id,
                'propuesta_version_id' => $version->id,
                'usuario_id' => $revisor->id,
                'tipo' => $hayObservaciones ? 'observada' : 'aprobada',
                'datos' => [
                    'numero_version' => $version->numero,
                    'modo' => $modo,
                    'observaciones' => $hayObservaciones,
                ],
                'ocurrio_en' => now(),
            ]);

            $version->load('propuesta.carrera', 'propuesta.gestion', 'propuesta.periodo', 'propuesta.creador');
            $this->notificarDirector(
                $version,
                $hayObservaciones ? 'observada' : 'aprobada_final',
                [
                    'filas_observadas' => $filasObservadas,
                    'filas_aprobadas' => $filasAprobadas,
                ],
            );
        });
    }

    private function resolverDecisiones(Collection $filas, string $modo, array $decisiones, ?string $observacionGeneral): Collection
    {
        if ($modo === 'aprobar_todo') {
            return $filas->mapWithKeys(fn ($snapshot) => [
                $snapshot->id => ['decision' => 'aprobada', 'observacion' => null],
            ]);
        }

        $decisionesPorSnapshot = collect($decisiones)->keyBy('snapshot_id');

        if (
            $decisionesPorSnapshot->count() !== count($decisiones)
            || $decisionesPorSnapshot->count() !== $filas->count()
            || $filas->contains(fn ($snapshot) => ! $decisionesPorSnapshot->has($snapshot->id))
        ) {
            throw ValidationException::withMessages([
                'decisiones' => 'Debe decidir cada fila pendiente de esta versión.',
            ]);
        }

        return $filas->mapWithKeys(function ($snapshot) use ($decisionesPorSnapshot, $observacionGeneral) {
            $decision = $decisionesPorSnapshot->get($snapshot->id);
            $observacion = trim((string) ($decision['observacion'] ?? ''));

            if ($decision['decision'] === 'observada' && $observacion === '' && blank($observacionGeneral)) {
                throw ValidationException::withMessages([
                    'decisiones' => 'Cada fila observada requiere una observación por fila o una observación general.',
                ]);
            }

            if ($decision['decision'] === 'aprobada' && $observacion !== '') {
                throw ValidationException::withMessages([
                    'decisiones' => 'Una fila aprobada no puede tener observacion; selecciona Observar para devolverla al Director.',
                ]);
            }

            return [
                $snapshot->id => [
                    'decision' => $decision['decision'],
                    'observacion' => $observacion ?: null,
                ],
            ];
        });
    }

    private function notificarDirector(PropuestaVersion $version, string $evento, array $resumen = []): void
    {
        $version->propuesta->creador?->notify(new PropuestaActualizadaNotification($version, $evento, $resumen));
    }
}
