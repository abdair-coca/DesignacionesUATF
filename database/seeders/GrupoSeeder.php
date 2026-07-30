<?php

namespace Database\Seeders;

use App\Models\Grupo;
use App\Models\MallaCurricular;
use Illuminate\Database\Seeder;

class GrupoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (MallaCurricular::all() as $malla) {
            Grupo::create([
                'materia_id' => $malla->materia_id,
                'malla_curricular_id' => $malla->id,
                'codigo' => '1',
                'estado' => 'habilitado',
            ]);

            // Las materias de alta demanda tienen un segundo grupo.
            if (fake()->boolean(35)) {
                Grupo::create([
                    'materia_id' => $malla->materia_id,
                    'malla_curricular_id' => $malla->id,
                    'codigo' => '2',
                    'estado' => 'habilitado',
                ]);
            }
        }

        // Un puñado de grupos deshabilitados manualmente, para probar ese estado en el listado.
        Grupo::inRandomOrder()->limit(8)->get()->each(
            fn (Grupo $grupo) => $grupo->update(['estado' => 'deshabilitado'])
        );
    }
}
