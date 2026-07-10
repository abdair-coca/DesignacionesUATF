<?php

namespace Database\Seeders;

use App\Models\Grupo;
use App\Models\Materia;
use Illuminate\Database\Seeder;

class GrupoSeeder extends Seeder
{
    public function run(): void
    {
        $materias = Materia::pluck('id', 'sigla');

        foreach ($materias as $materiaId) {
            Grupo::create(['materia_id' => $materiaId, 'codigo' => 'A', 'estado' => 'habilitado']);
        }

        // Algunas materias con alta demanda tienen un segundo grupo.
        Grupo::create(['materia_id' => $materias['INF-101'], 'codigo' => 'B', 'estado' => 'habilitado']);
        Grupo::create(['materia_id' => $materias['MAT-110'], 'codigo' => 'B', 'estado' => 'habilitado']);

        // Un grupo deshabilitado manualmente, para probar ese estado en el listado.
        Grupo::create(['materia_id' => $materias['MED-330'], 'codigo' => 'B', 'estado' => 'deshabilitado']);
    }
}
