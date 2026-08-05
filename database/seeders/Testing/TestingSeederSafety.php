<?php

namespace Database\Seeders\Testing;

use Closure;
use RuntimeException;

final class TestingSeederSafety
{
    public static function assertSafe(): void
    {
        $environment = (string) app()->environment();
        $connection = (string) config('database.default');
        $database = (array) config("database.connections.{$connection}", []);
        $host = strtolower((string) ($database['host'] ?? ''));
        $name = strtolower((string) ($database['database'] ?? ''));
        $port = (string) ($database['port'] ?? '');

        if ($environment === 'production' || self::containsProductionMarker($environment, $host, $name)) {
            throw new RuntimeException('Testing seeders blocked: production indicator detected. No data changed.');
        }

        $stagingAllowed = filter_var(env('TESTING_SEEDER_ALLOW_STAGING', false), FILTER_VALIDATE_BOOLEAN);
        if (! in_array($environment, ['local', 'testing', 'staging'], true)
            || ($environment === 'staging' && ! $stagingAllowed)) {
            throw new RuntimeException(
                "Testing seeders blocked: APP_ENV={$environment} is not explicitly authorized. No data changed.",
            );
        }

        $loopback = in_array($host, ['127.0.0.1', 'localhost', '::1'], true);
        $safeHost = $loopback || str_ends_with($host, '.test') || str_contains($host, 'staging');
        $safeName = (bool) preg_match('/(^|[_-])(test|testing|local|dev|development|stage|staging)([_-]|$)/', $name);
        $explicitLocal = filter_var(env('TESTING_SEEDER_ALLOW_LOCAL', false), FILTER_VALIDATE_BOOLEAN);

        if (! $safeHost || (! $safeName && ! ($environment === 'local' && $explicitLocal))) {
            throw new RuntimeException(
                "Testing seeders blocked: unsafe database target host={$host}, database={$name}, port={$port}. No data changed.",
            );
        }
    }

    public static function run(string $label, Closure $callback): mixed
    {
        self::assertSafe();

        return $callback();
    }

    private static function containsProductionMarker(string ...$values): bool
    {
        foreach ($values as $value) {
            if (preg_match('/(^|[_\-.])(prod|production|live|real)([_\-.]|$)/', $value)) {
                return true;
            }
        }

        return false;
    }
}
