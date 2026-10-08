<?php

namespace Tests\Unit;

use App\Exceptions\InvalidDesignacionesResponse;
use App\Services\Jachasun\DesignacionesResponseMapper;
use Tests\TestCase;

class DesignacionesResponseMapperTest extends TestCase
{
    protected $connectionsToTransact = [];

    public function test_mapea_resumen_con_fecha_timestamp_y_campos_opcionales_nulos(): void
    {
        $resumen = (new DesignacionesResponseMapper)->designacionResumen((object) [
            'r_id' => '21',
            'r_id_programa' => 'INF',
            'r_programa' => 'Carrera sintética',
            'r_detalle' => 'Designación sintética',
            'r_fecha' => '2026-10-07 14:52:01.12345',
            'r_id_gestion' => 2026,
            'r_id_periodo' => '1',
            'r_obs' => null,
            'r_estado' => null,
        ]);

        $this->assertSame([
            'id' => 21,
            'programa_codigo' => 'INF',
            'programa_nombre' => 'Carrera sintética',
            'detalle' => 'Designación sintética',
            'fecha' => '2026-10-07 14:52:01.12345',
            'gestion' => '2026',
            'periodo' => '1',
            'observacion' => null,
            'estado' => null,
        ], $resumen->toArray());
    }

    public function test_mapea_cabecera_de_respuestas_de_escritura(): void
    {
        $cabecera = (new DesignacionesResponseMapper)->designacionCabecera((object) [
            'fecha' => '2026-10-07 14:52:01.12345',
            'id_programa' => 'INF',
            'programa' => 'Carrera sintética',
            'id_gestion' => 2026,
            'id_periodo' => 1,
            'obs' => null,
        ]);

        $this->assertSame([
            'fecha' => '2026-10-07 14:52:01.12345',
            'programa_codigo' => 'INF',
            'programa_nombre' => 'Carrera sintética',
            'gestion' => '2026',
            'periodo' => '1',
            'observacion' => null,
        ], $cabecera->toArray());
    }

    public function test_mapea_resultado_alternativo_de_actualizacion_de_detalle(): void
    {
        $detalle = (new DesignacionesResponseMapper)->detalleAsignacion((object) [
            'r_id' => 31,
            'r_id_detalle' => 31,
            'r_id_gestion' => 2026,
            'r_id_periodo' => 1,
            'r_id_materia' => 112,
            'r_materia' => 'Asignatura sintética',
            'r_id_docente' => 71,
            'r_docente' => 'Docente sintético',
            'r_id_grupo' => 2,
            'r_hrs_teoricas' => 2,
            'r_hrs_practicas' => 1,
            'r_hrs_laboratorio' => 0,
            'r_id_programa' => 'INF',
            'r_programa' => 'Carrera sintética',
        ]);

        $this->assertSame([
            'id' => 31,
            'docente_id' => 71,
            'ci' => null,
            'docente_nombre' => 'Docente sintético',
            'materia_id' => 112,
            'materia_sigla' => null,
            'materia_nombre' => 'Asignatura sintética',
            'grupo_id' => 2,
            'horas_teoricas' => 2,
            'horas_practicas' => 1,
            'horas_laboratorio' => 0,
        ], $detalle->toArray());
    }

    public function test_mapea_detalle_con_horas_enteras_y_datos_academicos_opcionales(): void
    {
        $detalle = (new DesignacionesResponseMapper)->detalleAsignacion([
            'r_id' => '31',
            'r_id_docente' => null,
            'r_ci' => null,
            'r_nombres' => null,
            'r_id_materia' => '112',
            'r_sigla' => 'INF101',
            'r_materia' => 'Asignatura sintética',
            'r_id_grupo' => '2',
            'r_hrs_teoricas' => '3',
            'r_hrs_practicas' => '0',
            'r_hrs_laboratorio' => null,
        ]);

        $this->assertSame([
            'id' => 31,
            'docente_id' => null,
            'ci' => null,
            'docente_nombre' => null,
            'materia_id' => 112,
            'materia_sigla' => 'INF101',
            'materia_nombre' => 'Asignatura sintética',
            'grupo_id' => 2,
            'horas_teoricas' => 3,
            'horas_practicas' => 0,
            'horas_laboratorio' => null,
        ], $detalle->toArray());
    }

    public function test_mapea_docentes_de_respuestas_institucionales_y_del_catalogo(): void
    {
        $mapper = new DesignacionesResponseMapper;

        $docenteInstitucional = $mapper->docenteResumen((object) [
            'r_id_docente' => '71',
            'r_ci' => 'TEST-0071',
            'r_paterno' => 'Apellido',
            'r_materno' => null,
            'r_nombres' => 'Docente sintético',
        ]);
        $docenteCatalogo = $mapper->docenteResumen([
            'id' => 72,
            'nombre' => 'Otra docente sintética',
            'ci' => null,
        ]);

        $this->assertSame([
            'id' => 71,
            'nombre' => 'Apellido Docente sintético',
            'ci' => 'TEST-0071',
        ], $docenteInstitucional->toArray());
        $this->assertSame([
            'id' => 72,
            'nombre' => 'Otra docente sintética',
            'ci' => null,
        ], $docenteCatalogo->toArray());
    }

    public function test_mapea_oferta_con_grupos_autorizados_y_horas_oficiales(): void
    {
        $oferta = (new DesignacionesResponseMapper)->ofertaMateria((object) [
            'r_id_materia' => 112,
            'r_sigla' => 'INF101',
            'r_materia' => 'Asignatura sintética',
            'r_nivel_academico' => '1',
            'r_id_mencion' => null,
            'hrs_teoricas' => '3',
            'hrs_practicas' => 2,
            'hrs_laboratorio' => 0,
        ], ['1', 2], 3, true);

        $this->assertSame([
            'id' => 112,
            'sigla' => 'INF101',
            'nombre' => 'Asignatura sintética',
            'nivel_academico' => 1,
            'mencion_id' => null,
            'horas_teoricas' => 3,
            'horas_practicas' => 2,
            'horas_laboratorio' => 0,
            'grupos' => [1, 2],
            'grupo_siguiente' => 3,
            'grupos_disponibles' => true,
        ], $oferta->toArray());
    }

    public function test_mapea_decision_y_revision_con_valores_nulos(): void
    {
        $mapper = new DesignacionesResponseMapper;

        $decision = $mapper->decisionAsignacion([
            'r_id_detalle' => '31',
            'r_estado' => null,
            'r_obs' => null,
        ]);
        $revision = $mapper->revisionDesignacion((object) [
            'r_estado' => 'OBSERVADA',
            'r_obs_vicerrectorado' => 'Observación sintética',
        ]);

        $this->assertSame([
            'id_detalle' => 31,
            'estado' => null,
            'observacion' => null,
        ], $decision->toArray());
        $this->assertSame([
            'estado' => 'OBSERVADA',
            'observacion' => 'Observación sintética',
        ], $revision->toArray());
    }

    public function test_rechaza_fecha_invalida_y_tipos_numericos_no_enteros(): void
    {
        $mapper = new DesignacionesResponseMapper;

        try {
            $mapper->designacionResumen([
                'r_id' => 21,
                'r_id_programa' => 'INF',
                'r_programa' => 'Carrera sintética',
                'r_detalle' => 'Designación sintética',
                'r_fecha' => '2026-02-30',
                'r_id_gestion' => 2026,
                'r_id_periodo' => 1,
                'r_obs' => null,
                'r_estado' => 'SOLICITADO',
            ]);
            $this->fail('La fecha inválida debía rechazarse.');
        } catch (InvalidDesignacionesResponse) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(InvalidDesignacionesResponse::class);
        $mapper->detalleAsignacion([
            'r_id' => 31,
            'r_id_docente' => null,
            'r_ci' => null,
            'r_nombres' => null,
            'r_id_materia' => 112,
            'r_sigla' => 'INF101',
            'r_materia' => 'Asignatura sintética',
            'r_id_grupo' => 2,
            'r_hrs_teoricas' => 1.5,
            'r_hrs_practicas' => 0,
            'r_hrs_laboratorio' => 0,
        ]);
    }

    public function test_rechaza_campos_requeridos_ausentes_y_valores_con_tipo_inesperado(): void
    {
        $mapper = new DesignacionesResponseMapper;

        try {
            $mapper->revisionDesignacion(['r_estado' => 'OBSERVADA']);
            $this->fail('La ausencia de observación debía rechazarse como respuesta incompleta.');
        } catch (InvalidDesignacionesResponse) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(InvalidDesignacionesResponse::class);
        $mapper->revisionDesignacion([
            'r_estado' => 12,
            'r_obs_vicerrectorado' => null,
        ]);
    }
}
