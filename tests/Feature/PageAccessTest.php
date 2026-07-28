<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\Gestion;
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
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_dashboard_con_filtros(): void
    {
        $gestion = Gestion::first() ?? Gestion::factory()->create();
        $periodo = Periodo::first() ?? Periodo::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get('/dashboard?gestion_id=' . $gestion->id . '&periodo_id=' . $periodo->id)
            ->assertOk();
    }

    public function test_designaciones_index(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
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
        $carrera = Carrera::first() ?? Carrera::factory()->create();
        $gestion = Gestion::first() ?? Gestion::factory()->create();
        $periodo = Periodo::where('nombre', '2')->first() ?? Periodo::factory()->create(['nombre' => '2']);

        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get("/designaciones/carrera/{$carrera->id}?gestion_id={$gestion->id}&periodo_id={$periodo->id}")
            ->assertOk();
    }
}
