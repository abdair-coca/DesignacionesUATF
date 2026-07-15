<?php

namespace Database\Seeders;

use App\Models\Carrera;
use App\Models\Docente;
use Illuminate\Database\Seeder;

class DocenteSeeder extends Seeder
{
    private const NOMBRES = [
        'Juan Carlos', 'María Elena', 'Rodrigo Alberto', 'Beatriz Sonia', 'Freddy Wilson',
        'Ana Lucía', 'Edwin Gonzalo', 'Rosa Ximena', 'Luis Fernando', 'Carla Patricia',
        'José Miguel', 'Silvia Rosario', 'Mario Enrique', 'Verónica Paola', 'Álvaro Sebastián',
        'Gabriela Noemí', 'Ronald Iván', 'Patricia Elizabeth', 'Marco Antonio', 'Claudia Beatriz',
        'Oscar Iván', 'Daniela Fernanda', 'Hugo René', 'Jhenny Karina', 'Grover Adalid',
        'Nelly Roxana', 'Wilson Fabián', 'Elizabeth Carmen', 'Franz Ariel', 'Lourdes Ivonne',
    ];

    private const APELLIDOS = [
        'Mamani Quispe', 'Choque Fernández', 'Vargas Poma', 'Apaza Colque', 'Choquehuanca Rojas',
        'Torrez Villca', 'Callisaya Huanca', 'Flores Mamani', 'Condori Yujra', 'Gutierrez Cruz',
        'Chura Laura', 'Poma Ticona', 'Quispe Nina', 'Huanca Aruquipa', 'Colque Ramos',
        'Villca Serrano', 'Rojas Balboa', 'Cruz Escobar', 'Nina Choque', 'Aruquipa Mendoza',
    ];

    public function run(): void
    {
        foreach (Carrera::all() as $carrera) {
            $cantidad = fake()->numberBetween(2, 6);

            for ($i = 0; $i < $cantidad; $i++) {
                Docente::create([
                    'nombre' => fake()->randomElement(self::NOMBRES).' '.fake()->randomElement(self::APELLIDOS),
                    'ci' => fake()->unique()->numerify('#######'),
                    'carrera_origen_id' => $carrera->id,
                ]);
            }
        }
    }
}
