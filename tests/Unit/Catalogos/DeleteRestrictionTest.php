<?php

namespace Tests\Unit\Catalogos;

use App\Models\Carrera;
use App\Models\Designacion;
use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Periodo;
use App\Models\User;
use Tests\TestCase;

class DeleteRestrictionTest extends TestCase
{
    public function test_no_eliminar_carrera_con_materias(): void
    {
        $materia = Materia::factory()->create();
        $carrera = $materia->carrera;

        $this->actingAs(User::factory()->create())
            ->delete("/carreras/{$carrera->id}")
            ->assertRedirect();

        $this->assertDatabaseHas('carreras', ['id' => $carrera->id]);
    }

    public function test_no_eliminar_materia_con_grupos(): void
    {
        $grupo = Grupo::factory()->create();
        $materia = $grupo->materia;

        $this->actingAs(User::factory()->create())
            ->delete("/materias/{$materia->id}")
            ->assertRedirect();

        $this->assertDatabaseHas('materias', ['id' => $materia->id]);
    }

    public function test_no_eliminar_grupo_con_designaciones(): void
    {
        $designacion = Designacion::factory()->create();
        $grupo = $designacion->grupo;

        $this->actingAs(User::factory()->create())
            ->delete("/grupos/{$grupo->id}")
            ->assertRedirect();

        $this->assertDatabaseHas('grupos', ['id' => $grupo->id]);
    }

    public function test_no_eliminar_docente_con_designaciones(): void
    {
        $designacion = Designacion::factory()->create();
        $docente = $designacion->docente;

        $this->actingAs(User::factory()->create())
            ->delete("/docentes/{$docente->id}")
            ->assertRedirect();

        $this->assertDatabaseHas('docentes', ['id' => $docente->id]);
    }

    public function test_no_eliminar_gestion_con_designaciones(): void
    {
        $designacion = Designacion::factory()->create();
        $gestion = $designacion->gestion;

        $this->actingAs(User::factory()->create())
            ->delete("/gestiones/{$gestion->id}")
            ->assertRedirect();

        $this->assertDatabaseHas('gestiones', ['id' => $gestion->id]);
    }

    public function test_no_eliminar_periodo_con_designaciones(): void
    {
        $designacion = Designacion::factory()->create();
        $periodo = $designacion->periodo;

        $this->actingAs(User::factory()->create())
            ->delete("/periodos/{$periodo->id}")
            ->assertRedirect();

        $this->assertDatabaseHas('periodos', ['id' => $periodo->id]);
    }

    public function test_carrera_sin_materias_se_elimina(): void
    {
        $carrera = Carrera::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete("/carreras/{$carrera->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('carreras', ['id' => $carrera->id]);
    }

    public function test_materia_sin_grupos_se_elimina(): void
    {
        $materia = Materia::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete("/materias/{$materia->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('materias', ['id' => $materia->id]);
    }

    public function test_grupo_sin_designaciones_se_elimina(): void
    {
        $grupo = Grupo::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete("/grupos/{$grupo->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('grupos', ['id' => $grupo->id]);
    }

    public function test_docente_sin_designaciones_se_elimina(): void
    {
        $docente = Docente::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete("/docentes/{$docente->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('docentes', ['id' => $docente->id]);
    }

    public function test_gestion_sin_designaciones_se_elimina(): void
    {
        $gestion = Gestion::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete("/gestiones/{$gestion->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('gestiones', ['id' => $gestion->id]);
    }

    public function test_periodo_sin_designaciones_se_elimina(): void
    {
        $periodo = Periodo::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete("/periodos/{$periodo->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('periodos', ['id' => $periodo->id]);
    }
}
