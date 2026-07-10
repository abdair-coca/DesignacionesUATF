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
        $carreras = Carrera::pluck('id', 'sigla');
        $materias = Materia::pluck('id', 'sigla');

        // Cada materia aparece en la malla de su propia carrera.
        foreach ($materias as $sigla => $materiaId) {
            $carreraSigla = explode('-', $sigla)[0];
            MallaCurricular::create([
                'carrera_id' => $carreras[$carreraSigla],
                'materia_id' => $materiaId,
            ]);
        }

        // MAT-110 (Cálculo I) es materia de servicio: también aparece en la malla
        // de INF y CIV, además de la suya propia.
        MallaCurricular::create([
            'carrera_id' => $carreras['INF'],
            'materia_id' => $materias['MAT-110'],
        ]);
        MallaCurricular::create([
            'carrera_id' => $carreras['CIV'],
            'materia_id' => $materias['MAT-110'],
        ]);
    }
}
