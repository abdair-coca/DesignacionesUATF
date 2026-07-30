<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\User;
use Tests\TestCase;

class PageAccessTest extends TestCase
{
    public function test_raiz_redirige_a_designaciones_lista_para_director(): void
    {
        $director = User::factory()->create([
            'is_admin' => false,
            'carrera_id' => Carrera::factory(),
        ]);
        $this->actingAs($director)
            ->get('/')
            ->assertRedirect('/designaciones/lista');
    }

    public function test_raiz_redirige_a_revisiones_pendientes_para_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)
            ->get('/')
            ->assertRedirect(route('revisiones.pendientes'));
    }

    public function test_designaciones_lista_acceso_director(): void
    {
        $director = User::factory()->create([
            'is_admin' => false,
            'carrera_id' => Carrera::factory(),
        ]);
        $this->actingAs($director)
            ->get('/designaciones/lista')
            ->assertOk();
    }

    public function test_designaciones_carrera_redirecciona_admin(): void
    {
        $carrera = Carrera::first() ?? Carrera::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get("/designaciones/carrera/{$carrera->id}")
            ->assertRedirect(route('revisiones.pendientes'));
    }
}
