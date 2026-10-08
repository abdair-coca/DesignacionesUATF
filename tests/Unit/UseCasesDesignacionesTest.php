<?php

namespace Tests\Unit;

use App\Contracts\DesignacionesReadContract;
use App\Contracts\DesignacionesWriteContract;
use App\Data\Designaciones\DesignacionCabecera;
use App\Data\Designaciones\DesignacionResumen;
use App\Data\Designaciones\DetalleAsignacion;
use App\Data\Designaciones\OfertaMateria;
use App\Data\Designaciones\RevisionDesignacion;
use App\Domain\Designaciones\ReglasDecisionDesignacion;
use App\UseCases\Designaciones\ConsultarDesignaciones;
use App\UseCases\Designaciones\ConsultarDetalleDesignacion;
use App\UseCases\Designaciones\CopiarDesignacion;
use App\UseCases\Designaciones\GuardarAsignacion;
use App\UseCases\Designaciones\GuardarCabeceraDesignacion;
use App\UseCases\Designaciones\GuardarDecisionPorFila;
use App\UseCases\Designaciones\GuardarRevisionGeneral;
use App\UseCases\Designaciones\PrepararEdicionDesignacion;
use InvalidArgumentException;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery;
use PHPUnit\Framework\TestCase;

class UseCasesDesignacionesTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_consulta_de_lista_usa_el_contrato_de_lectura_tipado(): void
    {
        $lectura = Mockery::mock(DesignacionesReadContract::class);
        $lectura->shouldReceive('listar')->once()
            ->with('INF', '2026', '1')
            ->andReturn(collect([$this->designacion()]));

        $resultado = (new ConsultarDesignaciones($lectura))->porCarrera('INF', '2026', '1');

        $this->assertEquals([$this->designacion()], $resultado->all());
    }

    public function test_consulta_detalle_y_preparacion_de_edicion_coordinan_lecturas(): void
    {
        $lectura = Mockery::mock(DesignacionesReadContract::class);
        $lectura->shouldReceive('listar')->once()->with('INF', '0', '0')
            ->andReturn(collect([$this->designacion()]));
        $lectura->shouldReceive('detallar')->twice()->with(91)
            ->andReturn(collect([$this->detalle()]));
        $lectura->shouldReceive('ofertaMaterias')->once()->with('INF', '2026', '1')
            ->andReturn(collect([$this->oferta()]));

        $consulta = new ConsultarDetalleDesignacion($lectura);
        $this->assertEquals($this->designacion(), $consulta->porCarrera(91, 'INF'));
        $this->assertEquals([$this->detalle()], $consulta->filas(91)->all());

        $preparacion = (new PrepararEdicionDesignacion($lectura))->preparar($this->designacion());

        $this->assertEquals($this->designacion(), $preparacion->designacion);
        $this->assertEquals([$this->detalle()], $preparacion->filas);
        $this->assertEquals([$this->oferta()], $preparacion->materiasOferta);
    }

    public function test_copiar_rechaza_origen_ajeno_y_no_invoca_escritura(): void
    {
        $lectura = Mockery::mock(DesignacionesReadContract::class);
        $lectura->shouldReceive('listar')->once()->with('INF', '0', '0')->andReturn(collect());
        $escritura = Mockery::mock(DesignacionesWriteContract::class);
        $escritura->shouldNotReceive('copiar');

        $this->expectException(InvalidArgumentException::class);

        (new CopiarDesignacion($lectura, $escritura))->ejecutar('INF', 92, 2027, 1, 'COPIA SINTÉTICA');
    }

    public function test_actualizar_cabecera_verifica_pertenencia_antes_de_escribir(): void
    {
        $lectura = Mockery::mock(DesignacionesReadContract::class);
        $lectura->shouldReceive('listar')->once()->with('INF', '0', '0')
            ->andReturn(collect([$this->designacion()]));
        $escritura = Mockery::mock(DesignacionesWriteContract::class);
        $escritura->shouldReceive('actualizar')->once()
            ->with(91, 'INF', '2026-10-07', '2026', '1', 'OBSERVACIÓN SINTÉTICA')
            ->andReturn(new DesignacionCabecera(
                '2026-10-07',
                'INF',
                'Carrera sintética',
                '2026',
                '1',
                'OBSERVACIÓN SINTÉTICA',
            ));

        $cabecera = (new GuardarCabeceraDesignacion($lectura, $escritura))
            ->actualizar(91, 'INF', '2026-10-07', '2026', '1', 'OBSERVACIÓN SINTÉTICA');

        $this->assertSame('2026', $cabecera->gestion);
        $this->assertSame('OBSERVACIÓN SINTÉTICA', $cabecera->observacion);
    }

    public function test_guardar_asignacion_aplica_horas_grupo_y_contrato_de_escritura(): void
    {
        $lectura = Mockery::mock(DesignacionesReadContract::class);
        $lectura->shouldReceive('listar')->once()->with('INF', '0', '0')
            ->andReturn(collect([$this->designacion()]));
        $lectura->shouldReceive('detallar')->once()->with(91)->andReturn(collect());
        $lectura->shouldReceive('ofertaMaterias')->once()->with('INF', '2026', '1')
            ->andReturn(collect([$this->oferta()]));

        $detalleGuardado = $this->detalle();
        $escritura = Mockery::mock(DesignacionesWriteContract::class);
        $escritura->shouldReceive('guardarDetalle')->once()
            ->with(91, 0, 72, 101, 2, 2, 0, 0, 2026, true)
            ->andReturn(collect([$detalleGuardado]));

        $resultado = (new GuardarAsignacion($lectura, $escritura))
            ->ejecutar(91, 'INF', 0, 72, 101, 2, 2, 0, 0);

        $this->assertSame([$detalleGuardado], $resultado->all());
    }

    public function test_guardar_asignacion_rechaza_total_cero_sin_consultar_lecturas(): void
    {
        $lectura = Mockery::mock(DesignacionesReadContract::class);
        $lectura->shouldNotReceive('listar');
        $escritura = Mockery::mock(DesignacionesWriteContract::class);
        $escritura->shouldNotReceive('guardarDetalle');

        $this->expectException(InvalidArgumentException::class);

        (new GuardarAsignacion($lectura, $escritura))->ejecutar(91, 'INF', 0, 72, 101, 1, 0, 0, 0);
    }

    public function test_guardar_decision_por_fila_normaliza_estado_y_observacion(): void
    {
        $escritura = Mockery::mock(DesignacionesWriteContract::class);
        $escritura->shouldReceive('guardarDecisionAsignacionDetalle')->once()
            ->with(91, 31, 'RECHAZADA', 'Motivo sintético')
            ->andReturn(new \App\Data\Designaciones\DecisionAsignacion(31, 'RECHAZADA', 'Motivo sintético'));

        $decision = (new GuardarDecisionPorFila($escritura, new ReglasDecisionDesignacion))
            ->ejecutar(91, 31, ' rechazada ', ' Motivo sintético ');

        $this->assertSame('RECHAZADA', $decision->estado);
        $this->assertSame('Motivo sintético', $decision->observacion);
    }

    public function test_guardar_revision_general_aplica_regla_de_observacion_y_limpieza(): void
    {
        $escritura = Mockery::mock(DesignacionesWriteContract::class);
        $escritura->shouldReceive('guardarRevisionDesignacion')->once()
            ->with(91, 'APROBADO', null)
            ->andReturn(new RevisionDesignacion('APROBADO', null));

        $revision = (new GuardarRevisionGeneral($escritura, new ReglasDecisionDesignacion))
            ->ejecutar(91, ' aprobado ', 'Observación anterior');

        $this->assertSame('APROBADO', $revision->estado);
        $this->assertNull($revision->observacion);
    }

    private function designacion(): DesignacionResumen
    {
        return new DesignacionResumen(
            91,
            'INF',
            'Carrera sintética',
            'Designación sintética',
            '2026-01-15',
            '2026',
            '1',
            null,
            'SOLICITADO',
        );
    }

    private function detalle(): DetalleAsignacion
    {
        return new DetalleAsignacion(31, 72, 'TEST-0072', 'Docente sintético', 101, 'MAT-101', 'Asignatura sintética', 1, 2, 0, 0);
    }

    private function oferta(): OfertaMateria
    {
        return new OfertaMateria(101, 'MAT-101', 'Asignatura sintética', 1, null, 3, 2, 1, [1, 2], 2, true);
    }
}
