<?php

namespace Tests\Unit;

use App\Contracts\DesignacionesReadContract;
use App\Contracts\DesignacionesWriteContract;
use App\Data\Designaciones\DesignacionResumen;
use App\Services\Jachasun\JachasunDesignacionesReadAdapter;
use App\Services\Jachasun\JachasunDesignacionesService;
use App\Services\Jachasun\JachasunDesignacionesWriteAdapter;
use Mockery;
use Tests\TestCase;

class DesignacionesGatewayTest extends TestCase
{
    protected $connectionsToTransact = [];

    public function test_contratos_se_resuelven_a_adaptadores_separados(): void
    {
        $this->assertInstanceOf(JachasunDesignacionesReadAdapter::class, app(DesignacionesReadContract::class));
        $this->assertInstanceOf(JachasunDesignacionesWriteAdapter::class, app(DesignacionesWriteContract::class));
    }

    public function test_contenedor_resuelve_el_servicio_con_los_contratos_separados(): void
    {
        $this->assertInstanceOf(JachasunDesignacionesService::class, app(JachasunDesignacionesService::class));
    }

    public function test_servicio_puede_sustituir_lectura_y_serializa_dto_en_su_limite_publico(): void
    {
        $lectura = Mockery::mock(DesignacionesReadContract::class);
        $lectura->shouldReceive('listar')->once()
            ->with('INF', '2026', '1')
            ->andReturn(collect([$this->designacion()]));
        $escritura = Mockery::mock(DesignacionesWriteContract::class);

        $filas = (new JachasunDesignacionesService(null, $lectura, $escritura))
            ->listar('INF', '2026', '1');

        $this->assertSame([[
            'id' => 91,
            'programa_codigo' => 'INF',
            'programa_nombre' => 'Carrera sintética',
            'detalle' => 'Designación sintética',
            'fecha' => '2026-01-15',
            'gestion' => '2026',
            'periodo' => '1',
            'observacion' => null,
            'estado' => 'SOLICITADO',
        ]], $filas->all());
    }

    public function test_servicio_verifica_el_alcance_con_lectura_antes_de_copiar(): void
    {
        $lectura = Mockery::mock(DesignacionesReadContract::class);
        $lectura->shouldReceive('listar')->once()
            ->with('INF', '0', '0')
            ->andReturn(collect([$this->designacion()]));
        $escritura = Mockery::mock(DesignacionesWriteContract::class);
        $escritura->shouldReceive('copiar')->once()->with(91, '2027', '1', 'Copia sintética');

        (new JachasunDesignacionesService(null, $lectura, $escritura))
            ->copiar('INF', 91, '2027', '1', 'Copia sintética');
    }

    private function designacion(): DesignacionResumen
    {
        return new DesignacionResumen(
            id: 91,
            programaCodigo: 'INF',
            programaNombre: 'Carrera sintética',
            detalle: 'Designación sintética',
            fecha: '2026-01-15',
            gestion: '2026',
            periodo: '1',
            observacion: null,
            estado: 'SOLICITADO',
        );
    }
}
