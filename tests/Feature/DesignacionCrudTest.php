<?php

namespace Tests\Feature;

use App\Models\Designacion;
use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Periodo;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DesignacionCrudTest extends TestCase
{
    use DatabaseTransactions;

    public function test_usuario_autenticado_crea_una_designacion_y_queda_registrado_como_creador(): void
    {
        $docente = Docente::factory()->create();
        $materia = Materia::factory()->create();
        $grupo = Grupo::factory()->create(['materia_id' => $materia->id]);
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->post('/designaciones', [
                'Id_docente' => $docente->id,
                'Id_materia' => $materia->id,
                'Id_grupo' => $grupo->id,
                'Id_gestion' => $gestion->id,
                'Id_periodo' => $periodo->id,
                'estado' => 'propuesta',
            ])
            ->assertRedirect('/designaciones');

        $this->assertDatabaseHas('designaciones', [
            'Id_docente' => $docente->id,
            'Id_grupo' => $grupo->id,
            'creado_por' => $usuario->id,
        ]);
    }

    public function test_usuario_autenticado_actualiza_una_designacion_y_registra_historial(): void
    {
        $designacion = Designacion::factory()->create(['estado' => 'propuesta']);
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->put("/designaciones/{$designacion->id}", [
                'Id_docente' => $designacion->Id_docente,
                'Id_materia' => $designacion->Id_materia,
                'Id_grupo' => $designacion->Id_grupo,
                'Id_gestion' => $designacion->Id_gestion,
                'Id_periodo' => $designacion->Id_periodo,
                'estado' => 'aprobada',
            ])
            ->assertRedirect('/designaciones');

        $this->assertDatabaseHas('designaciones', ['id' => $designacion->id, 'estado' => 'aprobada']);
        $this->assertDatabaseHas('designaciones_historial', [
            'designacion_id' => $designacion->id,
            'campo' => 'estado',
            'valor_anterior' => 'propuesta',
            'valor_nuevo' => 'aprobada',
            'usuario_id' => $usuario->id,
        ]);
    }

    public function test_usuario_autenticado_elimina_una_designacion(): void
    {
        $designacion = Designacion::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete("/designaciones/{$designacion->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('designaciones', ['id' => $designacion->id]);
    }
}
