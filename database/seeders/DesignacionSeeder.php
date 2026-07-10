<?php

namespace Database\Seeders;

use App\Models\Designacion;
use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Periodo;
use Illuminate\Database\Seeder;

class DesignacionSeeder extends Seeder
{
    public function run(): void
    {
        $docentes = Docente::pluck('id', 'ci');
        $materias = Materia::pluck('id', 'sigla');
        $gestion2026 = Gestion::where('nombre', '2026')->value('id');
        $periodoI = Periodo::where('nombre', '1')->value('id');
        $periodoII = Periodo::where('nombre', '2')->value('id');

        $grupoA = fn (string $sigla) => Grupo::where('materia_id', $materias[$sigla])->where('codigo', 'A')->value('id');

        $designaciones = [
            [
                'Id_docente' => $docentes['4521367'],
                'Id_materia' => $materias['INF-101'],
                'Id_grupo' => $grupoA('INF-101'),
                'Id_gestion' => $gestion2026,
                'Id_periodo' => $periodoI,
                'estado' => 'aprobada',
            ],
            [
                'Id_docente' => $docentes['3987456'],
                'Id_materia' => $materias['INF-520'],
                'Id_grupo' => $grupoA('INF-520'),
                'Id_gestion' => $gestion2026,
                'Id_periodo' => $periodoI,
                'estado' => 'aprobada',
            ],
            [
                'Id_docente' => $docentes['5102834'],
                'Id_materia' => $materias['MAT-110'],
                'Id_grupo' => $grupoA('MAT-110'),
                'Id_gestion' => $gestion2026,
                'Id_periodo' => $periodoI,
                'estado' => 'propuesta',
            ],
            [
                'Id_docente' => $docentes['6234871'],
                'Id_materia' => $materias['MED-101'],
                'Id_grupo' => $grupoA('MED-101'),
                'Id_gestion' => $gestion2026,
                'Id_periodo' => $periodoII,
                'estado' => 'propuesta',
            ],
            [
                'Id_docente' => $docentes['5678123'],
                'Id_materia' => $materias['CIV-150'],
                'Id_grupo' => $grupoA('CIV-150'),
                'Id_gestion' => $gestion2026,
                'Id_periodo' => $periodoII,
                'estado' => 'rechazada',
            ],
        ];

        foreach ($designaciones as $designacion) {
            Designacion::create($designacion);
        }
    }
}
