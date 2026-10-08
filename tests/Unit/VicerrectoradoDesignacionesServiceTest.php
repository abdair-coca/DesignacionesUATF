<?php

namespace Tests\Unit;

use App\Data\Designaciones\RevisionDesignacion;
use App\Exceptions\InvalidDesignacionesResponse;
use App\Services\Jachasun\JachasunDesignacionesService;
use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class VicerrectoradoDesignacionesServiceTest extends TestCase
{
    public function test_lista_decisiones_por_asignacion_en_transaccion_de_solo_lectura(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->once()->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('select')->once()
            ->with('SELECT * FROM designaciones.f_listar_decisiones_asignacion_detalle(?)', [22])
            ->andReturn([(object) ['r_id_detalle' => 901, 'r_estado' => 'APROBADA', 'r_obs' => null]]);

        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->once()->with('pgsql')->andReturn($connection);

        $decisiones = (new JachasunDesignacionesService($database))->listarDecisionesAsignacionDetalle(22);

        $this->assertSame([
            ['id_detalle' => 901, 'estado' => 'APROBADA', 'observacion' => null],
        ], $decisiones->all());
    }

    public function test_aprueba_asignacion_y_limpia_observacion_vacia(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('select')->once()
            ->with(
                'SELECT * FROM designaciones.f_guardar_decision_asignacion_detalle(?, ?, ?, ?)',
                [22, 901, 'APROBADA', null],
            )
            ->andReturn([(object) ['r_id_detalle' => 901, 'r_estado' => 'APROBADA', 'r_obs' => null]]);

        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->once()->with('pgsql')->andReturn($connection);

        $decision = (new JachasunDesignacionesService($database))
            ->guardarDecisionAsignacionDetalle(22, 901, 'APROBADA', '   ');

        $this->assertSame([
            'id_detalle' => 901,
            'estado' => 'APROBADA',
            'observacion' => null,
        ], $decision);
    }

    public function test_rechaza_decision_para_un_detalle_que_no_pertenece_a_la_designacion(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('select')->once()
            ->with(
                'SELECT * FROM designaciones.f_guardar_decision_asignacion_detalle(?, ?, ?, ?)',
                [22, 902, 'RECHAZADA', 'Revisión sintética'],
            )
            ->andReturn([]);

        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->once()->with('pgsql')->andReturn($connection);

        $this->expectException(InvalidArgumentException::class);

        (new JachasunDesignacionesService($database))
            ->guardarDecisionAsignacionDetalle(22, 902, 'RECHAZADA', 'Revisión sintética');
    }

    public function test_lista_revision_general_en_transaccion_de_solo_lectura(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->once()->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('select')->once()
            ->with('SELECT * FROM designaciones.f_obtener_revision_designacion(?)', [22])
            ->andReturn([(object) ['r_estado' => 'OBSERVADA', 'r_obs_vicerrectorado' => 'Revisar la carga horaria']]);

        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->once()->with('pgsql')->andReturn($connection);

        $revision = (new JachasunDesignacionesService($database))->obtenerRevisionDesignacion(22);

        $this->assertInstanceOf(RevisionDesignacion::class, $revision);
        $this->assertSame([
            'estado' => 'OBSERVADA',
            'observacion' => 'Revisar la carga horaria',
        ], $revision->toArray());
    }

    public function test_respuesta_sin_filas_de_revision_falla_con_excepcion_controlada(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->once()->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('select')->once()
            ->with('SELECT * FROM designaciones.f_obtener_revision_designacion(?)', [22])
            ->andReturn([]);

        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->once()->with('pgsql')->andReturn($connection);

        $this->expectException(InvalidDesignacionesResponse::class);

        (new JachasunDesignacionesService($database))->obtenerRevisionDesignacion(22);
    }

    public function test_guarda_observacion_general_en_transaccion_de_escritura(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->never();
        $connection->shouldReceive('select')->once()
            ->with(
                'SELECT * FROM designaciones.f_guardar_revision_designacion(?, ?, ?)',
                [22, 'OBSERVADA', 'Revisar la carga horaria'],
            )
            ->andReturn([(object) ['r_estado' => 'OBSERVADA', 'r_obs_vicerrectorado' => 'Revisar la carga horaria']]);

        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->once()->with('pgsql')->andReturn($connection);

        $revision = (new JachasunDesignacionesService($database))
            ->guardarRevisionDesignacion(22, 'OBSERVADA', ' Revisar la carga horaria ');

        $this->assertSame([
            'estado' => 'OBSERVADA',
            'observacion' => 'Revisar la carga horaria',
        ], $revision);
    }

    public function test_aprobar_revision_general_limpia_su_observacion(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('select')->once()
            ->with(
                'SELECT * FROM designaciones.f_guardar_revision_designacion(?, ?, ?)',
                [22, 'APROBADO', null],
            )
            ->andReturn([(object) ['r_estado' => 'APROBADO', 'r_obs_vicerrectorado' => null]]);

        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->once()->with('pgsql')->andReturn($connection);

        $revision = (new JachasunDesignacionesService($database))
            ->guardarRevisionDesignacion(22, 'APROBADO', 'Motivo anterior');

        $this->assertSame(['estado' => 'APROBADO', 'observacion' => null], $revision);
    }

    public function test_obtener_designacion_universitaria_busca_en_todos_los_periodos(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->once()->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('select')->once()->withArgs(function (string $sql, array $bindings): bool {
            return substr_count($sql, 'designaciones.f_asignaciones(?, ?, ?)') === 2
                && str_contains($sql, 'WHERE r_id = ?')
                && $bindings === ['UATF', '2026', '1', 'UATF', '2026', '2', 22];
        })->andReturn([
            (object) [
                'r_id' => 22,
                'r_id_programa' => 'MED',
                'r_programa' => 'MEDICINA',
                'r_detalle' => 'SEMESTRAL 1/2026',
                'r_fecha' => '2026-09-20',
                'r_id_gestion' => 2026,
                'r_id_periodo' => 2,
                'r_obs' => null,
                'r_estado' => 'SOLICITADO',
            ],
        ]);

        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->once()->with('pgsql')->andReturn($connection);

        $designacion = (new JachasunDesignacionesService($database))
            ->obtenerUniversidad(22, '2026');

        $this->assertSame(22, $designacion['id']);
        $this->assertSame('2', $designacion['periodo']);
        $this->assertSame('SOLICITADO', $designacion['estado']);
    }

    protected $connectionsToTransact = [];

    public function test_lista_universitaria_consulta_uatf_con_gestion_y_todos_los_periodos(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->once()->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('select')->once()->withArgs(function (string $sql, array $bindings): bool {
            return substr_count($sql, 'designaciones.f_asignaciones(?, ?, ?)') === 2
                && str_contains($sql, 'ORDER BY r_fecha DESC NULLS LAST, r_id DESC')
                && ! str_contains($sql, '\\n')
                && $bindings === ['UATF', '2026', '1', 'UATF', '2026', '2', 10, 0];
        })->andReturn([
            (object) [
                'r_id' => 22,
                'r_id_programa' => 'MED',
                'r_programa' => 'MEDICINA',
                'r_detalle' => 'SEMESTRAL 1/2026',
                'r_fecha' => '2026-09-20',
                'r_id_gestion' => 2026,
                'r_id_periodo' => 1,
                'r_obs' => null,
                'r_estado' => 'RECHAZADA',
                'total_filas' => 2,
            ],
            (object) [
                'r_id' => 21,
                'r_id_programa' => 'INF',
                'r_programa' => 'INGENIERIA INFORMATICA',
                'r_detalle' => 'SEMESTRAL 2/2026',
                'r_fecha' => '2026-09-19',
                'r_id_gestion' => 2026,
                'r_id_periodo' => 2,
                'r_obs' => null,
                'r_estado' => 'SOLICITADO',
                'total_filas' => 2,
            ],
        ]);

        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->once()->with('pgsql')->andReturn($connection);

        $resultado = (new JachasunDesignacionesService($database))
            ->listarUniversidadPaginado('2026');

        $this->assertSame(2, $resultado['total']);
        $this->assertSame('MED', $resultado['items']->first()['programa_codigo']);
        $this->assertSame('RECHAZADA', $resultado['items']->first()['estado']);
    }

    public function test_lista_universitaria_entrega_todas_las_filas_ordenadas_para_filtrado_local(): void
    {
        $filas = collect(range(1, 12))->map(fn (int $numero): object => (object) [
            'r_id' => 100 + $numero,
            'r_id_programa' => $numero % 2 === 0 ? 'MED' : 'INF',
            'r_programa' => $numero % 2 === 0 ? 'MEDICINA' : 'INFORMATICA',
            'r_detalle' => 'Designación '.$numero,
            'r_fecha' => '2026-09-'.str_pad((string) $numero, 2, '0', STR_PAD_LEFT),
            'r_id_gestion' => 2026,
            'r_id_periodo' => $numero % 2 + 1,
            'r_obs' => null,
            'r_estado' => 'SOLICITADO',
        ])->all();

        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->once()->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('select')->once()->withArgs(function (string $sql, array $bindings): bool {
            return substr_count($sql, 'designaciones.f_asignaciones(?, ?, ?)') === 2
                && str_contains($sql, 'ORDER BY r_fecha DESC NULLS LAST, r_id DESC')
                && ! str_contains($sql, 'LIMIT')
                && ! str_contains($sql, 'OFFSET')
                && $bindings === ['UATF', '2026', '1', 'UATF', '2026', '2'];
        })->andReturn($filas);

        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->once()->with('pgsql')->andReturn($connection);

        $resultado = (new JachasunDesignacionesService($database))->listarUniversidad('2026');

        $this->assertCount(12, $resultado);
        $this->assertSame(112, $resultado->last()['id']);
        $this->assertSame('Designación 12', $resultado->last()['detalle']);
    }
}
