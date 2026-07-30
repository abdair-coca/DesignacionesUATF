<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\MallaCurricular;
use App\Models\Materia;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AcademicSchemaTest extends TestCase
{
    public function test_esquema_agrega_malla_a_grupos_y_marca_a_gestiones(): void
    {
        $this->assertTrue(Schema::hasColumn('grupos', 'malla_curricular_id'));
        $this->assertTrue(Schema::hasColumn('gestiones', 'es_actual'));
    }

    public function test_grupo_puede_referenciar_una_malla_curricular(): void
    {
        $carrera = Carrera::factory()->create();
        $materia = Materia::factory()->create(['carrera_id' => $carrera->id]);
        $malla = MallaCurricular::create([
            'carrera_id' => $carrera->id,
            'materia_id' => $materia->id,
        ]);

        $grupo = Grupo::factory()->create([
            'materia_id' => $materia->id,
            'malla_curricular_id' => $malla->id,
        ]);

        $this->assertTrue($grupo->mallaCurricular->is($malla));
    }

    public function test_permite_codigo_repetido_para_mallas_distintas_de_una_materia_compartida(): void
    {
        $materia = Materia::factory()->create();
        $mallaA = MallaCurricular::create([
            'carrera_id' => $materia->carrera_id,
            'materia_id' => $materia->id,
        ]);
        $mallaB = MallaCurricular::create([
            'carrera_id' => Carrera::factory()->create()->id,
            'materia_id' => $materia->id,
        ]);

        Grupo::factory()->create([
            'materia_id' => $materia->id,
            'malla_curricular_id' => $mallaA->id,
            'codigo' => '1',
        ]);
        Grupo::factory()->create([
            'materia_id' => $materia->id,
            'malla_curricular_id' => $mallaB->id,
            'codigo' => '1',
        ]);

        $this->assertDatabaseCount('grupos', 2);
    }

    public function test_no_permite_mas_de_una_gestion_actual(): void
    {
        Gestion::factory()->create(['es_actual' => true]);

        $this->expectException(QueryException::class);

        Gestion::factory()->create(['es_actual' => true]);
    }
}
