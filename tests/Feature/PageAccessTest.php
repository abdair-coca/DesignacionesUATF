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

    public function test_designaciones_index(): void
    {
        Carrera::factory()->count(2)->create();

        $this->actingAs(User::factory()->create())
            ->get('/designaciones')
            ->assertOk();
    }

    public function test_designaciones_lista(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/designaciones/lista')
            ->assertOk();
    }

    public function test_designaciones_carrera(): void
    {
        $carrera = Carrera::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create(['nombre' => '2']);

        $this->actingAs(User::factory()->create())
            ->get("/designaciones/carrera/{$carrera->id}?gestion_id={$gestion->id}&periodo_id={$periodo->id}")
            ->assertOk();
    }
}
