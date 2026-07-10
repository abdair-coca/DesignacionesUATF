<?php

namespace Database\Seeders;

use App\Models\Periodo;
use Illuminate\Database\Seeder;

class PeriodoSeeder extends Seeder
{
    public function run(): void
    {
        Periodo::insert([
            ['nombre' => 'I'],
            ['nombre' => 'II'],
            ['nombre' => 'Verano'],
        ]);
    }
}
