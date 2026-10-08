<?php

namespace Tests\Unit;

use App\Services\Jachasun\JachasunDesignacionesService;
use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Mockery;
use Tests\TestCase;

class DecanaturaDesignacionesServiceTest extends TestCase
{
    protected $connectionsToTransact = [];

    public function test_programas_de_facultad_usa_una_consulta_parametrizada_de_solo_lectura(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->once()->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('select')->once()->with(
            'SELECT DISTINCT programas.id_programa
FROM academico.alm_programas_facultades AS facultades
INNER JOIN academico.alm_programas AS programas
    ON programas.id_facultad = facultades.id_facultad
WHERE facultades.id_facultad = ?
ORDER BY programas.id_programa',
            [73001],
        )->andReturn([
            (object) ['id_programa' => 'INF'],
            (object) ['id_programa' => 'MED'],
            (object) ['id_programa' => 'INF '],
        ]);
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->once()->with('pgsql')->andReturn($connection);

        $programas = (new JachasunDesignacionesService($database))->listarProgramasPorFacultad(73001);

        $this->assertSame(['INF', 'MED'], $programas->all());
    }

    public function test_lista_de_facultad_filtra_aprobadas_y_ordena_establemente(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->times(3)->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->times(3)->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('select')->once()->with(
            Mockery::on(fn (string $sql): bool => str_contains($sql, 'academico.alm_programas_facultades')
                && str_contains($sql, 'academico.alm_programas')),
            [73001],
        )->andReturn([
            (object) ['id_programa' => 'INF'],
            (object) ['id_programa' => 'MED'],
        ]);
        $connection->shouldReceive('select')->once()->with(
            'SELECT * FROM designaciones.f_asignaciones(?, ?, ?)',
            ['INF', '0', '0'],
        )->andReturn([
            $this->filaAsignacion(2117, 'INF', 'APROBADO', '2026-01-01'),
            $this->filaAsignacion(2118, 'INF', 'SOLICITADO', '2026-03-01'),
            $this->filaAsignacion(9999, 'MED', 'APROBADO', '2026-04-01'),
        ]);
        $connection->shouldReceive('select')->once()->with(
            'SELECT * FROM designaciones.f_asignaciones(?, ?, ?)',
            ['MED', '0', '0'],
        )->andReturn([
            $this->filaAsignacion(3117, 'MED', 'APROBADO', '2026-02-01'),
            $this->filaAsignacion(3118, 'MED', 'aprobado', '2026-01-01'),
        ]);
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->times(3)->with('pgsql')->andReturn($connection);

        $designaciones = (new JachasunDesignacionesService($database))->listarAprobadasPorFacultad(73001);

        $this->assertSame([3117, 3118, 2117], $designaciones->pluck('id')->all());
        $this->assertSame(['MED', 'MED', 'INF'], $designaciones->pluck('programa_codigo')->all());
        $this->assertTrue($designaciones->every(fn (array $fila): bool => strtoupper($fila['estado']) === 'APROBADO'));
    }

    public function test_facultad_sin_programas_no_consulta_designaciones(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->once()->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('select')->once()->andReturn([]);
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->once()->with('pgsql')->andReturn($connection);

        $designaciones = (new JachasunDesignacionesService($database))->listarAprobadasPorFacultad(73001);

        $this->assertTrue($designaciones->isEmpty());
    }

    private function filaAsignacion(int $id, string $programa, string $estado, string $fecha): object
    {
        return (object) [
            'r_id' => $id,
            'r_id_programa' => $programa,
            'r_programa' => $programa,
            'r_detalle' => 'SEMESTRAL 1/2026',
            'r_fecha' => $fecha,
            'r_id_gestion' => 2026,
            'r_id_periodo' => 1,
            'r_obs' => null,
            'r_estado' => $estado,
        ];
    }
}
