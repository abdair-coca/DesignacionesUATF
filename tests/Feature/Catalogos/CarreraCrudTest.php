<?php

namespace Tests\Feature\Catalogos;

use App\Models\Carrera;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CarreraCrudTest extends TestCase
{
    use DatabaseTransactions;

    public function test_usuario_autenticado_crea_una_carrera(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/carreras', ['sigla' => 'ING', 'nombre' => 'Ingeniería'])
            ->assertRedirect('/carreras');

        $this->assertDatabaseHas('carreras', ['sigla' => 'ING', 'nombre' => 'Ingeniería']);
    }

    public function test_usuario_autenticado_actualiza_una_carrera(): void
    {
        $carrera = Carrera::factory()->create();

        $this->actingAs(User::factory()->create())
            ->put("/carreras/{$carrera->id}", ['sigla' => 'ZZZ', 'nombre' => 'Carrera renombrada'])
            ->assertRedirect('/carreras');

        $this->assertDatabaseHas('carreras', ['id' => $carrera->id, 'sigla' => 'ZZZ', 'nombre' => 'Carrera renombrada']);
    }

    public function test_usuario_autenticado_elimina_una_carrera_sin_materias(): void
    {
        $carrera = Carrera::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete("/carreras/{$carrera->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('carreras', ['id' => $carrera->id]);
    }
}
