<?php

namespace Database\Seeders;

use App\Models\Carrera;
use Illuminate\Database\Seeder;

class CarreraSeeder extends Seeder
{
    public function run(): void
    {
        Carrera::insert([
            ['sigla' => 'CIV', 'nombre' => 'Ingeniería Civil'],
            ['sigla' => 'INF', 'nombre' => 'Ingeniería Informática'],
            ['sigla' => 'IND', 'nombre' => 'Ingeniería Industrial'],
            ['sigla' => 'ELE', 'nombre' => 'Ingeniería Eléctrica'],
            ['sigla' => 'ELT', 'nombre' => 'Ingeniería Electrónica y Telecomunicaciones'],
            ['sigla' => 'MEC', 'nombre' => 'Ingeniería Mecánica'],
            ['sigla' => 'QUI', 'nombre' => 'Ingeniería Química'],
            ['sigla' => 'MIN', 'nombre' => 'Ingeniería de Minas'],
            ['sigla' => 'ARQ', 'nombre' => 'Arquitectura'],
            ['sigla' => 'MED', 'nombre' => 'Medicina'],
            ['sigla' => 'ODO', 'nombre' => 'Odontología'],
            ['sigla' => 'ENF', 'nombre' => 'Enfermería'],
            ['sigla' => 'BQF', 'nombre' => 'Bioquímica y Farmacia'],
            ['sigla' => 'DER', 'nombre' => 'Derecho'],
            ['sigla' => 'EDU', 'nombre' => 'Ciencias de la Educación'],
            ['sigla' => 'CPU', 'nombre' => 'Contaduría Pública'],
            ['sigla' => 'ADM', 'nombre' => 'Administración de Empresas'],
            ['sigla' => 'ECO', 'nombre' => 'Economía'],
            ['sigla' => 'COM', 'nombre' => 'Ciencias de la Comunicación Social'],
            ['sigla' => 'TSO', 'nombre' => 'Trabajo Social'],
            ['sigla' => 'PSI', 'nombre' => 'Psicología'],
            ['sigla' => 'AGR', 'nombre' => 'Ingeniería Agronómica'],
            ['sigla' => 'MVZ', 'nombre' => 'Medicina Veterinaria y Zootecnia'],
            ['sigla' => 'MAT', 'nombre' => 'Licenciatura en Matemáticas'],
            ['sigla' => 'TUR', 'nombre' => 'Turismo'],
        ]);
    }
}
