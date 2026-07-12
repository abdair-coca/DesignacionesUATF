<?php

namespace Tests\Feature\Catalogos;

use App\Models\Grupo;
use App\Models\Materia;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GrupoCrudTest extends TestCase
{
    use DatabaseTransactions;

    public function test_usuario_autenticado_crea_un_grupo(): void
    {
        $materia = Materia::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post('/grupos', [
                'materia_id' => $materia->id,
                'codigo' => 'A',
                'estado' => 'habilitado',
            ])
            ->assertRedirect('/grupos');

        $this->assertDatabaseHas('grupos', ['materia_id' => $materia->id, 'codigo' => 'A']);
    }

    public function test_usuario_autenticado_actualiza_un_grupo(): void
    {
        $grupo = Grupo::factory()->create(['estado' => 'habilitado']);

        $this->actingAs(User::factory()->create())
            ->put("/grupos/{$grupo->id}", [
                'materia_id' => $grupo->materia_id,
                'codigo' => $grupo->codigo,
                'estado' => 'deshabilitado',
            ])
            ->assertRedirect('/grupos');

        $this->assertDatabaseHas('grupos', ['id' => $grupo->id, 'estado' => 'deshabilitado']);
    }

    public function test_usuario_autenticado_elimina_un_grupo_sin_designaciones(): void
    {
        $grupo = Grupo::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete("/grupos/{$grupo->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('grupos', ['id' => $grupo->id]);
    }
}
