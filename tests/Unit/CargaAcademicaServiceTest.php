<?php

namespace Tests\Unit;

use App\Models\Designacion;
use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Periodo;
use App\Support\CargaAcademicaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CargaAcademicaServiceTest extends TestCase
{
    use DatabaseTransactions;

    private CargaAcademicaService $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servicio = new CargaAcademicaService();
    }

    public function test_horas_asignadas_suma_solo_designaciones_no_rechazadas_de_la_gestion_y_periodo(): void
    {
        $docente = Docente::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();
        $otraGestion = Gestion::factory()->create();

        $materiaA = Materia::factory()->create(['horas' => 4]);
        $materiaB = Materia::factory()->create(['horas' => 3]);
        $materiaRechazada = Materia::factory()->create(['horas' => 6]);
        $materiaOtraGestion = Materia::factory()->create(['horas' => 5]);

        Designacion::factory()->create([
            'Id_docente' => $docente->id,
            'Id_materia' => $materiaA->id,
            'Id_grupo' => Grupo::factory()->create(['materia_id' => $materiaA->id])->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'propuesta',
        ]);

        Designacion::factory()->create([
            'Id_docente' => $docente->id,
            'Id_materia' => $materiaB->id,
            'Id_grupo' => Grupo::factory()->create(['materia_id' => $materiaB->id])->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'aprobada',
        ]);

        Designacion::factory()->create([
            'Id_docente' => $docente->id,
            'Id_materia' => $materiaRechazada->id,
            'Id_grupo' => Grupo::factory()->create(['materia_id' => $materiaRechazada->id])->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'rechazada',
        ]);

        Designacion::factory()->create([
            'Id_docente' => $docente->id,
            'Id_materia' => $materiaOtraGestion->id,
            'Id_grupo' => Grupo::factory()->create(['materia_id' => $materiaOtraGestion->id])->id,
            'Id_gestion' => $otraGestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'propuesta',
        ]);

        $horas = $this->servicio->horasAsignadas($docente->id, $gestion->id, $periodo->id);

        $this->assertSame(7, $horas);
    }

    public function test_horas_asignadas_excluye_la_designacion_indicada(): void
    {
        $docente = Docente::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();
        $materia = Materia::factory()->create(['horas' => 4]);

        $designacion = Designacion::factory()->create([
            'Id_docente' => $docente->id,
            'Id_materia' => $materia->id,
            'Id_grupo' => Grupo::factory()->create(['materia_id' => $materia->id])->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'propuesta',
        ]);

        $horas = $this->servicio->horasAsignadas($docente->id, $gestion->id, $periodo->id, $designacion->id);

        $this->assertSame(0, $horas);
    }

    public function test_hay_choque_detecta_otra_designacion_activa_en_el_mismo_grupo_gestion_y_periodo(): void
    {
        $grupo = Grupo::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        Designacion::factory()->create([
            'Id_grupo' => $grupo->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'propuesta',
        ]);

        $this->assertTrue($this->servicio->hayChoque($grupo->id, $gestion->id, $periodo->id));
    }

    public function test_hay_choque_ignora_designaciones_rechazadas(): void
    {
        $grupo = Grupo::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        Designacion::factory()->create([
            'Id_grupo' => $grupo->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'rechazada',
        ]);

        $this->assertFalse($this->servicio->hayChoque($grupo->id, $gestion->id, $periodo->id));
    }

    public function test_hay_choque_excluye_la_designacion_indicada(): void
    {
        $grupo = Grupo::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        $designacion = Designacion::factory()->create([
            'Id_grupo' => $grupo->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'propuesta',
        ]);

        $this->assertFalse($this->servicio->hayChoque($grupo->id, $gestion->id, $periodo->id, $designacion->id));
    }
}
