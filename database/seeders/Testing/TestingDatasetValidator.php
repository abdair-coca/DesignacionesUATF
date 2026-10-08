<?php

namespace Database\Seeders\Testing;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class TestingDatasetValidator
{
    public static function validate(): array
    {
        $errors = [];
        $allowedRoles = [User::ROL_DIRECTOR_CARRERA, User::ROL_DECANATURA, User::ROL_VICERRECTORADO];
        $allowedLegacyStates = ['propuesta', 'aprobada', 'rechazada'];

        self::expectZero($errors, 'roles inválidos', DB::table('users')->whereNotIn('rol', $allowedRoles)->count());
        self::expectZero($errors, 'director sin carrera', DB::table('users')->where('rol', User::ROL_DIRECTOR_CARRERA)->whereNull('carrera_id')->count());
        self::expectZero($errors, 'director con facultad', DB::table('users')->where('rol', User::ROL_DIRECTOR_CARRERA)->whereNotNull('facultad_id')->count());
        self::expectZero($errors, 'Decanatura sin facultad', DB::table('users')->where('rol', User::ROL_DECANATURA)->whereNull('facultad_id')->count());
        self::expectZero($errors, 'Decanatura con carrera', DB::table('users')->where('rol', User::ROL_DECANATURA)->whereNotNull('carrera_id')->count());
        self::expectZero($errors, 'Vicerrectorado con carrera', DB::table('users')->where('rol', User::ROL_VICERRECTORADO)->whereNotNull('carrera_id')->count());
        self::expectZero($errors, 'Vicerrectorado con facultad', DB::table('users')->where('rol', User::ROL_VICERRECTORADO)->whereNotNull('facultad_id')->count());
        self::expectZero($errors, 'emails fuera de example.test', DB::table('users')->where('email', 'not like', '%@example.test')->count());
        self::expectZero($errors, 'emails duplicados', DB::table('users')->select('email')->groupBy('email')->havingRaw('COUNT(*) > 1')->count());
        self::expectZero($errors, 'estados legado inválidos', DB::table('designaciones')->whereNotIn('estado', $allowedLegacyStates)->count());
        self::expectZero($errors, 'designaciones con grupo/malla incompatibles', DB::table('designaciones as d')
            ->join('grupos as g', 'g.id', '=', 'd.Id_grupo')
            ->whereColumn('d.malla_curricular_id', '!=', 'g.malla_curricular_id')
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
