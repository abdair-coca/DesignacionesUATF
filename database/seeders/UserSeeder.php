<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = [
            ['name' => 'Abdair', 'email' => 'abdair@uatf.edu.bo'],
            ['name' => 'Supervisor', 'email' => 'supervisor@uatf.edu.bo'],
        ];

        foreach ($usuarios as $usuario) {
            User::firstOrCreate(
                ['email' => $usuario['email']],
                ['name' => $usuario['name'], 'password' => Hash::make('password')]
            );
        }
    }
}
