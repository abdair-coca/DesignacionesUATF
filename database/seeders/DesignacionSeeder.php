<?php

namespace Database\Seeders;

use App\Models\Designacion;
use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Periodo;
use App\Models\User;
use Illuminate\Database\Seeder;

class DesignacionSeeder extends Seeder
{
    /**
     * Qué tan cubierta está cada combinación gestión/periodo y qué tan probable es cada
     * estado, para que el dashboard y "Designaciones por carrera" tengan de todo: grupos
     * sin cubrir, pendientes, aprobados y rechazados.
     */
    private const ESCENARIOS = [
        ['gestion' => '2025', 'periodo' => '1', 'cobertura' => 0.9, 'estados' => ['aprobada' => 85, 'rechazada' => 15]],
        ['gestion' => '2025', 'periodo' => '2', 'cobertura' => 0.85, 'estados' => ['aprobada' => 80, 'rechazada' => 20]],
        ['gestion' => '2026', 'periodo' => '1', 'cobertura' => 0.75, 'estados' => ['aprobada' => 55, 'propuesta' => 30, 'rechazada' => 15]],
        ['gestion' => '2026', 'periodo' => '2', 'cobertura' => 0.35, 'estados' => ['propuesta' => 70, 'aprobada' => 20, 'rechazada' => 10]],
    ];

    public function run(): void
    {
        $grupos = Grupo::with('materia')->where('estado', 'habilitado')->get();
        $docentesPorCarrera = Docente::all()->groupBy('carrera_origen_id');
        $usuarioIds = User::pluck('id')->all();

        foreach (self::ESCENARIOS as $escenario) {
            $gestionId = Gestion::where('nombre', $escenario['gestion'])->value('id');
            $periodoId = Periodo::where('nombre', $escenario['periodo'])->value('id');

            foreach ($grupos as $grupo) {
                if (! fake()->boolean((int) round($escenario['cobertura'] * 100))) {
                    continue;
                }

                $candidatos = $docentesPorCarrera->get($grupo->materia->carrera_id);
                if (! $candidatos || $candidatos->isEmpty()) {
                    continue;
                }

                $anio = (int) $escenario['gestion'];
                $esPeriodo1 = ($escenario['periodo'] === '1');
                $mes = $esPeriodo1 ? fake()->numberBetween(1, 6) : fake()->numberBetween(7, 12);
                $dia = fake()->numberBetween(1, 28);
                $hora = fake()->numberBetween(8, 20);
                $minuto = fake()->numberBetween(0, 59);
                $segundo = fake()->numberBetween(0, 59);
                $createdAt = sprintf('%04d-%02d-%02d %02d:%02d:%02d', $anio, $mes, $dia, $hora, $minuto, $segundo);

                Designacion::create([
                    'Id_docente' => $candidatos->random()->id,
                    'Id_materia' => $grupo->materia_id,
                    'Id_grupo' => $grupo->id,
                    'Id_gestion' => $gestionId,
                    'Id_periodo' => $periodoId,
                    'estado' => fake()->randomElement(self::expandirEstados($escenario['estados'])),
                    'creado_por' => fake()->randomElement($usuarioIds),
                    'aprobado_por' => null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        }
    }

    /**
     * Convierte ['aprobada' => 55, 'propuesta' => 30] en una lista con cada estado repetido
     * según su peso, para poder elegir uno al azar con distribución proporcional.
     */
    private static function expandirEstados(array $pesos): array
    {
        $lista = [];
        foreach ($pesos as $estado => $peso) {
            $lista = [...$lista, ...array_fill(0, $peso, $estado)];
        }

        return $lista;
    }
}
