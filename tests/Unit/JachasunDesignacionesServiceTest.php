<?php

namespace Tests\Unit;

use App\Services\Jachasun\JachasunDesignacionesService;
use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Mockery;
use Tests\TestCase;

class JachasunDesignacionesServiceTest extends TestCase
{
    public function test_normaliza_filas_de_la_funcion_en_una_consulta_de_solo_lectura(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')
            ->once()
            ->with('SET TRANSACTION READ ONLY')
            ->andReturnTrue();
        $connection->shouldReceive('select')
            ->once()
            ->with(
                'SELECT * FROM designaciones.f_asignaciones(?, ?, ?)',
                ['INF', '2023', '1'],
            )
            ->andReturn([
                (object) [
                    'r_id' => 2117,
                    'r_id_programa' => 'INF',
                    'r_programa' => 'INGENIERIA INFORMATICA',
                    'r_detalle' => 'SEMESTRAL 1/2023',
                    'r_fecha' => '2024-10-23 15:07:01.38683',
                    'r_id_gestion' => 2023,
                    'r_id_periodo' => 1,
                    'r_obs' => 'MIGRADO',
                    'r_estado' => 'SOLICITADO',
                ],
            ]);

        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->once()->with('jachasun')->andReturn($connection);

        $rows = (new JachasunDesignacionesService($database))->listar('INF', '2023', '1');

        $this->assertSame([
            [
                'id' => 2117,
                'programa_codigo' => 'INF',
                'programa_nombre' => 'INGENIERIA INFORMATICA',
                'detalle' => 'SEMESTRAL 1/2023',
                'fecha' => '2024-10-23 15:07:01.38683',
                'gestion' => '2023',
                'periodo' => '1',
                'observacion' => 'MIGRADO',
                'estado' => 'SOLICITADO',
            ],
        ], $rows->all());
    }
}
