<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\User;
use Tests\TestCase;

class PageAccessTest extends TestCase
{
    public function test_raiz_redirige_a_propuestas_para_director(): void
    {
        $director = User::factory()->create([
            'carrera_id' => Carrera::factory(),
        ]);
        $this->actingAs($director)
            ->get('/')
            ->assertRedirect('/propuestas');
    }

    public function test_raiz_redirige_a_versiones_pendientes_para_vicerrectorado(): void
    {
        $admin = User::factory()->vicerrectorado()->create();
        $this->actingAs($admin)
            ->get('/')
            ->assertRedirect(route('versiones.pendientes'));
    }

    public function test_designaciones_lista_acceso_director(): void
    {
        $director = User::factory()->create([
            'carrera_id' => Carrera::factory(),
        ]);
        $this->actingAs($director)
            ->get('/designaciones/lista')
            ->assertOk();
    }

    public function test_designaciones_carrera_rechaza_vicerrectorado(): void
    {
        $carrera = Carrera::first() ?? Carrera::factory()->create();
        $admin = User::factory()->vicerrectorado()->create();

        $this->actingAs($admin)
            ->get("/designaciones/carrera/{$carrera->id}")
            ->assertForbidden();
    }
}
