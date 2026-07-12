<?php

namespace Tests\Feature\Catalogos;

use App\Models\Periodo;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PeriodoCrudTest extends TestCase
{
    use DatabaseTransactions;

    public function test_usuario_autenticado_crea_un_periodo(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/periodos', ['nombre' => 'Piloto'])
            ->assertRedirect('/periodos');

        $this->assertDatabaseHas('periodos', ['nombre' => 'Piloto']);
    }

    public function test_usuario_autenticado_actualiza_un_periodo(): void
    {
        $periodo = Periodo::factory()->create();

        $this->actingAs(User::factory()->create())
            ->put("/periodos/{$periodo->id}", ['nombre' => 'Renombrado'])
            ->assertRedirect('/periodos');

        $this->assertDatabaseHas('periodos', ['id' => $periodo->id, 'nombre' => 'Renombrado']);
    }

    public function test_usuario_autenticado_elimina_un_periodo_sin_designaciones(): void
    {
        $periodo = Periodo::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete("/periodos/{$periodo->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('periodos', ['id' => $periodo->id]);
    }
}
