<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VerifyAcademicNormalization extends Command
{
    protected $signature = 'academico:verificar-normalizacion
                            {--muestra=10 : Cantidad maxima de IDs mostrados por hallazgo}';

    protected $description = 'Verifica la consistencia de malla, grupos y designaciones antes de retirar columnas heredadas.';

    public function handle(): int
    {
        $columnasFaltantes = $this->columnasFaltantes();

        if ($columnasFaltantes->isNotEmpty()) {
            $this->error('No se puede verificar la normalizacion: faltan migraciones de Fase 1.');
            $this->line('Columnas requeridas: '.$columnasFaltantes->implode(', ').'.');
            $this->line('Aplique las migraciones en una base validada y vuelva a ejecutar el comando.');

            return self::FAILURE;
        }

        $muestra = max(1, (int) $this->option('muestra'));
        $hallazgos = [
            'Materias sin malla curricular' => $this->ids(
                'SELECT materias.id
                 FROM materias
                 LEFT JOIN malla_curricular AS mallas ON mallas.materia_id = materias.id
                 WHERE mallas.id IS NULL
                 ORDER BY materias.id',
            ),
            'Mallas incompatibles con la carrera historica de la materia' => $this->ids(
                'SELECT mallas.id
                 FROM malla_curricular AS mallas
                 JOIN materias ON materias.id = mallas.materia_id
                 WHERE materias.carrera_id IS NOT NULL
                   AND materias.carrera_id <> mallas.carrera_id
                 ORDER BY mallas.id',
            ),
            'Grupos sin malla curricular' => $this->ids(
                'SELECT grupos.id
                 FROM grupos
                 WHERE grupos.malla_curricular_id IS NULL
                 ORDER BY grupos.id',
            ),
            'Grupos cuya materia heredada no coincide con la malla' => $this->ids(
                'SELECT grupos.id
                 FROM grupos
                 JOIN malla_curricular AS mallas ON mallas.id = grupos.malla_curricular_id
                 WHERE grupos.materia_id <> mallas.materia_id
                 ORDER BY grupos.id',
            ),
            'Grupos con codigo no numerico positivo' => $this->ids(
                "SELECT grupos.id
                 FROM grupos
                 WHERE grupos.codigo !~ '^[1-9][0-9]*$'
                 ORDER BY grupos.id",
            ),
            'Codigos de grupo duplicados dentro de una malla' => $this->groupIds(
                'SELECT grupos.malla_curricular_id, grupos.codigo, array_agg(grupos.id ORDER BY grupos.id) AS ids
                 FROM grupos
                 GROUP BY grupos.malla_curricular_id, grupos.codigo
                 HAVING COUNT(*) > 1
                 ORDER BY grupos.malla_curricular_id, grupos.codigo',
            ),
            'Designaciones cuya malla no coincide con la del grupo' => $this->ids(
                'SELECT designaciones.id
                 FROM designaciones
                 JOIN grupos ON grupos.id = designaciones."Id_grupo"
                 WHERE designaciones.malla_curricular_id <> grupos.malla_curricular_id
                 ORDER BY designaciones.id',
            ),
            'Designaciones cuya materia no coincide con la malla' => $this->ids(
                'SELECT designaciones.id
                 FROM designaciones
                 JOIN malla_curricular AS mallas ON mallas.id = designaciones.malla_curricular_id
                 WHERE designaciones."Id_materia" <> mallas.materia_id
                 ORDER BY designaciones.id',
            ),
            'Designaciones activas duplicadas por grupo, gestion y periodo' => $this->groupIds(
                'SELECT designaciones."Id_grupo" AS grupo_id,
                        designaciones."Id_gestion" AS gestion_id,
                        designaciones."Id_periodo" AS periodo_id,
                        array_agg(designaciones.id ORDER BY designaciones.id) AS ids
                 FROM designaciones
                 WHERE designaciones.estado <> \'rechazada\'
                 GROUP BY designaciones."Id_grupo", designaciones."Id_gestion", designaciones."Id_periodo"
                 HAVING COUNT(*) > 1
                 ORDER BY grupo_id, gestion_id, periodo_id',
            ),
        ];

        $this->table(['Entidad', 'Registros'], [
            ['Materias', DB::table('materias')->count()],
            ['Mallas curriculares', DB::table('malla_curricular')->count()],
            ['Grupos', DB::table('grupos')->count()],
            ['Designaciones', DB::table('designaciones')->count()],
        ]);

        $fallos = collect($hallazgos)->filter(fn (Collection $registros) => $registros->isNotEmpty());

        if ($fallos->isEmpty()) {
            $this->info('Verificacion aprobada: no se detectaron inconsistencias de normalizacion.');

            return self::SUCCESS;
        }

        foreach ($fallos as $descripcion => $registros) {
            $this->error("{$descripcion}: {$registros->count()}.");
            $this->line('  Muestra: '.$registros->take($muestra)->implode('; '));
        }

        return self::FAILURE;
    }

    private function ids(string $sql): Collection
    {
        return collect(DB::select($sql))
            ->pluck('id')
            ->map(fn (int|string $id) => (string) $id);
    }

    private function columnasFaltantes(): Collection
    {
        return collect([
            'grupos.malla_curricular_id' => Schema::hasColumn('grupos', 'malla_curricular_id'),
            'designaciones.malla_curricular_id' => Schema::hasColumn('designaciones', 'malla_curricular_id'),
        ])
            ->filter(fn (bool $existe) => ! $existe)
            ->keys();
    }

    private function groupIds(string $sql): Collection
    {
        return collect(DB::select($sql))
            ->map(function (object $registro): string {
                $contexto = collect((array) $registro)
                    ->except('ids')
                    ->map(fn (mixed $valor, string $campo) => "{$campo}={$valor}")
                    ->implode(', ');

                return "{$contexto}: {$registro->ids}";
            });
    }
}
