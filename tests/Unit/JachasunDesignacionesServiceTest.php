<?php

namespace Tests\Unit;

use App\Services\Jachasun\JachasunDesignacionesService;
use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use InvalidArgumentException;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class JachasunDesignacionesServiceTest extends TestCase
{
    protected $connectionsToTransact = [];

    public function test_oferta_materias_incluye_unicamente_el_siguiente_grupo(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->once()->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('select')->once()->andReturn([
            (object) [
                'r_id_materia' => 11233,
                'r_sigla' => 'INF101',
                'r_materia' => 'Algoritmos',
                'r_nivel_academico' => 1,
                'r_id_mencion' => null,
                'hrs_teoricas' => 3,
                'hrs_practicas' => 3,
                'hrs_laboratorio' => 0,
                'id_grupo' => 1,
            ],
            (object) [
                'r_id_materia' => 11233,
                'r_sigla' => 'INF101',
                'r_materia' => 'Algoritmos',
                'r_nivel_academico' => 1,
                'r_id_mencion' => null,
                'hrs_teoricas' => 3,
                'hrs_practicas' => 3,
                'hrs_laboratorio' => 0,
                'id_grupo' => 3,
            ],
        ]);
        $connection->shouldReceive('select')->once()->with(
            'SELECT materia_id, ultimo_grupo FROM v_designacion_grupos_abiertos WHERE gestion = ?',
            [2023],
        )->andReturn([]);

        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->once()->with('pgsql')->andReturn($connection);

        $materia = (new JachasunDesignacionesService($database))
            ->ofertaMaterias('INF', '2023', '1')
            ->first();

        $this->assertSame([1, 2, 3, 4], $materia['grupos']);
    }

    public function test_normaliza_filas_de_lista_en_una_consulta_de_solo_lectura(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->once()->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('select')->once()->with(
            'SELECT * FROM designaciones.f_asignaciones(?, ?, ?)',
            ['INF', '2023', '1'],
        )->andReturn([(object) [
            'r_id' => 2117,
            'r_id_programa' => 'INF',
            'r_programa' => 'INGENIERIA INFORMATICA',
            'r_detalle' => 'SEMESTRAL 1/2023',
            'r_fecha' => '2024-10-23 15:07:01.38683',
            'r_id_gestion' => 2023,
            'r_id_periodo' => 1,
            'r_obs' => 'MIGRADO',
            'r_estado' => 'SOLICITADO',
        ]]);
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->once()->with('pgsql')->andReturn($connection);

        $rows = (new JachasunDesignacionesService($database))->listar('INF', '2023', '1');

        $this->assertSame(2117, $rows->first()['id']);
        $this->assertSame('INF', $rows->first()['programa_codigo']);
        $this->assertSame('SEMESTRAL 1/2023', $rows->first()['detalle']);
    }

    public function test_lista_docentes_usa_el_programa_y_normaliza_el_catalogo(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->once()->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('select')->once()->with(
            'SELECT * FROM academico.f_lista_docentes(?)',
            ['INF'],
        )->andReturn([(object) [
            'r_id_docente' => 974,
            'r_ci' => '5107607',
            'r_paterno' => 'Lovelace',
            'r_materno' => null,
            'r_nombres' => 'Ada',
            'r_cargo' => 'DOCENTE',
            'r_direccion' => null,
            'r_id_programa' => 'INF',
            'r_programa' => 'INGENIERIA INFORMATICA',
        ]]);
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->once()->with('pgsql')->andReturn($connection);

        $rows = (new JachasunDesignacionesService($database))->listarDocentes('inf');

        $this->assertSame([
            'id' => 974,
            'nombre' => 'Lovelace Ada',
            'ci' => '5107607',
            'programa_codigo' => 'INF',
            'programa_nombre' => 'INGENIERIA INFORMATICA',
        ], $rows->first());
    }

    public function test_busqueda_local_con_termino_vacio_no_consulta_la_base(): void
    {
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldNotReceive('connection');

        $rows = (new JachasunDesignacionesService($database))->buscarDocentes(' : () ');

        $this->assertTrue($rows->isEmpty());
    }

    public function test_busqueda_local_usa_el_catalogo_academico_si_no_existe_la_tabla_publica(): void
    {
        $connection = Mockery::mock(Connection::class);
        $schema = Mockery::mock(SchemaBuilder::class);
        $query = Mockery::mock(QueryBuilder::class);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->once()->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('getSchemaBuilder')->once()->andReturn($schema);
        $schema->shouldReceive('hasTable')->once()->with('docentes')->andReturnFalse();
        $schema->shouldReceive('hasTable')->once()->with('academico.docentes')->andReturnTrue();
        $connection->shouldReceive('table')->once()->with('academico.docentes')->andReturn($query);
        $query->shouldReceive('selectRaw')->once()->with(
            "id_docente AS id, concat_ws(' ', paterno, materno, nombres) AS nombre, ci",
        )->andReturnSelf();
        $query->shouldReceive('orderBy')->once()->with('nombres')->andReturnSelf();
        $query->shouldReceive('orderBy')->once()->with('id_docente')->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn(collect([(object) [
            'id' => 974,
            'nombre' => 'Lovelace Ada',
            'ci' => '5107607',
        ]]));
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->once()->with('pgsql')->andReturn($connection);

        $rows = (new JachasunDesignacionesService($database))->buscarDocentes('Ada');

        $this->assertSame([
            'id' => 974,
            'nombre' => 'Lovelace Ada',
            'ci' => '5107607',
        ], $rows->first());
    }

    public function test_listar_paginado_envuelve_la_funcion_con_orden_estable_y_total(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->once()->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('select')->once()->with(
            'SELECT * FROM (
    SELECT *, COUNT(*) OVER() AS total_filas
    FROM designaciones.f_asignaciones(?, ?, ?)
) AS asignaciones
ORDER BY r_id DESC
LIMIT ? OFFSET ?',
            ['INF', '2023', '1', 10, 10],
        )->andReturn([(object) [
            'r_id' => 2117,
            'r_id_programa' => 'INF',
            'r_programa' => 'INGENIERIA INFORMATICA',
            'r_detalle' => 'SEMESTRAL 1/2023',
            'r_fecha' => '2024-10-23 15:07:01.38683',
            'r_id_gestion' => 2023,
            'r_id_periodo' => 1,
            'r_obs' => 'MIGRADO',
            'r_estado' => 'SOLICITADO',
            'total_filas' => 25,
        ]]);
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->once()->with('pgsql')->andReturn($connection);

        $resultado = (new JachasunDesignacionesService($database))->listarPaginado('INF', '2023', '1', 2, 10);

        $this->assertSame(25, $resultado['total']);
        $this->assertCount(1, $resultado['items']);
        $this->assertSame(2117, $resultado['items']->first()['id']);
        $this->assertArrayNotHasKey('total_filas', $resultado['items']->first());
    }

    public function test_listar_paginado_sin_filas_devuelve_total_cero(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->once()->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('select')->once()->andReturn([]);
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->once()->with('pgsql')->andReturn($connection);

        $resultado = (new JachasunDesignacionesService($database))->listarPaginado('INF', '0', '0', 1, 10);

        $this->assertSame(0, $resultado['total']);
        $this->assertTrue($resultado['items']->isEmpty());
    }

    public function test_listar_paginado_filtra_por_descripcion(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->once()->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('select')->once()->with(
            'SELECT * FROM (
    SELECT *, COUNT(*) OVER() AS total_filas
    FROM designaciones.f_asignaciones(?, ?, ?)
) AS asignaciones
WHERE asignaciones.r_detalle ILIKE ?
ORDER BY r_id DESC
LIMIT ? OFFSET ?',
            ['INF', '0', '0', '%SEMESTRAL%', 10, 0],
        )->andReturn([(object) [
            'r_id' => 2117,
            'r_id_programa' => 'INF',
            'r_programa' => 'INGENIERIA INFORMATICA',
            'r_detalle' => 'SEMESTRAL 1/2023',
            'r_fecha' => '2024-10-23',
            'r_id_gestion' => 2023,
            'r_id_periodo' => 1,
            'r_obs' => null,
            'r_estado' => 'SOLICITADO',
            'total_filas' => 1,
        ]]);
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->once()->with('pgsql')->andReturn($connection);

        $resultado = (new JachasunDesignacionesService($database))->listarPaginado('INF', '0', '0', 1, 10, 'SEMESTRAL');

        $this->assertSame(1, $resultado['total']);
        $this->assertSame('SEMESTRAL 1/2023', $resultado['items']->first()['detalle']);
    }

    public function test_normaliza_detalle_de_la_funcion_en_una_consulta_de_solo_lectura(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->once()->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('select')->once()->with(
            'SELECT * FROM designaciones.f_asignaciones_detalles(?)',
            [2117],
        )->andReturn([(object) [
            'r_id' => 22325,
            'r_id_docente' => 974,
            'r_ci' => '5107607',
            'r_nombres' => 'Lic. ASTETE ARROYO, MIGUEL ANGEL',
            'r_id_materia' => 11233,
            'r_sigla' => 'INF122',
            'r_materia' => 'FUNDAMENTOS DE TECNOLOGIAS DE INFORMACION',
            'r_id_grupo' => 2,
            'r_hrs_teoricas' => 3,
            'r_hrs_practicas' => 3,
            'r_hrs_laboratorio' => 0,
        ]]);
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->once()->with('pgsql')->andReturn($connection);

        $rows = (new JachasunDesignacionesService($database))->detallar(2117);

        $this->assertSame([
            'id' => 22325,
            'docente_id' => 974,
            'ci' => '5107607',
            'docente_nombre' => 'Lic. ASTETE ARROYO, MIGUEL ANGEL',
            'materia_id' => 11233,
            'materia_sigla' => 'INF122',
            'materia_nombre' => 'FUNDAMENTOS DE TECNOLOGIAS DE INFORMACION',
            'grupo_id' => 2,
            'horas_teoricas' => 3,
            'horas_practicas' => 3,
            'horas_laboratorio' => 0,
        ], $rows->first());
    }

    public function test_copiar_verifica_la_carrera_y_escribe_en_transaccion_normal(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->twice()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->once()->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('select')->once()->with(
            'SELECT * FROM designaciones.f_asignaciones(?, ?, ?)',
            ['INF', '0', '0'],
        )->andReturn([(object) [
            'r_id' => 2117,
            'r_id_programa' => 'INF',
            'r_programa' => 'INGENIERIA INFORMATICA',
            'r_detalle' => 'SEMESTRAL 1/2023',
            'r_fecha' => null,
            'r_id_gestion' => 2023,
            'r_id_periodo' => 1,
            'r_obs' => null,
            'r_estado' => 'SOLICITADO',
        ]]);
        $connection->shouldReceive('select')->once()->with(
            'SELECT * FROM designaciones.f_copiar_designacion(?, ?, ?, ?)',
            [2117, 2026, 1, 'NUEVA GESTION'],
        )->andReturn([(object) ['f_copiar_designacion' => 'Copia realizada correctamente']]);
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->twice()->with('pgsql')->andReturn($connection);

        $service = new JachasunDesignacionesService($database);
        $service->copiar('INF', 2117, 2026, 1, 'NUEVA GESTION');

        $this->assertTrue(true);
    }

    public function test_copiar_rechaza_una_designacion_de_otra_carrera(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->once()->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('select')->once()->with(
            'SELECT * FROM designaciones.f_asignaciones(?, ?, ?)',
            ['INF', '0', '0'],
        )->andReturn([]);
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->once()->with('pgsql')->andReturn($connection);

        $this->expectException(InvalidArgumentException::class);

        (new JachasunDesignacionesService($database))->copiar('INF', 2117, 2026, 1, 'X');
    }

    public function test_insertar_llama_a_la_funcion_con_estado_ins_y_normaliza_la_fila(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('select')->once()->with(
            "SELECT * FROM designaciones.f_designacion(0, ?, ?, ?, ?, ?, 'INS')",
            ['2026-08-31 12:00', 'INF', 2026, 1, 'DESIGNACION'],
        )->andReturn([(object) [
            'fecha' => '2026-08-31 12:00:00',
            'id_programa' => 'INF',
            'programa' => 'INGENIERIA INFORMATICA',
            'id_gestion' => 2026,
            'id_periodo' => 1,
            'obs' => 'DESIGNACION',
        ]]);
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->once()->with('pgsql')->andReturn($connection);

        $fila = (new JachasunDesignacionesService($database))->insertar('INF', '2026-08-31 12:00', 2026, 1, 'DESIGNACION');

        $this->assertSame('2026-08-31 12:00:00', $fila['fecha']);
        $this->assertSame('INF', $fila['programa_codigo']);
        $this->assertSame('INGENIERIA INFORMATICA', $fila['programa_nombre']);
        $this->assertSame('2026', $fila['gestion']);
        $this->assertSame('1', $fila['periodo']);
        $this->assertSame('DESIGNACION', $fila['observacion']);
    }

    public function test_actualizar_verifica_la_carrera_y_escribe_con_estado_upd(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->twice()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->once()->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('select')->once()->with(
            'SELECT * FROM designaciones.f_asignaciones(?, ?, ?)',
            ['INF', '0', '0'],
        )->andReturn([(object) [
            'r_id' => 10,
            'r_id_programa' => 'INF',
            'r_programa' => 'INGENIERIA INFORMATICA',
            'r_detalle' => 'SEMESTRAL 1/2026',
            'r_fecha' => null,
            'r_id_gestion' => 2026,
            'r_id_periodo' => 1,
            'r_obs' => null,
            'r_estado' => 'SOLICITADO',
        ]]);
        $connection->shouldReceive('select')->once()->with(
            "SELECT * FROM designaciones.f_designacion(?, ?, ?, ?, ?, ?, 'UPD')",
            [10, '2026-08-31 12:00', 'INF', 2026, 1, 'DESIGNACION'],
        )->andReturn([(object) [
            'fecha' => '2026-08-31 12:00:00',
            'id_programa' => 'INF',
            'programa' => 'INGENIERIA INFORMATICA',
            'id_gestion' => 2026,
            'id_periodo' => 1,
            'obs' => 'DESIGNACION',
        ]]);
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->twice()->with('pgsql')->andReturn($connection);

        $fila = (new JachasunDesignacionesService($database))->actualizar(10, 'INF', '2026-08-31 12:00', 2026, 1, 'DESIGNACION');

        $this->assertSame('2026', $fila['gestion']);
        $this->assertSame('DESIGNACION', $fila['observacion']);
    }

    public function test_insertar_con_fecha_vacia_delega_el_fallback_a_la_bd(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('select')->once()->with(
            "SELECT * FROM designaciones.f_designacion(0, ?, ?, ?, ?, ?, 'INS')",
            ['', 'INF', 2026, 1, 'DESIGNACION'],
        )->andReturn([(object) [
            'fecha' => '2026-09-03 10:00:00',
            'id_programa' => 'INF',
            'programa' => 'INGENIERIA INFORMATICA',
            'id_gestion' => 2026,
            'id_periodo' => 1,
            'obs' => 'DESIGNACION',
        ]]);
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->once()->with('pgsql')->andReturn($connection);

        $fila = (new JachasunDesignacionesService($database))->insertar('INF', '', 2026, 1, 'DESIGNACION');

        $this->assertSame('2026-09-03 10:00:00', $fila['fecha']);
        $this->assertSame('DESIGNACION', $fila['observacion']);
    }

    public function test_actualizar_con_fecha_vacia_delega_el_fallback_a_la_bd(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->twice()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->once()->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('select')->once()->with(
            'SELECT * FROM designaciones.f_asignaciones(?, ?, ?)',
            ['INF', '0', '0'],
        )->andReturn([(object) [
            'r_id' => 10,
            'r_id_programa' => 'INF',
            'r_programa' => 'INGENIERIA INFORMATICA',
            'r_detalle' => 'SEMESTRAL 1/2026',
            'r_fecha' => null,
            'r_id_gestion' => 2026,
            'r_id_periodo' => 1,
            'r_obs' => null,
            'r_estado' => 'SOLICITADO',
        ]]);
        $connection->shouldReceive('select')->once()->with(
            "SELECT * FROM designaciones.f_designacion(?, ?, ?, ?, ?, ?, 'UPD')",
            [10, '', 'INF', 2026, 1, 'DESIGNACION'],
        )->andReturn([(object) [
            'fecha' => '2026-09-03 10:00:00',
            'id_programa' => 'INF',
            'programa' => 'INGENIERIA INFORMATICA',
            'id_gestion' => 2026,
            'id_periodo' => 1,
            'obs' => 'DESIGNACION',
        ]]);
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->twice()->with('pgsql')->andReturn($connection);

        $fila = (new JachasunDesignacionesService($database))->actualizar(10, 'INF', '', 2026, 1, 'DESIGNACION');

        $this->assertSame('2026-09-03 10:00:00', $fila['fecha']);
        $this->assertSame('DESIGNACION', $fila['observacion']);
    }

    public function test_actualizar_rechaza_una_fecha_con_formato_invalido(): void
    {
        $database = Mockery::mock(DatabaseManager::class);

        $this->expectException(InvalidArgumentException::class);

        (new JachasunDesignacionesService($database))->actualizar(10, 'INF', 'no-es-fecha', 2026, 1, 'X');
    }

    public function test_obtener_devuelve_la_fila_por_id(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->once()->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('select')->once()->with(
            "SELECT * FROM designaciones.f_designacion(?, NULL, '', 0, 0, NULL, '')",
            [10],
        )->andReturn([(object) [
            'fecha' => null,
            'id_programa' => 'INF',
            'programa' => 'INGENIERIA INFORMATICA',
            'id_gestion' => 2026,
            'id_periodo' => 1,
            'obs' => 'OBS',
        ]]);
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->once()->with('pgsql')->andReturn($connection);

        $fila = (new JachasunDesignacionesService($database))->obtener(10);

        $this->assertSame('INF', $fila['programa_codigo']);
        $this->assertSame('2026', $fila['gestion']);
    }

    public function test_obtener_devuelve_null_cuando_no_existe_la_designacion(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->once()->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('select')->once()->with(
            "SELECT * FROM designaciones.f_designacion(?, NULL, '', 0, 0, NULL, '')",
            [10],
        )->andReturn([]);
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->once()->with('pgsql')->andReturn($connection);

        $this->assertNull((new JachasunDesignacionesService($database))->obtener(10));
    }

    public function test_guardar_detalle_verifica_la_carrera_y_escribe_en_transaccion_normal(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->times(4)->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->times(3)->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('select')->once()->with(
            'SELECT * FROM designaciones.f_asignaciones(?, ?, ?)',
            ['INF', '0', '0'],
        )->andReturn([(object) [
            'r_id' => 2117,
            'r_id_programa' => 'INF',
            'r_programa' => 'INGENIERIA INFORMATICA',
            'r_detalle' => 'SEMESTRAL 1/2026',
            'r_fecha' => null,
            'r_id_gestion' => 2026,
            'r_id_periodo' => 1,
            'r_obs' => null,
            'r_estado' => 'SOLICITADO',
        ]]);
        $connection->shouldReceive('select')->once()->with(
            'SELECT * FROM designaciones.f_asignaciones_detalles(?)',
            [2117],
        )->andReturn([(object) [
            'r_id' => 80356,
            'r_id_docente' => 974,
            'r_ci' => '5107607',
            'r_nombres' => 'Lic. ASTETE ARROYO, MIGUEL ANGEL',
            'r_id_materia' => 11233,
            'r_sigla' => 'INF122',
            'r_materia' => 'FUNDAMENTOS DE TECNOLOGIAS DE INFORMACION',
            'r_id_grupo' => 2,
            'r_hrs_teoricas' => 3,
            'r_hrs_practicas' => 3,
            'r_hrs_laboratorio' => 0,
        ]]);
        $connection->shouldReceive('select')->once()->with(
            Mockery::on(fn (string $sql): bool => str_contains($sql, 'academico.f_oferta_materias')),
            ['INF', 2026, 1],
        )->andReturn([$this->filaOferta(11233, 2)]);
        $connection->shouldReceive('select')->once()->with(
            'SELECT * FROM designaciones.f_designacion_detalle(?, ?, ?, ?, ?, ?, ?, ?)',
            [2117, 80356, 974, 11233, 2, 3, 3, 0],
        )->andReturn([(object) [
            'r_id' => 22325,
            'r_id_docente' => 974,
            'r_ci' => '5107607',
            'r_nombres' => 'Lic. ASTETE ARROYO, MIGUEL ANGEL',
            'r_id_materia' => 11233,
            'r_sigla' => 'INF122',
            'r_materia' => 'FUNDAMENTOS DE TECNOLOGIAS DE INFORMACION',
            'r_id_grupo' => 2,
            'r_hrs_teoricas' => 3,
            'r_hrs_practicas' => 3,
            'r_hrs_laboratorio' => 0,
        ]]);
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->times(4)->with('pgsql')->andReturn($connection);

        $filas = (new JachasunDesignacionesService($database))->guardarDetalle(2117, 'INF', 80356, 974, 11233, 2, 3, 3, 0);

        $this->assertSame([
            'id' => 22325,
            'docente_id' => 974,
            'ci' => '5107607',
            'docente_nombre' => 'Lic. ASTETE ARROYO, MIGUEL ANGEL',
            'materia_id' => 11233,
            'materia_sigla' => 'INF122',
            'materia_nombre' => 'FUNDAMENTOS DE TECNOLOGIAS DE INFORMACION',
            'grupo_id' => 2,
            'horas_teoricas' => 3,
            'horas_practicas' => 3,
            'horas_laboratorio' => 0,
        ], $filas->first());
    }

    public function test_guardar_detalle_continua_si_no_existe_la_tabla_local_de_aperturas(): void
    {
        $connection = Mockery::mock(Connection::class);
        $schema = Mockery::mock(SchemaBuilder::class);
        $connection->shouldReceive('transaction')->times(4)->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->times(3)->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('getSchemaBuilder')->once()->andReturn($schema);
        $schema->shouldReceive('hasTable')->once()->with('designacion_grupo_aperturas')->andReturnFalse();
        $connection->shouldReceive('select')->once()->with(
            'SELECT * FROM designaciones.f_asignaciones(?, ?, ?)',
            ['INF', '0', '0'],
        )->andReturn([(object) [
            'r_id' => 2117,
            'r_id_programa' => 'INF',
            'r_programa' => 'INGENIERIA INFORMATICA',
            'r_detalle' => 'SEMESTRAL 1/2026',
            'r_fecha' => null,
            'r_id_gestion' => 2026,
            'r_id_periodo' => 1,
            'r_obs' => null,
            'r_estado' => 'SOLICITADO',
        ]]);
        $connection->shouldReceive('select')->once()->with(
            'SELECT * FROM designaciones.f_asignaciones_detalles(?)',
            [2117],
        )->andReturn([]);
        $connection->shouldReceive('select')->once()->with(
            Mockery::on(fn (string $sql): bool => str_contains($sql, 'academico.f_oferta_materias')),
            ['INF', 2026, 1],
        )->andReturn([$this->filaOferta(11233, 1)]);
        $connection->shouldReceive('select')->once()->with(
            'SELECT * FROM designaciones.f_designacion_detalle(?, ?, ?, ?, ?, ?, ?, ?)',
            [2117, 0, 974, 11233, 2, 3, 3, 0],
        )->andReturn([(object) [
            'r_id' => 22325,
            'r_id_docente' => 974,
            'r_ci' => '5107607',
            'r_nombres' => 'Lic. ASTETE ARROYO, MIGUEL ANGEL',
            'r_id_materia' => 11233,
            'r_sigla' => 'INF122',
            'r_materia' => 'FUNDAMENTOS DE TECNOLOGIAS DE INFORMACION',
            'r_id_grupo' => 2,
            'r_hrs_teoricas' => 3,
            'r_hrs_practicas' => 3,
            'r_hrs_laboratorio' => 0,
        ]]);
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->times(4)->with('pgsql')->andReturn($connection);

        $filas = (new JachasunDesignacionesService($database))->guardarDetalle(2117, 'INF', 0, 974, 11233, 2, 3, 3, 0);

        $this->assertSame(974, $filas->first()['docente_id']);
    }

    public function test_guardar_detalle_rechaza_todas_las_horas_en_cero(): void
    {
        $database = Mockery::mock(DatabaseManager::class);

        $this->expectException(InvalidArgumentException::class);

        (new JachasunDesignacionesService($database))->guardarDetalle(2117, 'INF', 80356, 974, 11233, 2, 0, 0, 0);
    }

    public function test_guardar_detalle_nuevo_exige_el_siguiente_grupo(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->times(3)->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->times(3)->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('select')->once()->with(
            'SELECT * FROM designaciones.f_asignaciones(?, ?, ?)',
            ['INF', '0', '0'],
        )->andReturn([(object) [
            'r_id' => 2117,
            'r_id_programa' => 'INF',
            'r_programa' => 'INGENIERIA INFORMATICA',
            'r_detalle' => 'SEMESTRAL 1/2026',
            'r_fecha' => null,
            'r_id_gestion' => 2026,
            'r_id_periodo' => 1,
            'r_obs' => null,
            'r_estado' => 'SOLICITADO',
        ]]);
        $connection->shouldReceive('select')->once()->with(
            'SELECT * FROM designaciones.f_asignaciones_detalles(?)',
            [2117],
        )->andReturn([]);
        $connection->shouldReceive('select')->once()->with(
            Mockery::on(fn (string $sql): bool => str_contains($sql, 'academico.f_oferta_materias')),
            ['INF', 2026, 1],
        )->andReturn([$this->filaOferta(11233, 1)]);
        $connection->shouldReceive('select')->once()->with(
            'SELECT materia_id, ultimo_grupo FROM v_designacion_grupos_abiertos WHERE gestion = ?',
            [2026],
        )->andReturn([]);
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->times(3)->with('pgsql')->andReturn($connection);

        $this->expectException(InvalidArgumentException::class);

        (new JachasunDesignacionesService($database))->guardarDetalle(2117, 'INF', 0, 974, 11233, 1, 3, 0, 0);
    }

    public function test_guardar_detalle_cambio_de_materia_conserva_horas_modificadas_validas(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->times(4)->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->times(3)->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('select')->once()->with(
            'SELECT * FROM designaciones.f_asignaciones(?, ?, ?)',
            ['INF', '0', '0'],
        )->andReturn([(object) [
            'r_id' => 2117,
            'r_id_programa' => 'INF',
            'r_programa' => 'INGENIERIA INFORMATICA',
            'r_detalle' => 'SEMESTRAL 1/2026',
            'r_fecha' => null,
            'r_id_gestion' => 2026,
            'r_id_periodo' => 1,
            'r_obs' => null,
            'r_estado' => 'SOLICITADO',
        ]]);
        $connection->shouldReceive('select')->once()->with(
            'SELECT * FROM designaciones.f_asignaciones_detalles(?)',
            [2117],
        )->andReturn([(object) [
            'r_id' => 80356,
            'r_id_docente' => 974,
            'r_ci' => '5107607',
            'r_nombres' => 'DOCENTE',
            'r_id_materia' => 11233,
            'r_sigla' => 'INF101',
            'r_materia' => 'MATERIA ANTERIOR',
            'r_id_grupo' => 2,
            'r_hrs_teoricas' => 3,
            'r_hrs_practicas' => 3,
            'r_hrs_laboratorio' => 0,
        ]]);
        $connection->shouldReceive('select')->once()->with(
            Mockery::on(fn (string $sql): bool => str_contains($sql, 'academico.f_oferta_materias')),
            ['INF', 2026, 1],
        )->andReturn([$this->filaOferta(11234, 1, 2, 1, 1)]);
        $connection->shouldReceive('select')->once()->with(
            'SELECT * FROM designaciones.f_designacion_detalle(?, ?, ?, ?, ?, ?, ?, ?)',
            [2117, 80356, 974, 11234, 1, 1, 1, 1],
        )->andReturn([]);
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->times(4)->with('pgsql')->andReturn($connection);

        (new JachasunDesignacionesService($database))->guardarDetalle(2117, 'INF', 80356, 974, 11234, 1, 1, 1, 1);

        $this->assertTrue(true);
    }

    public function test_guardar_detalle_rechaza_materia_y_grupo_ya_asignados(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->times(3)->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->times(3)->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('select')->once()->with(
            'SELECT * FROM designaciones.f_asignaciones(?, ?, ?)',
            ['INF', '0', '0'],
        )->andReturn([(object) [
            'r_id' => 2117,
            'r_id_programa' => 'INF',
            'r_programa' => 'INGENIERIA INFORMATICA',
            'r_detalle' => 'SEMESTRAL 1/2026',
            'r_fecha' => null,
            'r_id_gestion' => 2026,
            'r_id_periodo' => 1,
            'r_obs' => null,
            'r_estado' => 'SOLICITADO',
        ]]);
        $connection->shouldReceive('select')->once()->with(
            'SELECT * FROM designaciones.f_asignaciones_detalles(?)',
            [2117],
        )->andReturn([
            (object) [
                'r_id' => 80356,
                'r_id_docente' => 974,
                'r_ci' => null,
                'r_nombres' => null,
                'r_id_materia' => 11233,
                'r_sigla' => 'INF101',
                'r_materia' => 'MATERIA',
                'r_id_grupo' => 2,
                'r_hrs_teoricas' => 3,
                'r_hrs_practicas' => 3,
                'r_hrs_laboratorio' => 0,
            ],
            (object) [
                'r_id' => 80357,
                'r_id_docente' => 975,
                'r_ci' => null,
                'r_nombres' => null,
                'r_id_materia' => 11234,
                'r_sigla' => 'INF102',
                'r_materia' => 'MATERIA DUPLICADA',
                'r_id_grupo' => 1,
                'r_hrs_teoricas' => 2,
                'r_hrs_practicas' => 1,
                'r_hrs_laboratorio' => 0,
            ],
        ]);
        $connection->shouldReceive('select')->once()->with(
            Mockery::on(fn (string $sql): bool => str_contains($sql, 'academico.f_oferta_materias')),
            ['INF', 2026, 1],
        )->andReturn([$this->filaOferta(11234, 1, 2, 1, 0)]);
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->times(3)->with('pgsql')->andReturn($connection);

        $this->expectException(InvalidArgumentException::class);

        (new JachasunDesignacionesService($database))->guardarDetalle(2117, 'INF', 80356, 974, 11234, 1, 2, 1, 0);
    }

    public function test_guardar_detalle_rechaza_una_designacion_de_otra_carrera(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->once()->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('select')->once()->with(
            'SELECT * FROM designaciones.f_asignaciones(?, ?, ?)',
            ['INF', '0', '0'],
        )->andReturn([]);
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->once()->with('pgsql')->andReturn($connection);

        $this->expectException(InvalidArgumentException::class);

        (new JachasunDesignacionesService($database))->guardarDetalle(2117, 'INF', 80356, 974, 11233, 2, 3, 3, 0);
    }

    public function test_guardar_detalle_rechaza_parametros_invalidos(): void
    {
        $database = Mockery::mock(DatabaseManager::class);

        $this->expectException(InvalidArgumentException::class);

        (new JachasunDesignacionesService($database))->guardarDetalle(2117, 'INF', 80356, 0, 11233, 2, 3, 3, 0);
    }

    public function test_oferta_materias_normaliza_grupos_y_horas_oficiales(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->once()->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('select')->once()->with(
            'SELECT oferta.r_id_programa,
    oferta.r_id_gestion,
    oferta.r_id_periodo,
    oferta.r_id_materia,
    oferta.r_sigla,
    oferta.r_materia,
    oferta.r_nivel_academico,
    oferta.r_id_mencion,
    pm.hrs_teoricas,
    pm.hrs_practicas,
    pm.hrs_laboratorio,
    dct.id_grupo
FROM academico.f_oferta_materias(?, ?, ?) AS oferta
INNER JOIN academico.pln_materias AS pm
        ON pm.id_materia = oferta.r_id_materia
LEFT JOIN academico.dct_asignaciones AS dct
       ON dct.id_programa = oferta.r_id_programa
      AND dct.id_gestion = oferta.r_id_gestion
      AND dct.id_periodo = oferta.r_id_periodo
      AND dct.id_materia = oferta.r_id_materia
ORDER BY oferta.r_nivel_academico, oferta.r_sigla, dct.id_grupo',
            ['INF', 2026, 1],
        )->andReturn([
            (object) [
                'r_id_programa' => 'INF',
                'r_id_gestion' => 2026,
                'r_id_periodo' => 1,
                'r_id_materia' => 11233,
                'r_sigla' => 'INF101',
                'r_materia' => 'ALGORITMOS',
                'r_nivel_academico' => 1,
                'r_id_mencion' => null,
                'hrs_teoricas' => 3,
                'hrs_practicas' => 3,
                'hrs_laboratorio' => 0,
                'id_grupo' => 1,
            ],
            (object) [
                'r_id_programa' => 'INF',
                'r_id_gestion' => 2026,
                'r_id_periodo' => 1,
                'r_id_materia' => 11233,
                'r_sigla' => 'INF101',
                'r_materia' => 'ALGORITMOS',
                'r_nivel_academico' => 1,
                'r_id_mencion' => null,
                'hrs_teoricas' => 3,
                'hrs_practicas' => 3,
                'hrs_laboratorio' => 0,
                'id_grupo' => 2,
            ],
        ]);
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->once()->with('pgsql')->andReturn($connection);

        $materias = (new JachasunDesignacionesService($database))->ofertaMaterias('INF', 2026, 1);

        $this->assertCount(1, $materias);
        $this->assertSame(11233, $materias->first()['id']);
        $this->assertSame([1, 2, 3], $materias->first()['grupos']);
        $this->assertSame(3, $materias->first()['horas_teoricas']);
        $this->assertSame(3, $materias->first()['horas_practicas']);
        $this->assertSame(0, $materias->first()['horas_laboratorio']);
    }

    public function test_oferta_materias_con_permisos_limitados_conserva_materias_y_horas(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->twice()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->twice()->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('select')->once()->with(
            Mockery::on(fn (string $sql): bool => str_contains($sql, 'academico.dct_asignaciones')),
            ['INF', 2026, 1],
        )->andThrow(new QueryException('pgsql', 'SELECT grupos', [], new RuntimeException('permission denied')));
        $connection->shouldReceive('select')->once()->with(
            Mockery::on(fn (string $sql): bool => str_contains($sql, 'academico.f_oferta_materias')
                && ! str_contains($sql, 'academico.dct_asignaciones')),
            ['INF', 2026, 1],
        )->andReturn([$this->filaOferta(11233, 1)]);
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->twice()->with('pgsql')->andReturn($connection);

        $materias = (new JachasunDesignacionesService($database))->ofertaMaterias('INF', 2026, 1);

        $this->assertCount(1, $materias);
        $this->assertSame([], $materias->first()['grupos']);
        $this->assertFalse($materias->first()['grupos_disponibles']);
        $this->assertSame(3, $materias->first()['horas_teoricas']);
    }

    public function test_guardar_detalle_sin_grupos_permite_reasignar_docente_y_horas_validas(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->times(5)->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->times(4)->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $this->expectLecturasOfertaSinGrupos($connection, 11233, 2);
        $connection->shouldReceive('select')->once()->with(
            'SELECT * FROM designaciones.f_designacion_detalle(?, ?, ?, ?, ?, ?, ?, ?)',
            [2117, 80356, 975, 11233, 2, 0, 2, 0],
        )->andReturn([]);
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->times(5)->with('pgsql')->andReturn($connection);

        (new JachasunDesignacionesService($database))->guardarDetalle(2117, 'INF', 80356, 975, 11233, 2, 0, 2, 0);

        $this->assertTrue(true);
    }

    public function test_guardar_detalle_rechaza_horas_superiores_a_la_oferta_oficial(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->times(4)->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->times(4)->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $this->expectLecturasOfertaSinGrupos($connection, 11233, 2);
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->times(4)->with('pgsql')->andReturn($connection);

        $this->expectException(InvalidArgumentException::class);

        (new JachasunDesignacionesService($database))->guardarDetalle(2117, 'INF', 80356, 975, 11233, 2, 4, 3, 0);
    }

    public function test_guardar_detalle_sin_grupos_permite_cambio_de_materia_conservando_grupo(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->times(5)->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->times(4)->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $this->expectLecturasOfertaSinGrupos($connection, 11233, 2, 11234);
        $connection->shouldReceive('select')->once()->with(
            'SELECT * FROM designaciones.f_designacion_detalle(?, ?, ?, ?, ?, ?, ?, ?)',
            [2117, 80356, 975, 11234, 2, 3, 3, 0],
        )->andReturn([]);
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->times(5)->with('pgsql')->andReturn($connection);

        (new JachasunDesignacionesService($database))->guardarDetalle(2117, 'INF', 80356, 975, 11234, 2, 3, 3, 0);

        $this->assertTrue(true);
    }

    public function test_guardar_detalle_sin_grupos_permite_nuevo_con_siguiente_oficial(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->times(5)->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->times(4)->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $schema = Mockery::mock(SchemaBuilder::class);
        $connection->shouldReceive('getSchemaBuilder')->once()->andReturn($schema);
        $schema->shouldReceive('hasTable')->once()->with('designacion_grupo_aperturas')->andReturnTrue();
        $this->expectLecturasOfertaSinGrupos($connection, 11233, 1);
        $connection->shouldReceive('insert')->once()->andReturnTrue();
        $connection->shouldReceive('select')->once()->with(
            'SELECT * FROM designaciones.f_designacion_detalle(?, ?, ?, ?, ?, ?, ?, ?)',
            [2117, 0, 974, 11233, 2, 3, 0, 0],
        )->andReturn([]);
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->times(5)->with('pgsql')->andReturn($connection);

        (new JachasunDesignacionesService($database))->guardarDetalle(2117, 'INF', 0, 974, 11233, 2, 3, 0, 0);

        $this->assertTrue(true);
    }

    public function test_contexto_actual_consulta_la_gestion_del_programa_en_solo_lectura(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->once()->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('selectOne')->once()->with(
            'SELECT gestion, periodo
FROM public.gestion_periodo_directores
WHERE id_programa = ?',
            ['INF'],
        )->andReturn((object) ['gestion' => 2026, 'periodo' => 1]);
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->once()->with('pgsql')->andReturn($connection);

        $contexto = (new JachasunDesignacionesService($database))->contextoActual('INF');

        $this->assertSame(['gestion' => '2026', 'periodo' => '1'], $contexto);
    }

    public function test_contexto_actual_usa_la_ultima_designacion_si_no_hay_permiso_de_contexto(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('transaction')->twice()->andReturnUsing(fn (Closure $callback) => $callback());
        $connection->shouldReceive('statement')->twice()->with('SET TRANSACTION READ ONLY')->andReturnTrue();
        $connection->shouldReceive('selectOne')->once()->with(
            'SELECT gestion, periodo
FROM public.gestion_periodo_directores
WHERE id_programa = ?',
            ['INF'],
        )->andThrow(new QueryException('pgsql', 'SELECT contexto', [], new RuntimeException('permission denied')));
        $connection->shouldReceive('select')->once()->with(
            'SELECT * FROM designaciones.f_asignaciones(?, ?, ?)',
            ['INF', '0', '0'],
        )->andReturn([
            (object) [
                'r_id' => 2117,
                'r_id_programa' => 'INF',
                'r_programa' => 'INGENIERIA INFORMATICA',
                'r_detalle' => 'SEMESTRAL 1/2026',
                'r_fecha' => null,
                'r_id_gestion' => 2026,
                'r_id_periodo' => 1,
                'r_obs' => null,
                'r_estado' => 'SOLICITADO',
            ],
            (object) [
                'r_id' => 2118,
                'r_id_programa' => 'INF',
                'r_programa' => 'INGENIERIA INFORMATICA',
                'r_detalle' => 'SEMESTRAL 2/2026',
                'r_fecha' => null,
                'r_id_gestion' => 2026,
                'r_id_periodo' => 2,
                'r_obs' => null,
                'r_estado' => 'SOLICITADO',
            ],
        ]);
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->twice()->with('pgsql')->andReturn($connection);

        $contexto = (new JachasunDesignacionesService($database))->contextoActual('INF');

        $this->assertSame(['gestion' => '2026', 'periodo' => '2'], $contexto);
    }

    private function filaOferta(int $materia, int $grupo, int $teoria = 3, int $practica = 3, int $laboratorio = 0): object
    {
        return (object) [
            'r_id_programa' => 'INF',
            'r_id_gestion' => 2026,
            'r_id_periodo' => 1,
            'r_id_materia' => $materia,
            'r_sigla' => 'INF101',
            'r_materia' => 'ALGORITMOS',
            'r_nivel_academico' => 1,
            'r_id_mencion' => null,
            'hrs_teoricas' => $teoria,
            'hrs_practicas' => $practica,
            'hrs_laboratorio' => $laboratorio,
            'id_grupo' => $grupo,
        ];
    }

    private function expectLecturasOfertaSinGrupos(Connection $connection, int $materia, int $grupo, ?int $materiaOferta = null): void
    {
        $connection->shouldReceive('select')->once()->with(
            'SELECT * FROM designaciones.f_asignaciones(?, ?, ?)',
            ['INF', '0', '0'],
        )->andReturn([(object) [
            'r_id' => 2117,
            'r_id_programa' => 'INF',
            'r_programa' => 'INGENIERIA INFORMATICA',
            'r_detalle' => 'SEMESTRAL 1/2026',
            'r_fecha' => null,
            'r_id_gestion' => 2026,
            'r_id_periodo' => 1,
            'r_obs' => null,
            'r_estado' => 'SOLICITADO',
        ]]);
        $connection->shouldReceive('select')->once()->with(
            'SELECT * FROM designaciones.f_asignaciones_detalles(?)',
            [2117],
        )->andReturn([(object) [
            'r_id' => 80356,
            'r_id_docente' => 974,
            'r_ci' => '5107607',
            'r_nombres' => 'DOCENTE',
            'r_id_materia' => $materia,
            'r_sigla' => 'INF101',
            'r_materia' => 'MATERIA',
            'r_id_grupo' => $grupo,
            'r_hrs_teoricas' => 3,
            'r_hrs_practicas' => 3,
            'r_hrs_laboratorio' => 0,
        ]]);
        $connection->shouldReceive('select')->once()->with(
            Mockery::on(fn (string $sql): bool => str_contains($sql, 'academico.dct_asignaciones')),
            ['INF', 2026, 1],
        )->andThrow(new QueryException('pgsql', 'SELECT grupos', [], new RuntimeException('permission denied')));
        $connection->shouldReceive('select')->once()->with(
            Mockery::on(fn (string $sql): bool => str_contains($sql, 'academico.f_oferta_materias')
                && ! str_contains($sql, 'academico.dct_asignaciones')),
            ['INF', 2026, 1],
        )->andReturn([$this->filaOferta($materiaOferta ?? $materia, $grupo)]);
    }
}
