<?php

namespace Database\Seeders\Testing;

use App\Models\Carrera;
use App\Models\Designacion;
use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\MallaCurricular;
use App\Models\Materia;
use App\Models\Periodo;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

final class TestingDatasetSupport
{
    public static function ensureCareers(int $count): Collection
    {
        $careers = new Collection;
        for ($index = 1; $index <= $count; $index++) {
            $sigla = sprintf('TST%02d', $index);
            $careers->push(Carrera::firstOrCreate(
                ['sigla' => $sigla],
                ['nombre' => "Carrera Sintética {$index}"],
            ));
        }

        return $careers;
    }

    public static function seed(array $config): array
    {
        $careers = self::ensureCareers($config['careers']);
        $gestiones = self::seedGestiones($config['gestiones']);
        $periodos = self::seedPeriodos($config['periodos']);
        TestingUsersSeeder::seedForCareers($careers, $config['users']);

        $catalog = self::seedCatalog($careers, $config);
        self::seedLegacyDesignaciones($catalog, $gestiones, $periodos);

        return [
            'careers' => $careers,
            'gestiones' => $gestiones,
            'periodos' => $periodos,
            'catalog' => $catalog,
            'counts' => self::counts(),
        ];
    }

    public static function seedEdgeCases(array $config): array
    {
        $summary = self::seed($config);
        $career = Carrera::where('sigla', 'TST01')->firstOrFail();
        $materia = Materia::where('sigla', 'TST01-001')->firstOrFail();
        $docente = Docente::where('carrera_origen_id', $career->id)->firstOrFail();
        $career->update(['nombre' => "Carrera Ñandú O'Connor — Δ"]);
        $materia->update(['nombre' => 'Álgebra Ñandú — O\'Connor — Δ']);
        $docente->update(['nombre' => "Núñez O'Connor — Δ docente"]);
        Docente::firstOrCreate(
            ['ci' => '999999999'],
            ['nombre' => 'Docente sin carrera opcional', 'carrera_origen_id' => null],
        );

        $grupo = Grupo::where('malla_curricular_id', $materia->mallaCurricular()->where('carrera_id', $career->id)->value('id'))
            ->orderBy('id')
            ->first();
        $grupo?->update(['estado' => 'deshabilitado']);

        Designacion::query()->oldest('id')->first()?->update([
            'created_at' => '2000-01-01 00:00:00',
            'updated_at' => '2000-01-01 00:00:00',
        ]);

        return [
            ...$summary,
            'counts' => self::counts(),
            'edge_cases' => [
                'unicode_and_punctuation' => true,
                'nullable_teacher_origin' => true,
                'disabled_group' => $grupo !== null,
                'invalid_rows_inserted' => false,
                'soft_deletes' => false,
            ],
        ];
    }

    private static function seedGestiones(int $count): SupportCollection
    {
        $names = ['2026', '2025', '2024'];
        for ($index = 3; $index < $count; $index++) {
            $names[] = (string) (2027 + $index - 3);
        }

        Gestion::query()->update(['es_actual' => false]);

        return collect($names)->map(fn (string $name, int $index) => Gestion::updateOrCreate(
            ['nombre' => $name],
            ['es_actual' => $index === 0],
        ));
    }

    private static function seedPeriodos(int $count): SupportCollection
    {
        $names = ['1', '2', 'Verano'];
        for ($index = 3; $index < $count; $index++) {
            $names[] = 'P'.($index + 1);
        }

        return collect($names)->map(fn (string $name) => Periodo::firstOrCreate(['nombre' => $name]));
    }

    private static function seedCatalog(Collection $careers, array $config): array
    {
        foreach ($careers as $careerIndex => $career) {
            for ($subjectIndex = 1; $subjectIndex <= $config['subjects']; $subjectIndex++) {
                $sigla = sprintf('%s-%03d', $career->sigla, $subjectIndex);
                $materia = Materia::firstOrCreate(
                    ['sigla' => $sigla],
                    [
                        'nombre' => "Materia Sintética {$careerIndex}-{$subjectIndex}",
                        'horas' => [2, 4, 6, 8][$subjectIndex % 4],
                    ],
                );
                $malla = MallaCurricular::firstOrCreate([
                    'carrera_id' => $career->id,
                    'materia_id' => $materia->id,
                ]);

                for ($groupIndex = 1; $groupIndex <= $config['groups']; $groupIndex++) {
                    Grupo::firstOrCreate([
                        'malla_curricular_id' => $malla->id,
                        'codigo' => (string) $groupIndex,
                    ], ['estado' => 'habilitado']);
                }
            }
        }

        $teacherRows = [];
        $now = '2026-01-01 00:00:00';
        foreach ($careers as $careerIndex => $career) {
            for ($teacherIndex = 1; $teacherIndex <= $config['teachers']; $teacherIndex++) {
                $teacherRows[] = [
                    'nombre' => "Docente Sintético {$career->sigla} {$teacherIndex}",
                    'ci' => sprintf('7%02d%06d', $careerIndex + 1, $teacherIndex),
                    'carrera_origen_id' => $career->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        foreach (array_chunk($teacherRows, 1000) as $chunk) {
            DB::table('docentes')->insertOrIgnore($chunk);
        }

        $groupsByCareer = Grupo::query()
            ->with('mallaCurricular.materia', 'mallaCurricular.carrera')
            ->where('estado', 'habilitado')
            ->get()
            ->groupBy(fn (Grupo $group): int => $group->mallaCurricular->carrera_id);
        $teachersByCareer = Docente::query()
            ->whereIn('carrera_origen_id', $careers->pluck('id'))
            ->get()
            ->groupBy('carrera_origen_id')
            ->map(fn (Collection $teachers) => $teachers->pluck('id')->values());
        $directorsByCareer = User::query()
            ->where('rol', User::ROL_DIRECTOR_CARRERA)
            ->whereIn('carrera_id', $careers->pluck('id'))
            ->get()
            ->groupBy('carrera_id')
            ->map(fn (Collection $users) => $users->first()->id);

        return compact('careers', 'groupsByCareer', 'teachersByCareer', 'directorsByCareer');
    }

    private static function seedLegacyDesignaciones(array $catalog, SupportCollection $gestiones, SupportCollection $periodos): void
    {
        $rows = [];
        $counter = 0;
        $baseDate = CarbonImmutable::parse('2020-01-01 00:00:00');
        $vicerrectorado = User::where('rol', User::ROL_VICERRECTORADO)->value('id');

        foreach ($catalog['groupsByCareer'] as $careerId => $groups) {
            $teachers = $catalog['teachersByCareer']->get($careerId, collect())->values();
            $directorId = $catalog['directorsByCareer']->get($careerId);
            if ($teachers->isEmpty() || $directorId === null) {
                continue;
            }

            foreach ($groups as $group) {
                foreach ($gestiones as $gestion) {
                    foreach ($periodos as $periodo) {
                        $state = match ($counter % 10) {
                            0 => 'rechazada',
                            1, 2 => 'propuesta',
                            default => 'aprobada',
                        };
                        $createdAt = $baseDate->addDays($counter % 1500)->toDateTimeString();
                        $rows[] = [
                            'Id_docente' => $teachers[$counter % $teachers->count()],
                            'Id_materia' => $group->mallaCurricular->materia_id,
                            'Id_grupo' => $group->id,
                            'malla_curricular_id' => $group->malla_curricular_id,
                            'Id_gestion' => $gestion->id,
                            'Id_periodo' => $periodo->id,
                            'estado' => $state,
                            'creado_por' => $directorId,
                            'aprobado_por' => $state === 'aprobada' ? $vicerrectorado : null,
                            'motivo_rechazo' => $state === 'rechazada' ? 'Caso rechazado sintético' : null,
                            'created_at' => $createdAt,
                            'updated_at' => $createdAt,
                        ];
                        $counter++;

                        if (count($rows) >= 1000) {
                            DB::table('designaciones')->insert($rows);
                            $rows = [];
                        }
                    }
                }
            }
        }

        if ($rows !== []) {
            DB::table('designaciones')->insert($rows);
        }
    }

    public static function counts(): array
    {
        $tables = [
            'users', 'carreras', 'materias', 'malla_curricular', 'grupos', 'docentes',
            'gestiones', 'periodos', 'designaciones', 'notifications',
        ];

        return collect($tables)->mapWithKeys(fn (string $table) => [$table => DB::table($table)->count()])->all();
    }
}
