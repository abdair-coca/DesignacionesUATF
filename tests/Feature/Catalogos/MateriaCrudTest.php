<?php

namespace Tests\Feature\Catalogos;

use App\Models\Carrera;
use App\Models\Materia;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MateriaCrudTest extends TestCase
{
    use DatabaseTransactions;

    public function test_usuario_autenticado_crea_una_materia(): void
    {
        $carrera = Carrera::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post('/materias', [
                'sigla' => 'MAT101',
                'nombre' => 'Cálculo I',
                'carrera_id' => $carrera->id,
                'horas' => 6,
            ])
            ->assertRedirect('/materias');

        $this->assertDatabaseHas('materias', ['sigla' => 'MAT101', 'carrera_id' => $carrera->id, 'horas' => 6]);
    }

    public function test_usuario_autenticado_actualiza_una_materia(): void
    {
        $materia = Materia::factory()->create();

        $this->actingAs(User::factory()->create())
            ->put("/materias/{$materia->id}", [
                'sigla' => $materia->sigla,
                'nombre' => 'Nombre actualizado',
                'carrera_id' => $materia->carrera_id,
                'horas' => 8,
            ])
            ->assertRedirect('/materias');

        $this->assertDatabaseHas('materias', ['id' => $materia->id, 'nombre' => 'Nombre actualizado', 'horas' => 8]);
    }

    public function test_usuario_autenticado_elimina_una_materia_sin_grupos(): void
    {
        $materia = Materia::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete("/materias/{$materia->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('materias', ['id' => $materia->id]);
    }
}
