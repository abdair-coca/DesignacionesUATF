<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class TestingPhase0 extends Command
{
    protected $signature = 'testing:phase0';

    protected $description = 'Prepara y verifica el entorno aislado de pruebas';

    public function handle(): int
    {
        try {
            $this->assertSafeEnvironment();
            $this->verifyRuntimeSettings();
            $this->verifyDatabaseLifecycle();
            $this->verifyTestingStorage();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Fase 0 completada: entorno seguro y reproducible.');

        return self::SUCCESS;
    }

    private function assertSafeEnvironment(): void
    {
        if (app()->environment('production') || config('app.env') === 'production') {
            throw new RuntimeException(
                'Bloqueado: las pruebas destructivas no pueden ejecutarse en produccion.'
            );
        }

        $connection = (string) config('database.default');
        $settings = (array) config("database.connections.{$connection}");
        $database = strtolower((string) ($settings['database'] ?? ''));
        $host = strtolower((string) ($settings['host'] ?? ''));

        if ($connection === 'pgsql') {
            if (! in_array($host, ['127.0.0.1', 'localhost', '::1'], true)) {
                throw new RuntimeException('Bloqueado: PostgreSQL de pruebas debe estar en localhost.');
            }

            if (! preg_match('/(?:_testing|_test|_staging)$/', $database)) {
                throw new RuntimeException('Bloqueado: la base no tiene sufijo _testing, _test o _staging.');
            }
        }

        if ($connection === 'sqlite') {
            $path = strtolower((string) ($settings['database'] ?? ''));

            if (! str_contains($path, 'testing') && ! str_contains($path, '_test')) {
                throw new RuntimeException('Bloqueado: SQLite debe estar dentro del almacenamiento de pruebas.');
            }
        }
    }

    private function verifyRuntimeSettings(): void
    {
        $required = [
            'mail' => config('mail.default') === 'array',
            'broadcast' => in_array(config('broadcasting.default'), [null, 'null'], true),
            'queue' => config('queue.default') === 'sync',
            'cache' => config('cache.default') === 'array',
            'filesystem' => config('filesystems.default') === 'local',
            'external_services' => config('services.external_mode') === 'fake',
        ];

        $failed = collect($required)->filter(fn (bool $valid): bool => ! $valid)->keys()->implode(', ');

        if ($failed !== '') {
            throw new RuntimeException("Configuracion insegura para pruebas: {$failed}.");
        }

        $this->line('Efectos externos: correo array, broadcast null, colas sync.');
    }

    private function verifyDatabaseLifecycle(): void
    {
        DB::connection()->getPdo();
        $this->line('Base aislada: '.DB::connection()->getDatabaseName());

        $this->call('migrate:fresh', ['--force' => true]);

        if (! Schema::hasTable('migrations')) {
            throw new RuntimeException('Fallo: migrate:fresh no creo la tabla de migraciones.');
        }

        $attempts = 0;
        while (DB::table('migrations')->count() > 0 && $attempts < 100) {
            $this->call('migrate:rollback', ['--step' => 1, '--force' => true]);
            $attempts++;
        }

        if (DB::table('migrations')->count() !== 0) {
            throw new RuntimeException('Fallo: rollback no dejo la base sin migraciones aplicadas.');
        }

        $this->call('migrate', ['--force' => true]);

        $migrationFiles = glob(database_path('migrations/*.php')) ?: [];
        $appliedMigrations = DB::table('migrations')->count();

        if ($appliedMigrations !== count($migrationFiles)) {
            throw new RuntimeException('Fallo: no se aplicaron todas las migraciones desde cero.');
        }

        $this->line("Migraciones: {$appliedMigrations} aplicadas despues de rollback.");
    }

    private function verifyTestingStorage(): void
    {
        $disk = Storage::disk(config('filesystems.default'));
        $probe = 'phase-0/'.uniqid('', true).'.txt';

        $disk->put($probe, 'testing');

        if (! $disk->exists($probe)) {
            throw new RuntimeException('Fallo: el almacenamiento de pruebas no es escribible.');
        }

        $disk->delete($probe);
        $this->line('Almacenamiento temporal: verificado y limpiado.');
    }
}
