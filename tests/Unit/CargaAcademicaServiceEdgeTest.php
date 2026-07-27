<?php

namespace Tests\Unit;

use App\Models\Designacion;
use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Periodo;
use App\Support\CargaAcademicaService;
use Tests\TestCase;

class CargaAcademicaServiceEdgeTest extends TestCase
{
    private CargaAcademicaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CargaAcademicaService::class);
    }

    public function test_horas_asignadas_suma_multiples_materias(): void
    {
        $docente = Docente::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        $materia1 = Materia::factory()->create(['horas' => 3]);
        $materia2 = Materia::factory()->create(['horas' => 4]);
        $grupo1 = Grupo::factory()->create(['materia_id' => $materia1->id]);
        $grupo2 = Grupo::factory()->create(['materia_id' => $materia2->id]);

        Designacion::factory()->create([
            'Id_docente' => $docente->id,
            'Id_materia' => $materia1->id,
            'Id_grupo' => $grupo1->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'propuesta',
        ]);
        Designacion::factory()->create([
            'Id_docente' => $docente->id,
            'Id_materia' => $materia2->id,
            'Id_grupo' => $grupo2->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'propuesta',
        ]);

        $horas = $this->service->horasAsignadas($docente->id, $gestion->id, $periodo->id);

        $this->assertSame(7, $horas);
    }

    public function test_horas_asignadas_cero_sin_designaciones(): void
    {
        $docente = Docente::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        $horas = $this->service->horasAsignadas($docente->id, $gestion->id, $periodo->id);

        $this->assertSame(0, $horas);
    }

    public function test_cumple_minimo_con_adicionales(): void
    {
        $docente = Docente::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();
        $materia = Materia::factory()->create(['horas' => 5]);
        $grupo = Grupo::factory()->create(['materia_id' => $materia->id]);

        Designacion::factory()->create([
            'Id_docente' => $docente->id,
            'Id_materia' => $materia->id,
            'Id_grupo' => $grupo->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'propuesta',
        ]);

        $cumple = $this->service->cumpleMinimo($docente->id, $gestion->id, $periodo->id, 2);

        $this->assertTrue($cumple);
    }

    public function test_cumple_minimo_false_sin_horas_suficientes(): void
    {
        $docente = Docente::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();
        $materia = Materia::factory()->create(['horas' => 3]);
        $grupo = Grupo::factory()->create(['materia_id' => $materia->id]);

        Designacion::factory()->create([
            'Id_docente' => $docente->id,
            'Id_materia' => $materia->id,
            'Id_grupo' => $grupo->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'propuesta',
        ]);

        $cumple = $this->service->cumpleMinimo($docente->id, $gestion->id, $periodo->id);

        $this->assertFalse($cumple);
    }

    public function test_hay_choque_false_con_distinto_grupo(): void
    {
        $docente = Docente::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();
        $materia = Materia::factory()->create();
        $grupo1 = Grupo::factory()->create(['materia_id' => $materia->id]);
        $grupo2 = Grupo::factory()->create(['materia_id' => $materia->id]);

        Designacion::factory()->create([
            'Id_docente' => $docente->id,
            'Id_materia' => $materia->id,
            'Id_grupo' => $grupo1->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'propuesta',
        ]);

        $choque = $this->service->hayChoque($grupo2->id, $gestion->id, $periodo->id);

        $this->assertFalse($choque);
    }

    public function test_minimo_getter(): void
    {
        $this->assertSame(6, CargaAcademicaService::getMinimo());
        $this->assertSame(6, CargaAcademicaService::getLimite());
    }
}
