<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductionDatabaseConfigurationTest extends TestCase
{
    public function test_postgresql_pdo_driver_is_available(): void
    {
        $this->assertContains(
            'pgsql',
            \PDO::getAvailableDrivers(),
            'PHP debe tener habilitado el driver PDO de PostgreSQL para conectar con Jachasun.',
        );
    }

    public function test_pgsql_is_the_canonical_database_connection(): void
    {
        $this->assertSame('pgsql', config('database.default'));
        $this->assertSame('pgsql', config('database.connections.pgsql.driver'));
    }
}
