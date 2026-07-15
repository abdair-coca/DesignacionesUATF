<?php

namespace Tests\Unit;

use App\Models\Carrera;
use App\Models\Designacion;
use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Periodo;
use App\Support\DesignacionReportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DesignacionReportServiceTest extends TestCase
{
    use DatabaseTransactions;

    private DesignacionReportService $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servicio = app(DesignacionReportService::class);
    }

    public function test_resumen_por_carrera_cuenta_grupos_asignados(): void
    {
        $carrera = Carrera::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        $materia = Materia::factory()->create(['carrera_id' => $carrera->id, 'horas' => 4]);
        $grupo = Grupo::factory()->create(['materia_id' => $materia->id, 'estado' => 'habilitado']);

        Designacion::factory()->create([
            'Id_materia' => $materia->id,
            'Id_grupo' => $grupo->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'propuesta',
        ]);

        $resultado = $this->servicio->resumenPorCarrera($gestion->id, $periodo->id);
        $fila = $resultado->firstWhere('id', $carrera->id);

        $this->assertNotNull($fila);
        $this->assertSame(1, $fila['materias']);
        $this->assertSame(1, $fila['grupos']);
        $this->assertSame(1, $fila['activas']);
        $this->assertSame(0, $fila['pendientes']);
        $this->assertSame('activas', $fila['situacion']);
    }

    public function test_resumen_por_carrera_cuenta_pendientes(): void
    {
        $carrera = Carrera::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        $materia = Materia::factory()->create(['carrera_id' => $carrera->id]);
        $grupoA = Grupo::factory()->create(['materia_id' => $materia->id, 'estado' => 'habilitado']);
        $grupoB = Grupo::factory()->create(['materia_id' => $materia->id, 'estado' => 'habilitado']);

        Designacion::factory()->create([
            'Id_materia' => $materia->id,
            'Id_grupo' => $grupoA->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'propuesta',
        ]);

        $resultado = $this->servicio->resumenPorCarrera($gestion->id, $periodo->id);
        $fila = $resultado->firstWhere('id', $carrera->id);

        $this->assertSame(2, $fila['grupos']);
        $this->assertSame(1, $fila['activas']);
        $this->assertSame(1, $fila['pendientes']);
        $this->assertSame('pendientes', $fila['situacion']);
    }

    public function test_resumen_por_carrera_ignora_designaciones_rechazadas(): void
    {
        $carrera = Carrera::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        $materia = Materia::factory()->create(['carrera_id' => $carrera->id]);
        $grupo = Grupo::factory()->create(['materia_id' => $materia->id, 'estado' => 'habilitado']);

        Designacion::factory()->create([
            'Id_materia' => $materia->id,
            'Id_grupo' => $grupo->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'rechazada',
        ]);

        $resultado = $this->servicio->resumenPorCarrera($gestion->id, $periodo->id);
        $fila = $resultado->firstWhere('id', $carrera->id);

        $this->assertSame(0, $fila['activas']);
        $this->assertSame('sin', $fila['situacion']);
    }

    public function test_resumen_por_materia_cuenta_grupos_por_materia(): void
    {
        $carrera = Carrera::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        $materia = Materia::factory()->create(['carrera_id' => $carrera->id]);
        $grupoA = Grupo::factory()->create(['materia_id' => $materia->id, 'estado' => 'habilitado']);
        $grupoB = Grupo::factory()->create(['materia_id' => $materia->id, 'estado' => 'habilitado']);

        Designacion::factory()->create([
            'Id_materia' => $materia->id,
            'Id_grupo' => $grupoA->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'aprobada',
        ]);

        $resultado = $this->servicio->resumenPorMateria($carrera, $gestion->id, $periodo->id);
        $fila = $resultado->firstWhere('id', $materia->id);

        $this->assertSame(2, $fila['grupos_total']);
        $this->assertSame(1, $fila['grupos_asignados']);
        $this->assertSame('por_asignar', $fila['estado']);
    }

    public function test_datos_dashboard_cuenta_grupos_sin_designar(): void
    {
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        $materia = Materia::factory()->create();
        $grupo = Grupo::factory()->create(['materia_id' => $materia->id, 'estado' => 'habilitado']);

        $resultado = $this->servicio->datosDashboard($gestion->id, $periodo->id);

        $sinDesignar = $resultado['gruposSinDesignar']->pluck('id');
        $this->assertTrue($sinDesignar->contains($grupo->id));
    }

    public function test_datos_dashboard_cuenta_por_estado(): void
    {
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();
        $materia = Materia::factory()->create();

        Designacion::factory()->create([
            'Id_materia' => $materia->id,
            'Id_grupo' => Grupo::factory()->create(['materia_id' => $materia->id])->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'propuesta',
        ]);

        Designacion::factory()->create([
            'Id_materia' => $materia->id,
            'Id_grupo' => Grupo::factory()->create(['materia_id' => $materia->id])->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'aprobada',
        ]);

        $resultado = $this->servicio->datosDashboard($gestion->id, $periodo->id);

        $this->assertSame(1, $resultado['conteoEstado']['propuesta']);
        $this->assertSame(1, $resultado['conteoEstado']['aprobada']);
        $this->assertSame(0, $resultado['conteoEstado']['rechazada']);
    }

    public function test_resumen_carga_calcula_horas_proyectadas(): void
    {
        $docente = Docente::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();
        $materia = Materia::factory()->create(['horas' => 4]);
        $otraMateria = Materia::factory()->create(['horas' => 3]);
        $grupo = Grupo::factory()->create(['materia_id' => $materia->id]);
        $otroGrupo = Grupo::factory()->create(['materia_id' => $otraMateria->id]);

        Designacion::factory()->create([
            'Id_docente' => $docente->id,
            'Id_materia' => $materia->id,
            'Id_grupo' => $grupo->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'propuesta',
        ]);

        $resultado = $this->servicio->resumenCarga([
            'Id_docente' => $docente->id,
            'Id_materia' => $otraMateria->id,
            'Id_grupo' => $otroGrupo->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
        ]);

        $this->assertSame(4, $resultado['horasActuales']);
        $this->assertSame(7, $resultado['horasProyectadas']);
        $this->assertFalse($resultado['hayChoque']);
    }

    public function test_resumen_carga_detecta_choque(): void
    {
        $docente = Docente::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();
        $materia = Materia::factory()->create(['horas' => 4]);
        $grupo = Grupo::factory()->create(['materia_id' => $materia->id]);

        Designacion::factory()->create([
            'Id_grupo' => $grupo->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'propuesta',
        ]);

        $resultado = $this->servicio->resumenCarga([
            'Id_docente' => $docente->id,
            'Id_materia' => $materia->id,
            'Id_grupo' => $grupo->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
        ]);

        $this->assertTrue($resultado['hayChoque']);
    }
}
