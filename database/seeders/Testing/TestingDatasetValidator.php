<?php

namespace Database\Seeders\Testing;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class TestingDatasetValidator
{
    public static function validate(): array
    {
        $errors = [];
        $allowedRoles = ['director_carrera', 'vicerrectorado'];
        $allowedLegacyStates = ['propuesta', 'aprobada', 'rechazada'];
        $allowedProposalStates = ['borrador', 'oficial'];
        $allowedVersionStates = ['pendiente', 'retirada', 'observada', 'aprobada'];
        $allowedRowStates = ['propuesta', 'aprobada_previamente', 'oficial'];
        $allowedDecisions = ['aprobada', 'observada'];

        self::expectZero($errors, 'roles inválidos', DB::table('users')->whereNotIn('rol', $allowedRoles)->count());
        self::expectZero($errors, 'director sin carrera', DB::table('users')->where('rol', 'director_carrera')->whereNull('carrera_id')->count());
        self::expectZero($errors, 'Vicerrectorado con carrera', DB::table('users')->where('rol', 'vicerrectorado')->whereNotNull('carrera_id')->count());
        self::expectZero($errors, 'emails fuera de example.test', DB::table('users')->where('email', 'not like', '%@example.test')->count());
        self::expectZero($errors, 'emails duplicados', DB::table('users')->select('email')->groupBy('email')->havingRaw('COUNT(*) > 1')->count());
        self::expectZero($errors, 'estados legado inválidos', DB::table('designaciones')->whereNotIn('estado', $allowedLegacyStates)->count());
        self::expectZero($errors, 'estados propuesta inválidos', DB::table('propuestas')->whereNotIn('estado', $allowedProposalStates)->count());
        self::expectZero($errors, 'estados versión inválidos', DB::table('propuesta_versiones')->whereNotIn('estado', $allowedVersionStates)->count());
        self::expectZero($errors, 'estados fila inválidos', DB::table('propuesta_designaciones')->whereNotIn('estado', $allowedRowStates)->count());
        self::expectZero($errors, 'decisiones inválidas', DB::table('propuesta_version_decisiones')->whereNotIn('decision', $allowedDecisions)->count());
        self::expectZero($errors, 'designaciones con grupo/malla incompatibles', DB::table('designaciones as d')
            ->join('grupos as g', 'g.id', '=', 'd.Id_grupo')
            ->whereColumn('d.malla_curricular_id', '!=', 'g.malla_curricular_id')
            ->count());
        self::expectZero($errors, 'filas de propuesta con grupo/malla incompatibles', DB::table('propuesta_designaciones as d')
            ->join('grupos as g', 'g.id', '=', 'd.grupo_id')
            ->whereColumn('d.malla_curricular_id', '!=', 'g.malla_curricular_id')
            ->count());
        self::expectZero($errors, 'filas de propuesta con materia/malla incompatibles', DB::table('propuesta_designaciones as d')
            ->join('malla_curricular as m', 'm.id', '=', 'd.malla_curricular_id')
            ->whereColumn('d.materia_id', '!=', 'm.materia_id')
            ->count());
        self::expectZero($errors, 'versiones pendientes duplicadas', DB::table('propuesta_versiones')
            ->where('estado', 'pendiente')
            ->select('propuesta_id')
            ->groupBy('propuesta_id')
            ->havingRaw('COUNT(*) > 1')
            ->count());
        self::expectZero($errors, 'snapshots duplicados por grupo', DB::table('propuesta_version_designaciones')
            ->select(['propuesta_version_id', 'grupo_id'])
            ->groupBy(['propuesta_version_id', 'grupo_id'])
            ->havingRaw('COUNT(*) > 1')
            ->count());
        self::expectZero($errors, 'decisiones duplicadas por snapshot', DB::table('propuesta_version_decisiones')
            ->select('propuesta_version_designacion_id')
            ->groupBy('propuesta_version_designacion_id')
            ->havingRaw('COUNT(*) > 1')
            ->count());

        if ($errors !== []) {
            throw new RuntimeException('Testing dataset validation failed: '.implode('; ', $errors));
        }

        return [
            'ok' => true,
            'counts' => TestingDatasetSupport::counts(),
            'checks' => [
                'foreign_keys' => 'database-enforced',
                'roles' => 'valid',
                'states' => 'valid',
                'relations' => 'valid',
                'synthetic_emails' => 'example.test',
                'invalid_rows_inserted' => false,
            ],
        ];
    }

    private static function expectZero(array &$errors, string $label, int $count): void
    {
        if ($count > 0) {
            $errors[] = "{$label}: {$count}";
        }
    }
}
