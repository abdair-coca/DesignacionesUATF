<?php

namespace Tests\Feature\Catalogos;

use App\Models\Gestion;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GestionCrudTest extends TestCase
{
    use DatabaseTransactions;

    public function test_usuario_autenticado_crea_una_gestion(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/gestiones', ['nombre' => '2099'])
            ->assertRedirect('/gestiones');

        $this->assertDatabaseHas('gestiones', ['nombre' => '2099']);
    }

    public function test_usuario_autenticado_actualiza_una_gestion(): void
    {
        $gestion = Gestion::factory()->create();

        $this->actingAs(User::factory()->create())
            ->put("/gestiones/{$gestion->id}", ['nombre' => '2098'])
            ->assertRedirect('/gestiones');

        $this->assertDatabaseHas('gestiones', ['id' => $gestion->id, 'nombre' => '2098']);
    }

    public function test_usuario_autenticado_elimina_una_gestion_sin_designaciones(): void
    {
        $gestion = Gestion::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete("/gestiones/{$gestion->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('gestiones', ['id' => $gestion->id]);
    }
}
