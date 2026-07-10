<?php

namespace Database\Seeders;

use App\Models\Carrera;
use App\Models\Docente;
use Illuminate\Database\Seeder;

class DocenteSeeder extends Seeder
{
    public function run(): void
    {
        $carreras = Carrera::pluck('id', 'sigla');

        $docentes = [
            ['nombre' => 'Juan Carlos Mamani Quispe', 'ci' => '4521367', 'carrera' => 'INF'],
            ['nombre' => 'María Elena Choque Fernández', 'ci' => '3987456', 'carrera' => 'INF'],
            ['nombre' => 'Rodrigo Alberto Vargas Poma', 'ci' => '5102834', 'carrera' => 'MAT'],
            ['nombre' => 'Beatriz Sonia Apaza Colque', 'ci' => '4765290', 'carrera' => 'MAT'],
            ['nombre' => 'Freddy Wilson Choquehuanca Rojas', 'ci' => '6234871', 'carrera' => 'MED'],
            ['nombre' => 'Ana Lucía Torrez Villca', 'ci' => '3456789', 'carrera' => 'MED'],
            ['nombre' => 'Edwin Gonzalo Callisaya Huanca', 'ci' => '5678123', 'carrera' => 'CIV'],
            ['nombre' => 'Rosa Ximena Flores Mamani', 'ci' => '4890321', 'carrera' => 'CIV'],
        ];

        foreach ($docentes as $docente) {
            Docente::create([
                'nombre' => $docente['nombre'],
                'ci' => $docente['ci'],
                'carrera_origen_id' => $carreras[$docente['carrera']],
            ]);
        }
    }
}
