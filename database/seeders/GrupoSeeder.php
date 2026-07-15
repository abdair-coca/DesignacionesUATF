<?php

namespace Database\Seeders;

use App\Models\Grupo;
use App\Models\Materia;
use Illuminate\Database\Seeder;

class GrupoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Materia::all() as $materia) {
            Grupo::create(['materia_id' => $materia->id, 'codigo' => 'A', 'estado' => 'habilitado']);

            // Las materias de alta demanda tienen un segundo grupo.
            if (fake()->boolean(35)) {
                Grupo::create(['materia_id' => $materia->id, 'codigo' => 'B', 'estado' => 'habilitado']);
            }
        }

        // Un puñado de grupos deshabilitados manualmente, para probar ese estado en el listado.
        Grupo::inRandomOrder()->limit(8)->get()->each(
            fn (Grupo $grupo) => $grupo->update(['estado' => 'deshabilitado'])
        );
    }
}
