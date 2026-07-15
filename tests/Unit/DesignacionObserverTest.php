<?php

namespace Tests\Unit;

use App\Models\Designacion;
use App\Models\Docente;
use App\Models\Materia;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DesignacionObserverTest extends TestCase
{
    use DatabaseTransactions;

    public function test_registra_historial_al_cambiar_estado(): void
    {
        $designacion = Designacion::factory()->create(['estado' => 'propuesta']);
        $usuario = User::factory()->create();

        $this->actingAs($usuario);

        $designacion->update(['estado' => 'aprobada']);

        $this->assertDatabaseHas('designaciones_historial', [
            'designacion_id' => $designacion->id,
            'campo' => 'estado',
            'valor_anterior' => 'propuesta',
            'valor_nuevo' => 'aprobada',
            'usuario_id' => $usuario->id,
        ]);
    }

    public function test_registra_historial_al_cambiar_docente(): void
    {
        $docenteOriginal = Docente::factory()->create();
        $docenteNuevo = Docente::factory()->create();
        $designacion = Designacion::factory()->create(['Id_docente' => $docenteOriginal->id]);

        $designacion->update(['Id_docente' => $docenteNuevo->id]);

        $this->assertDatabaseHas('designaciones_historial', [
            'designacion_id' => $designacion->id,
            'campo' => 'Id_docente',
            'valor_anterior' => (string) $docenteOriginal->id,
            'valor_nuevo' => (string) $docenteNuevo->id,
        ]);
    }

    public function test_no_registra_historial_si_no_hay_cambios(): void
    {
        $designacion = Designacion::factory()->create(['estado' => 'propuesta']);

        $designacion->update(['estado' => 'propuesta']);

        $this->assertDatabaseMissing('designaciones_historial', [
            'designacion_id' => $designacion->id,
            'campo' => 'estado',
        ]);
    }

    public function test_registra_multiples_cambios_en_una_sola_operacion(): void
    {
        $docente = Docente::factory()->create();
        $materia = Materia::factory()->create();
        $designacion = Designacion::factory()->create([
            'Id_docente' => $docente->id,
            'Id_materia' => $materia->id,
            'estado' => 'propuesta',
        ]);

        $nuevoDocente = Docente::factory()->create();
        $designacion->update([
            'Id_docente' => $nuevoDocente->id,
            'estado' => 'aprobada',
        ]);

        $this->assertDatabaseHas('designaciones_historial', [
            'designacion_id' => $designacion->id,
            'campo' => 'Id_docente',
        ]);

        $this->assertDatabaseHas('designaciones_historial', [
            'designacion_id' => $designacion->id,
            'campo' => 'estado',
        ]);
    }

    public function test_no_registra_historial_para_campos_no_rastreados(): void
    {
        $usuario = User::factory()->create();
        $designacion = Designacion::factory()->create(['creado_por' => $usuario->id]);

        $designacion->update(['creado_por' => $usuario->id]);

        $this->assertDatabaseMissing('designaciones_historial', [
            'designacion_id' => $designacion->id,
            'campo' => 'creado_por',
        ]);
    }
}
