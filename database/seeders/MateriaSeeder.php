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
            ['sigla' => 'INF-101', 'nombre' => 'Introducción a la Programación', 'carrera' => 'INF', 'horas' => 6],
            ['sigla' => 'INF-210', 'nombre' => 'Estructuras de Datos', 'carrera' => 'INF', 'horas' => 6],
            ['sigla' => 'INF-520', 'nombre' => 'Ingeniería de Software II', 'carrera' => 'INF', 'horas' => 8],
            ['sigla' => 'MAT-110', 'nombre' => 'Cálculo I', 'carrera' => 'MAT', 'horas' => 6],
            ['sigla' => 'MAT-220', 'nombre' => 'Álgebra Lineal', 'carrera' => 'MAT', 'horas' => 4],
            ['sigla' => 'MED-101', 'nombre' => 'Anatomía I', 'carrera' => 'MED', 'horas' => 8],
            ['sigla' => 'MED-330', 'nombre' => 'Fisiología Humana', 'carrera' => 'MED', 'horas' => 8],
            ['sigla' => 'CIV-150', 'nombre' => 'Mecánica de Materiales', 'carrera' => 'CIV', 'horas' => 6],
            ['sigla' => 'CIV-340', 'nombre' => 'Hidráulica', 'carrera' => 'CIV', 'horas' => 4],
        ];

        foreach ($materias as $materia) {
            Materia::create([
                'sigla' => $materia['sigla'],
                'nombre' => $materia['nombre'],
                'carrera_id' => $carreras[$materia['carrera']],
                'horas' => $materia['horas'],
            ]);
        }
    }
}
