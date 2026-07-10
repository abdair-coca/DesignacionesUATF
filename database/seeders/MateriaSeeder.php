<?php

namespace Database\Seeders;

use App\Models\Carrera;
use App\Models\Materia;
use Illuminate\Database\Seeder;

class MateriaSeeder extends Seeder
{
    public function run(): void
    {
        $carreras = Carrera::pluck('id', 'sigla');

        $materias = [
            ['sigla' => 'INF-101', 'nombre' => 'Introducción a la Programación', 'carrera' => 'INF'],
            ['sigla' => 'INF-210', 'nombre' => 'Estructuras de Datos', 'carrera' => 'INF'],
            ['sigla' => 'INF-520', 'nombre' => 'Ingeniería de Software II', 'carrera' => 'INF'],
            ['sigla' => 'MAT-110', 'nombre' => 'Cálculo I', 'carrera' => 'MAT'],
            ['sigla' => 'MAT-220', 'nombre' => 'Álgebra Lineal', 'carrera' => 'MAT'],
            ['sigla' => 'MED-101', 'nombre' => 'Anatomía I', 'carrera' => 'MED'],
            ['sigla' => 'MED-330', 'nombre' => 'Fisiología Humana', 'carrera' => 'MED'],
            ['sigla' => 'CIV-150', 'nombre' => 'Mecánica de Materiales', 'carrera' => 'CIV'],
            ['sigla' => 'CIV-340', 'nombre' => 'Hidráulica', 'carrera' => 'CIV'],
        ];

        foreach ($materias as $materia) {
            Materia::create([
                'sigla' => $materia['sigla'],
                'nombre' => $materia['nombre'],
                'carrera_id' => $carreras[$materia['carrera']],
            ]);
        }
    }
}
