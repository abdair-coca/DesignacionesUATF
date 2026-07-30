<?php

namespace Tests\Feature;

use App\Models\Designacion;
use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Periodo;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class DesignacionMallaIntegrityTest extends TestCase
{
    public function test_designacion_usa_la_malla_del_grupo(): void
    {
        $grupo = Grupo::factory()->create();

        $designacion = Designacion::factory()->create([
            'Id_grupo' => $grupo->id,
            'Id_materia' => $grupo->mallaCurricular->materia_id,
        ]);

        $this->assertSame($grupo->malla_curricular_id, $designacion->malla_curricular_id);
    }

    public function test_rechaza_materia_incompatible_con_grupo_y_malla(): void
    {
        $grupo = Grupo::factory()->create();

        $this->expectException(QueryException::class);

        Designacion::factory()->create([
            'Id_grupo' => $grupo->id,
            'Id_materia' => Materia::factory(),
        ]);
    }

    public function test_rechaza_segunda_designacion_activa_para_el_mismo_grupo(): void
    {
        $grupo = Grupo::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        Designacion::factory()->create([
            'Id_grupo' => $grupo->id,
            'Id_materia' => $grupo->mallaCurricular->materia_id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
        ]);

        $this->expectException(QueryException::class);

        Designacion::factory()->create([
            'Id_docente' => Docente::factory(),
            'Id_grupo' => $grupo->id,
            'Id_materia' => $grupo->mallaCurricular->materia_id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
        ]);
    }
}
