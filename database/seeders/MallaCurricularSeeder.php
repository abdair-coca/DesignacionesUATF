<?php

namespace Database\Seeders;

use App\Models\Carrera;
use App\Models\MallaCurricular;
use App\Models\Materia;
use Illuminate\Database\Seeder;

class MallaCurricularSeeder extends Seeder
{
    public function run(): void
    {
        // Cada materia aparece, como mínimo, en la malla de su propia carrera.
        foreach (Materia::all() as $materia) {
            MallaCurricular::create([
                'carrera_id' => $materia->carrera_id,
                'materia_id' => $materia->id,
            ]);
        }

        // Cálculo I y II (Matemáticas) son materias de servicio: también las cursan
        // varias ingenierías, aunque las dicte la carrera de Matemáticas.
        $calculoI = Materia::where('sigla', 'MAT-050')->value('id');
        $calculoII = Materia::where('sigla', 'MAT-060')->value('id');

        $ingenierias = Carrera::whereIn('sigla', ['CIV', 'INF', 'IND', 'ELE', 'ELT', 'MEC', 'QUI', 'MIN'])->pluck('id');

        foreach ($ingenierias as $carreraId) {
            MallaCurricular::create(['carrera_id' => $carreraId, 'materia_id' => $calculoI]);
            MallaCurricular::create(['carrera_id' => $carreraId, 'materia_id' => $calculoII]);
        }
    }
}
