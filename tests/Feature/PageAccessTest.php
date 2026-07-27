<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\Designacion;
use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Periodo;
use App\Models\User;
use Tests\TestCase;

class PageAccessTest extends TestCase
{
    public function test_raiz_redirige_al_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertRedirect('/dashboard');
    }

    public function test_dashboard(): void
    {
        Carrera::factory()->count(2)->create();
        Gestion::factory()->create();
        Periodo::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_dashboard_con_filtros(): void
    {
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get('/dashboard?gestion_id=' . $gestion->id . '&periodo_id=' . $periodo->id)
            ->assertOk();
    }

    public function test_carreras_index(): void
    {
        Carrera::factory()->count(3)->create();

        $this->actingAs(User::factory()->create())
            ->get('/carreras')
            ->assertOk();
    }

    public function test_carreras_create(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/carreras/create')
            ->assertOk();
    }

    public function test_carreras_edit(): void
    {
        $carrera = Carrera::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get("/carreras/{$carrera->id}/edit")
            ->assertOk();
    }

    public function test_materias_index(): void
    {
        Materia::factory()->count(3)->create();

        $this->actingAs(User::factory()->create())
            ->get('/materias')
            ->assertOk();
    }

    public function test_materias_index_filtro_carrera(): void
    {
        $carrera = Carrera::factory()->create();
        Materia::factory()->count(2)->create(['carrera_id' => $carrera->id]);

        $this->actingAs(User::factory()->create())
            ->get('/materias?carrera_id=' . $carrera->id)
            ->assertOk();
    }

    public function test_materias_create(): void
    {
        Carrera::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get('/materias/create')
            ->assertOk();
    }

    public function test_materias_edit(): void
    {
        $materia = Materia::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get("/materias/{$materia->id}/edit")
            ->assertOk();
    }

    public function test_grupos_index(): void
    {
        Grupo::factory()->count(3)->create();

        $this->actingAs(User::factory()->create())
            ->get('/grupos')
            ->assertOk();
    }

    public function test_grupos_index_filtros(): void
    {
        $materia = Materia::factory()->create();
        Grupo::factory()->count(2)->create(['materia_id' => $materia->id]);

        $this->actingAs(User::factory()->create())
            ->get('/grupos?materia_id=' . $materia->id)
            ->assertOk();
    }

    public function test_grupos_create(): void
    {
        Materia::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get('/grupos/create')
            ->assertOk();
    }

    public function test_grupos_edit(): void
    {
        $grupo = Grupo::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get("/grupos/{$grupo->id}/edit")
            ->assertOk();
    }

    public function test_docentes_index(): void
    {
        Docente::factory()->count(3)->create();

        $this->actingAs(User::factory()->create())
            ->get('/docentes')
            ->assertOk();
    }

    public function test_docentes_index_search(): void
    {
        Docente::factory()->create(['nombre' => 'Juan Perez']);

        $this->actingAs(User::factory()->create())
            ->get('/docentes?q=Juan')
            ->assertOk();
    }

    public function test_docentes_create(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/docentes/create')
            ->assertOk();
    }

    public function test_docentes_edit(): void
    {
        $docente = Docente::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get("/docentes/{$docente->id}/edit")
            ->assertOk();
    }

    public function test_gestiones_index(): void
    {
        Gestion::factory()->count(3)->create();

        $this->actingAs(User::factory()->create())
            ->get('/gestiones')
            ->assertOk();
    }

    public function test_gestiones_create(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/gestiones/create')
            ->assertOk();
    }

    public function test_gestiones_edit(): void
    {
        $gestion = Gestion::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get("/gestiones/{$gestion->id}/edit")
            ->assertOk();
    }

    public function test_periodos_index(): void
    {
        Periodo::factory()->count(3)->create();

        $this->actingAs(User::factory()->create())
            ->get('/periodos')
            ->assertOk();
    }

    public function test_periodos_create(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/periodos/create')
            ->assertOk();
    }

    public function test_periodos_edit(): void
    {
        $periodo = Periodo::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get("/periodos/{$periodo->id}/edit")
            ->assertOk();
    }

    public function test_designaciones_index(): void
    {
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get('/designaciones')
            ->assertOk();
    }

    public function test_designaciones_index_con_filtros(): void
    {
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get('/designaciones?gestion_id=' . $gestion->id . '&periodo_id=' . $periodo->id)
            ->assertOk();
    }

    public function test_designaciones_lista(): void
    {
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get('/designaciones/lista')
            ->assertOk();
    }

    public function test_designaciones_lista_con_filtros(): void
    {
        $carrera = Carrera::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get('/designaciones/lista?carrera_id=' . $carrera->id . '&gestion_id=' . $gestion->id . '&periodo_id=' . $periodo->id)
            ->assertOk();
    }

    public function test_designaciones_carrera(): void
    {
        $carrera = Carrera::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();
        $materia = Materia::factory()->create(['carrera_id' => $carrera->id]);
        Grupo::factory()->create(['materia_id' => $materia->id]);

        $this->actingAs(User::factory()->create())
            ->get("/designaciones/carrera/{$carrera->id}?gestion_id={$gestion->id}&periodo_id={$periodo->id}")
            ->assertOk();
    }

    public function test_designaciones_create(): void
    {
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get('/designaciones/create')
            ->assertOk();
    }

    public function test_designaciones_edit(): void
    {
        $designacion = Designacion::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get("/designaciones/{$designacion->id}/edit")
            ->assertOk();
    }

    public function test_designaciones_historial(): void
    {
        $designacion = Designacion::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get("/designaciones/{$designacion->id}/historial")
            ->assertOk();
    }
}
