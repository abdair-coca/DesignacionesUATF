<?php

namespace Database\Seeders;

use App\Models\Carrera;
use Illuminate\Database\Seeder;

class CarreraSeeder extends Seeder
{
    public function run(): void
    {
        Carrera::insert([
            ['sigla' => 'INF', 'nombre' => 'Ingeniería Informática'],
            ['sigla' => 'MAT', 'nombre' => 'Licenciatura en Matemáticas'],
            ['sigla' => 'MED', 'nombre' => 'Medicina'],
            ['sigla' => 'CIV', 'nombre' => 'Ingeniería Civil'],
        ]);
    }
}
