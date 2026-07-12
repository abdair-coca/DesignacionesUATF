<?php

namespace Tests\Feature\Catalogos;

use App\Models\Docente;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DocenteCrudTest extends TestCase
{
    use DatabaseTransactions;

    public function test_usuario_autenticado_crea_un_docente(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/docentes', ['nombre' => 'Juana Pérez', 'ci' => '1234567'])
            ->assertRedirect('/docentes');

        $this->assertDatabaseHas('docentes', ['nombre' => 'Juana Pérez', 'ci' => '1234567']);
    }

    public function test_usuario_autenticado_actualiza_un_docente(): void
    {
        $docente = Docente::factory()->create();

        $this->actingAs(User::factory()->create())
            ->put("/docentes/{$docente->id}", ['nombre' => 'Nombre actualizado', 'ci' => $docente->ci])
            ->assertRedirect('/docentes');

        $this->assertDatabaseHas('docentes', ['id' => $docente->id, 'nombre' => 'Nombre actualizado']);
    }

    public function test_usuario_autenticado_elimina_un_docente_sin_designaciones(): void
    {
        $docente = Docente::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete("/docentes/{$docente->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('docentes', ['id' => $docente->id]);
    }
}
