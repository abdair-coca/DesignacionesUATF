<?php

namespace Database\Seeders;

use App\Models\Gestion;
use Illuminate\Database\Seeder;

class GestionSeeder extends Seeder
{
    public function run(): void
    {
        Gestion::query()->update(['es_actual' => false]);

        foreach (['2024', '2025', '2026'] as $nombre) {
            Gestion::updateOrCreate(
                ['nombre' => $nombre],
                ['es_actual' => $nombre === '2026'],
            );
        }
    }
}
