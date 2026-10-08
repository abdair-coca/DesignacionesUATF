<?php

namespace Tests\Unit;

use App\Data\Designaciones\OfertaMateria;
use App\Domain\Designaciones\CargaHoraria;
use App\Domain\Designaciones\ReglasDecisionDesignacion;
use App\Domain\Designaciones\SeleccionGrupo;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ReglasDesignacionesTest extends TestCase
{
    protected $connectionsToTransact = [];

    public function test_valida_horas_no_negativas_no_cero_y_dentro_de_la_oferta(): void
    {
        $horas = CargaHoraria::validarEntrada(2, 0, 1);
        $horas->validarContraOferta(3, 0, 1);

        $this->assertSame(2, $horas->teoricas);
        $this->assertSame(0, $horas->practicas);
        $this->assertSame(1, $horas->laboratorio);
    }

    public function test_rechaza_horas_negativas(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CargaHoraria::validarEntrada(2, -1, 0);
    }

    public function test_rechaza_asignacion_con_todas_las_horas_en_cero(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CargaHoraria::validarEntrada(0, 0, 0);
    }

    public function test_rechaza_horas_mayores_a_las_oficiales(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CargaHoraria::validarEntrada(4, 0, 0)->validarContraOferta(3, 0, 0);
    }

    public function test_nueva_asignacion_usa_el_siguiente_grupo_tras_la_oferta_y_las_filas(): void
    {
        $seleccion = SeleccionGrupo::calcular($this->oferta([1, 2], 3, true), [1, 2], null);

        $this->assertSame(3, $seleccion->grupoSiguiente);
        $this->assertContains(3, $seleccion->gruposPermitidos);
        $seleccion->validarNuevo(3);
    }

    public function test_fallback_de_grupo_usa_el_maximo_existente_y_respeta_la_disponibilidad(): void
    {
        $seleccion = SeleccionGrupo::calcular($this->oferta([], null, false), [1, 4], null);

        $this->assertSame(5, $seleccion->grupoSiguiente);
        $seleccion->validarNuevo(5);
        $this->expectException(InvalidArgumentException::class);
        $seleccion->validarNuevo(6);
    }

    public function test_editar_conserva_el_grupo_actual_y_acepta_solo_grupos_autorizados(): void
    {
        $seleccion = SeleccionGrupo::calcular($this->oferta([1, 2], 3, true), [1, 2], 7);

        $this->assertSame(3, $seleccion->grupoSiguiente);
        $seleccion->validarEdicion(7);
        $seleccion->validarEdicion(2);

        $this->expectException(InvalidArgumentException::class);
        $seleccion->validarEdicion(4);
    }

    public function test_edicion_sin_catalogo_conserva_el_grupo_actual_o_usa_el_siguiente(): void
    {
        $seleccion = SeleccionGrupo::calcular($this->oferta([], null, false), [1, 2], 2);

        $seleccion->validarEdicion(2);
        $seleccion->validarEdicion(3);

        $this->expectException(InvalidArgumentException::class);
        $seleccion->validarEdicion(1);
    }

    public function test_valida_decisiones_por_fila_y_limpia_observacion_vacia(): void
    {
        $reglas = new ReglasDecisionDesignacion;
        $decision = $reglas->decisionFila(9, ' aprobada ', '   ');

        $this->assertSame(['id_detalle' => 9, 'estado' => 'APROBADA', 'observacion' => null], $decision->toArray());
        $this->assertSame(
            ['id_detalle' => 10, 'estado' => 'RECHAZADA', 'observacion' => 'Motivo sintético'],
            $reglas->decisionFila(10, 'rechazada', ' Motivo sintético ')->toArray(),
        );
    }

    public function test_rechaza_estado_o_motivo_de_decision_invalido(): void
    {
        $reglas = new ReglasDecisionDesignacion;

        try {
            $reglas->decisionFila(9, 'OBSERVADA', null);
            $this->fail('El estado no permitido debía rechazarse.');
        } catch (InvalidArgumentException) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(InvalidArgumentException::class);
        $reglas->decisionFila(9, 'APROBADA', str_repeat('x', 1001));
    }

    public function test_revision_general_permite_observar_con_motivo_y_aprobar_limpiando_el_previo(): void
    {
        $reglas = new ReglasDecisionDesignacion;

        $this->assertSame(
            ['estado' => 'OBSERVADA', 'observacion' => 'Motivo sintético'],
            $reglas->revisionGeneral('observada', ' Motivo sintético ')->toArray(),
        );
        $this->assertSame(
            ['estado' => 'APROBADO', 'observacion' => null],
            $reglas->revisionGeneral('aprobado', 'Motivo previo')->toArray(),
        );
    }

    public function test_revision_general_requiere_motivo_solo_para_observar(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ReglasDecisionDesignacion)->revisionGeneral('OBSERVADA', '  ');
    }

    public function test_estado_general_permanece_pendiente_sin_filas_o_con_filas_pendientes(): void
    {
        $reglas = new ReglasDecisionDesignacion;

        $this->assertSame('SOLICITADO', $reglas->estadoGeneralCalculado([]));
        $this->assertSame('SOLICITADO', $reglas->estadoGeneralCalculado(['APROBADA', null]));
        $this->assertSame('SOLICITADO', $reglas->estadoGeneralCalculado(['APROBADA', 'PENDIENTE']));
    }

    public function test_estado_general_se_aprueba_o_se_observa_al_resolver_todas_las_filas(): void
    {
        $reglas = new ReglasDecisionDesignacion;

        $this->assertSame('APROBADO', $reglas->estadoGeneralCalculado(['APROBADA', 'APROBADA']));
        $this->assertSame('OBSERVADA', $reglas->estadoGeneralCalculado(['APROBADA', 'RECHAZADA']));
        $this->assertSame('OBSERVADA', $reglas->estadoGeneralCalculado(['RECHAZADA']));
    }

    /** @param array<int, int> $grupos */
    private function oferta(array $grupos, ?int $grupoSiguiente, bool $gruposDisponibles): OfertaMateria
    {
        return new OfertaMateria(
            id: 100,
            sigla: 'MAT-100',
            nombre: 'Asignatura sintética',
            nivelAcademico: 1,
            mencionId: null,
            horasTeoricas: 3,
            horasPracticas: 2,
            horasLaboratorio: 1,
            grupos: $grupos,
            grupoSiguiente: $grupoSiguiente,
            gruposDisponibles: $gruposDisponibles,
        );
    }
}
