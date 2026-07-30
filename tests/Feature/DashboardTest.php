<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\User;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    public function test_usuario_autenticado_ve_designaciones_lista(): void
    {
        $this->actingAs(User::factory()->create(['carrera_id' => Carrera::factory()]))
            ->get('/designaciones/lista')
            ->assertOk();
    }

    public function test_raiz_redirige_a_propuestas_para_usuario_autenticado(): void
    {
        $this->actingAs(User::factory()->create(['carrera_id' => Carrera::factory()]))
            ->get('/')
            ->assertRedirect('/propuestas');
    }
}
