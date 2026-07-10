<?php

namespace Database\Seeders;

use App\Models\Periodo;
use Illuminate\Database\Seeder;

class PeriodoSeeder extends Seeder
{
    public function run(): void
    {
        Periodo::insert([
            ['nombre' => '1'],
            ['nombre' => '2'],
            ['nombre' => 'Verano'],
        ]);
    }
}
