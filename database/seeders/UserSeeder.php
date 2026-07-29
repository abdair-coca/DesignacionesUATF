<?php

namespace Database\Seeders;

use App\Models\Carrera;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear Administrador / Vicedecano
        User::firstOrCreate(
            ['email' => 'admin@uatf.edu.bo'],
            [
                'name' => 'Admin Vicedecano',
                'password' => Hash::make('password'),
                'is_admin' => true,
                'carrera_id' => null,
            ]
        );

        // 2. Crear Director de Carrera para cada Carrera existente
        $carreras = Carrera::all();

        foreach ($carreras as $carrera) {
            $email = 'director.'.strtolower($carrera->sigla).'@uatf.edu.bo';
            $nombre = 'Director '.$carrera->nombre;

            User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $nombre,
                    'password' => Hash::make('password'),
                    'is_admin' => false,
                    'carrera_id' => $carrera->id,
                ]
            );
        }
    }
}
