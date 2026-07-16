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

class DesignacionValidationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_store_requiere_campos_obligatorios(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/designaciones', [])
            ->assertSessionHasErrors(['Id_docente', 'Id_materia', 'Id_grupo', 'Id_gestion', 'Id_periodo']);
    }

    public function test_store_ignora_estado_del_usuario_y_fuerza_propuesta(): void
    {
        $docente = Docente::factory()->create();
        $materia = Materia::factory()->create();
        $grupo = Grupo::factory()->create(['materia_id' => $materia->id]);
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post('/designaciones', [
                'Id_docente' => $docente->id,
                'Id_materia' => $materia->id,
                'Id_grupo' => $grupo->id,
                'Id_gestion' => $gestion->id,
                'Id_periodo' => $periodo->id,
                'estado' => 'aprobada',  // usuario intenta forzar estado
            ])
            ->assertRedirect('/designaciones');

        $this->assertDatabaseHas('designaciones', [
            'Id_docente' => $docente->id,
            'estado' => 'propuesta',  // siempre forzado a propuesta
        ]);
    }

    public function test_store_rechaza_ids_inexistentes(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/designaciones', [
                'Id_docente' => 9999,
                'Id_materia' => 9999,
                'Id_grupo' => 9999,
                'Id_gestion' => 9999,
                'Id_periodo' => 9999,
            ])
            ->assertSessionHasErrors(['Id_docente', 'Id_materia', 'Id_grupo', 'Id_gestion', 'Id_periodo']);
    }

    public function test_update_permite_mismos_datos_sin_error(): void
    {
        $designacion = Designacion::factory()->create(['estado' => 'propuesta']);

        $this->actingAs(User::factory()->create())
            ->put("/designaciones/{$designacion->id}", [
                'Id_docente' => $designacion->Id_docente,
                'Id_materia' => $designacion->Id_materia,
                'Id_grupo' => $designacion->Id_grupo,
                'Id_gestion' => $designacion->Id_gestion,
                'Id_periodo' => $designacion->Id_periodo,
                'estado' => 'propuesta',
            ])
            ->assertRedirect('/designaciones');

        $this->assertDatabaseHas('designaciones', ['id' => $designacion->id, 'estado' => 'propuesta']);
    }

    public function test_update_rechaza_duplicado_con_otro_docente(): void
    {
        $materia = Materia::factory()->create();
        $grupo = Grupo::factory()->create(['materia_id' => $materia->id]);
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        $docenteA = Docente::factory()->create();
        $docenteB = Docente::factory()->create();

        Designacion::factory()->create([
            'Id_docente' => $docenteA->id,
            'Id_materia' => $materia->id,
            'Id_grupo' => $grupo->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
        ]);

        $designacion = Designacion::factory()->create([
            'Id_docente' => $docenteB->id,
            'Id_materia' => $materia->id,
            'Id_grupo' => $grupo->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
        ]);

        $this->actingAs(User::factory()->create())
            ->put("/designaciones/{$designacion->id}", [
                'Id_docente' => $docenteA->id,
                'Id_materia' => $materia->id,
                'Id_grupo' => $grupo->id,
                'Id_gestion' => $gestion->id,
                'Id_periodo' => $periodo->id,
                'estado' => 'propuesta',
            ])
            ->assertSessionHasErrors('Id_docente');
    }
}
