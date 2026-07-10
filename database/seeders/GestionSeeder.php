<?php

namespace Database\Seeders;

use App\Models\Gestion;
use Illuminate\Database\Seeder;

class GestionSeeder extends Seeder
{
    public function run(): void
    {
        Gestion::insert([
            ['nombre' => '2025'],
            ['nombre' => '2026'],
        ]);
    }
}
