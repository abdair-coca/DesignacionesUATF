<?php

namespace Database\Seeders;

use App\Models\Carrera;
use App\Models\Designacion;
use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\MallaCurricular;
use App\Models\Materia;
use App\Models\Periodo;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDesignacionesSeeder extends Seeder
{
    public function run(): void
    {
        $carrera = Carrera::firstOrCreate(
            ['sigla' => 'INF'],
            ['nombre' => 'Ingenieria Informatica'],
        );

        $gestionActual = Gestion::updateOrCreate(['nombre' => '2026'], ['es_actual' => true]);
        Gestion::where('id', '!=', $gestionActual->id)->update(['es_actual' => false]);
        $gestionOrigen = Gestion::updateOrCreate(['nombre' => '2025'], ['es_actual' => false]);
        $periodoDestino = Periodo::firstOrCreate(['nombre' => '1']);
        $periodoOrigen = Periodo::firstOrCreate(['nombre' => '2']);

        $director = User::updateOrCreate(
            ['email' => 'director.inf@uatf.edu.bo'],
            [
                'name' => 'Director Ingenieria Informatica',
                'password' => Hash::make('password'),
                'rol' => User::ROL_DIRECTOR_CARRERA,
                'carrera_id' => $carrera->id,
            ],
        );

        User::updateOrCreate(
            ['email' => 'admin@uatf.edu.bo'],
            [
                'name' => 'Vicerrectorado',
                'password' => Hash::make('password'),
                'rol' => User::ROL_VICERRECTORADO,
                'carrera_id' => null,
            ],
        );

        $docentes = collect([
            ['nombre' => 'Ana Lucia Mamani Quispe', 'ci' => '9001001'],
            ['nombre' => 'Rodrigo Alberto Vargas Poma', 'ci' => '9001002'],
            ['nombre' => 'Beatriz Sonia Choque Fernandez', 'ci' => '9001003'],
            ['nombre' => 'Luis Fernando Condori Yujra', 'ci' => '9001004'],
        ])->map(fn (array $docente) => Docente::updateOrCreate(
            ['ci' => $docente['ci']],
            ['nombre' => $docente['nombre'], 'carrera_origen_id' => $carrera->id],
        ))->values();

        $materias = collect([
            ['sigla' => 'INF-101', 'nombre' => 'Programacion I', 'horas' => 6],
            ['sigla' => 'INF-201', 'nombre' => 'Base de Datos I', 'horas' => 6],
            ['sigla' => 'INF-301', 'nombre' => 'Ingenieria de Software I', 'horas' => 4],
        ])->map(function (array $materia) use ($carrera) {
            $registro = Materia::updateOrCreate(
                ['sigla' => $materia['sigla']],
                ['nombre' => $materia['nombre'], 'horas' => $materia['horas']],
            );

            $malla = MallaCurricular::firstOrCreate([
                'carrera_id' => $carrera->id,
                'materia_id' => $registro->id,
            ]);

            return [$registro, $malla];
        });

        $materias->each(function (array $par, int $indice) use ($docentes, $gestionOrigen, $periodoOrigen, $director): void {
            [$materia, $malla] = $par;
            $grupo = Grupo::firstOrCreate(
                ['malla_curricular_id' => $malla->id, 'codigo' => '1'],
                ['estado' => 'habilitado'],
            );
            $grupo->update(['estado' => 'habilitado']);

            Designacion::updateOrCreate(
                [
                    'Id_grupo' => $grupo->id,
                    'Id_gestion' => $gestionOrigen->id,
                    'Id_periodo' => $periodoOrigen->id,
                ],
                [
                    'Id_docente' => $docentes[$indice % $docentes->count()]->id,
                    'Id_materia' => $materia->id,
                    'malla_curricular_id' => $malla->id,
                    'estado' => 'aprobada',
                    'creado_por' => $director->id,
                    'aprobado_por' => null,
                ],
            );
        });

        $this->command?->info('Datos demo listos: director.inf@uatf.edu.bo / password');
    }
}
