<?php

namespace App\Services;

use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Periodo;
use App\Models\Propuesta;
use App\Models\PropuestaDesignacion;
use App\Models\PropuestaEvento;
use App\Models\PropuestaVersion;
use App\Models\PropuestaVersionDesignacion;
use App\Models\User;
use App\Notifications\PropuestaActualizadaNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PropuestaService
{
    public function crearBorrador(User $user, Gestion $gestion, Periodo $periodo, ?string $descripcion): Propuesta
    {
        $this->validarGestionActual($gestion);

        return DB::transaction(function () use ($user, $gestion, $periodo, $descripcion) {
            $propuesta = Propuesta::firstOrCreate(
                [
                    'carrera_id' => $user->carrera_id,
                    'gestion_id' => $gestion->id,
                    'periodo_id' => $periodo->id,
                ],
                [
                    'creado_por' => $user->id,
                    'descripcion' => $descripcion,
                    'estado' => 'borrador',
                ],
            );

            if ($propuesta->creado_por !== $user->id) {
                throw ValidationException::withMessages([
                    'gestion_id' => 'Ya existe una propuesta para esta carrera, gestión y período.',
                ]);
            }

            return $propuesta;
        });
    }

    public function guardarCambios(Propuesta $propuesta, array $cambios): void
    {
        $this->validarGestionActual($propuesta->gestion);

        DB::transaction(function () use ($propuesta, $cambios) {
            $propuesta = Propuesta::lockForUpdate()->findOrFail($propuesta->id);
            $this->asegurarEditable($propuesta);

            $grupos = Grupo::with('mallaCurricular.materia')
                ->whereIn('id', collect($cambios)->pluck('grupo_id')->unique())
                ->whereHas('mallaCurricular', fn ($query) => $query->where('carrera_id', $propuesta->carrera_id))
                ->get()
                ->keyBy('id');

            foreach ($cambios as $cambio) {
                $grupo = $grupos->get($cambio['grupo_id']);
                $designacionExistente = $propuesta->designaciones()->where('grupo_id', $cambio['grupo_id'])->first();

                if (! $grupo || (int) $grupo->mallaCurricular->materia_id !== (int) $cambio['materia_id']) {
                    throw ValidationException::withMessages([
                        'cambios' => 'Cada grupo debe pertenecer a la carrera y materia de la propuesta.',
                    ]);
                }

                if ($designacionExistente?->estado === 'aprobada_previamente') {
                    if (isset($cambio['docente_id']) && (int) $cambio['docente_id'] !== (int) $designacionExistente->docente_id) {
                        throw ValidationException::withMessages([
                            'cambios' => 'Las filas aprobadas previamente no se pueden modificar.',
                        ]);
                    }

                    continue;
                }

                if (empty($cambio['docente_id'])) {
                    $propuesta->designaciones()->where('grupo_id', $grupo->id)->delete();

                    continue;
                }

                $horasOficiales = (int) $grupo->mallaCurricular->materia->horas;
                [$horasPagadas, $horasNoPagadas] = $this->validarDistribucion(
                    $horasOficiales,
                    $cambio['horas_pagadas'] ?? $horasOficiales,
                    $cambio['horas_no_pagadas'] ?? 0,
                );

                PropuestaDesignacion::updateOrCreate(
                    ['propuesta_id' => $propuesta->id, 'grupo_id' => $grupo->id],
                    [
                        'docente_id' => $cambio['docente_id'],
                        'materia_id' => $grupo->mallaCurricular->materia_id,
                        'malla_curricular_id' => $grupo->malla_curricular_id,
                        'estado' => 'propuesta',
                        'horas_pagadas' => $horasPagadas,
                        'horas_no_pagadas' => $horasNoPagadas,
                        'observacion_remuneracion' => blank($cambio['observacion_remuneracion'] ?? null)
                            ? null
                            : trim((string) $cambio['observacion_remuneracion']),
                    ],
                );
            }
        });
    }

    public function enviar(Propuesta $propuesta, User $user): PropuestaVersion
    {
        $this->validarGestionActual($propuesta->gestion);

        return DB::transaction(function () use ($propuesta, $user) {
            $propuesta = Propuesta::with([
                'carrera',
                'gestion',
                'periodo',
                'designaciones.docente',
                'designaciones.materia',
                'designaciones.grupo',
                'designaciones.mallaCurricular',
            ])->lockForUpdate()->findOrFail($propuesta->id);

            $this->asegurarEditable($propuesta);
            $filas = $propuesta->designaciones;

            if ($filas->isEmpty()) {
                throw ValidationException::withMessages([
                    'propuesta' => 'El borrador debe contener al menos una designación antes de enviarse.',
                ]);
            }

            $gruposEsperados = Grupo::query()
                ->where('estado', 'habilitado')
                ->whereHas('mallaCurricular', fn ($query) => $query->where('carrera_id', $propuesta->carrera_id))
                ->pluck('id');
            $gruposEnPropuesta = $filas->pluck('grupo_id');
            $gruposSinDocente = $gruposEsperados->diff($gruposEnPropuesta);

            if ($gruposSinDocente->isNotEmpty() || $filas->contains(fn (PropuestaDesignacion $fila): bool => $fila->docente_id === null)) {
                throw ValidationException::withMessages([
                    'propuesta' => 'Todas las materias y grupos habilitados deben tener un docente antes de enviar la propuesta.',
                ]);
            }

            $numero = ((int) PropuestaVersion::where('propuesta_id', $propuesta->id)->max('numero')) + 1;
            $version = PropuestaVersion::create([
                'propuesta_id' => $propuesta->id,
                'numero' => $numero,
                'estado' => 'pendiente',
                'enviado_por' => $user->id,
                'enviado_en' => now(),
            ]);

            foreach ($filas as $fila) {
                $this->crearSnapshot($version, $propuesta, $fila);
            }

            PropuestaEvento::create([
                'propuesta_id' => $propuesta->id,
                'propuesta_version_id' => $version->id,
                'usuario_id' => $user->id,
                'tipo' => 'enviada',
                'datos' => ['numero_version' => $version->numero, 'filas' => $filas->count()],
                'ocurrio_en' => now(),
            ]);

            $version->setRelation('propuesta', $propuesta);
            $this->notificarVicerrectorado($version, $version->numero === 1 ? 'enviada' : 'reenviada');

            return $version;
        });
    }

    public function retirar(PropuestaVersion $version, User $user): void
    {
        DB::transaction(function () use ($version, $user) {
            $version = PropuestaVersion::lockForUpdate()->findOrFail($version->id);
            $propuesta = $version->propuesta;

            if (
                $version->estado !== 'pendiente'
                || $version->enviado_por !== $user->id
                || ! $propuesta
                || ! $user->administraCarrera($propuesta->carrera_id)
            ) {
                throw ValidationException::withMessages([
                    'version' => 'Solo el Director que envió una versión pendiente puede retirarla.',
                ]);
            }

            $version->update([
                'estado' => 'retirada',
                'retirado_por' => $user->id,
                'retirado_en' => now(),
            ]);

            PropuestaEvento::create([
                'propuesta_id' => $propuesta->id,
                'propuesta_version_id' => $version->id,
                'usuario_id' => $user->id,
                'tipo' => 'retirada',
                'datos' => ['numero_version' => $version->numero],
                'ocurrio_en' => now(),
            ]);

            $version->load('propuesta.carrera', 'propuesta.gestion', 'propuesta.periodo');
            $this->notificarVicerrectorado($version, 'retirada');
        });
    }

    private function validarGestionActual(Gestion $gestion): void
    {
        if (! $gestion->es_actual) {
            throw ValidationException::withMessages([
                'gestion_id' => 'Solo se puede editar o enviar la propuesta de la gestión actual.',
            ]);
        }
    }

    private function asegurarEditable(Propuesta $propuesta): void
    {
        if ($propuesta->estado !== 'borrador' || $propuesta->versiones()->where('estado', 'pendiente')->exists()) {
            throw ValidationException::withMessages([
                'propuesta' => 'La propuesta está enviada y no puede modificarse hasta retirar su versión pendiente.',
            ]);
        }
    }

    private function crearSnapshot(PropuestaVersion $version, Propuesta $propuesta, PropuestaDesignacion $fila): void
    {
        $materia = $fila->materia;
        $grupo = $fila->grupo;
        $docente = $fila->docente;

        PropuestaVersionDesignacion::create([
            'propuesta_version_id' => $version->id,
            'docente_id' => $docente?->id,
            'docente_nombre' => $docente?->nombre,
            'materia_id' => $materia->id,
            'materia_sigla' => $materia->sigla,
            'materia_nombre' => $materia->nombre,
            'materia_horas' => $materia->horas,
            'carrera_id' => $propuesta->carrera->id,
            'carrera_sigla' => $propuesta->carrera->sigla,
            'carrera_nombre' => $propuesta->carrera->nombre,
            'grupo_id' => $grupo->id,
            'grupo_codigo' => (string) $grupo->codigo,
            'malla_curricular_id' => $fila->malla_curricular_id,
            'gestion_id' => $propuesta->gestion->id,
            'gestion_nombre' => $propuesta->gestion->nombre,
            'periodo_id' => $propuesta->periodo->id,
            'periodo_nombre' => $propuesta->periodo->nombre,
            'estado' => $fila->estado,
            'horas_pagadas' => $fila->horas_pagadas,
            'horas_no_pagadas' => $fila->horas_no_pagadas,
            'observacion_remuneracion' => $fila->observacion_remuneracion,
        ]);
    }

    private function validarDistribucion(int $horasOficiales, mixed $horasPagadas, mixed $horasNoPagadas): array
    {
        $pagadas = filter_var($horasPagadas, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
        $noPagadas = filter_var($horasNoPagadas, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);

        if ($pagadas === null || $noPagadas === null || $pagadas < 0 || $noPagadas < 0) {
            throw ValidationException::withMessages([
                'cambios' => 'Las horas pagadas y no pagadas deben ser enteros no negativos.',
            ]);
        }

        if ($pagadas > $horasOficiales) {
            throw ValidationException::withMessages([
                'cambios' => 'Las horas pagadas no pueden superar las horas oficiales.',
            ]);
        }

        if ($pagadas + $noPagadas < $horasOficiales) {
            throw ValidationException::withMessages([
                'cambios' => 'La distribución debe cubrir todas las horas oficiales.',
            ]);
        }

        return [$pagadas, $noPagadas];
    }

    private function notificarVicerrectorado(PropuestaVersion $version, string $evento): void
    {
        User::query()
            ->where('rol', User::ROL_VICERRECTORADO)
            ->get()
            ->each(fn (User $usuario) => $usuario->notify(new PropuestaActualizadaNotification($version, $evento)));
    }
}
