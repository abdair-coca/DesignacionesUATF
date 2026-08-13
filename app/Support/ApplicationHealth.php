<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ApplicationHealth
{
    /**
     * @return array{status: string, checks: array<string, array{status: string, required: bool}>}
     */
    public function check(): array
    {
        $checks = [
            'app_key' => $this->checkAppKey(),
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkWritableDirectory(storage_path()),
            'bootstrap_cache' => $this->checkWritableDirectory(base_path('bootstrap/cache')),
        ];

        $failedRequired = collect($checks)->contains(
            fn (array $check): bool => $check['required'] && $check['status'] !== 'ok',
        );

        return [
            'status' => $failedRequired ? 'degraded' : 'ok',
            'checks' => $checks,
        ];
    }

    /**
     * @return array{status: string, required: bool}
     */
    private function checkAppKey(): array
    {
        return [
            'status' => filled(config('app.key')) ? 'ok' : 'failed',
            'required' => true,
        ];
    }

    /**
     * @return array{status: string, required: bool}
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->select('select 1');

            return ['status' => 'ok', 'required' => true];
        } catch (Throwable) {
            return ['status' => 'failed', 'required' => true];
        }
    }

    /**
     * @return array{status: string, required: bool}
     */
    private function checkCache(): array
    {
        $key = 'application-health-'.Str::uuid()->toString();

        try {
            Cache::put($key, 'ok', now()->addMinute());
            $healthy = Cache::get($key) === 'ok';
            Cache::forget($key);

            return ['status' => $healthy ? 'ok' : 'failed', 'required' => true];
        } catch (Throwable) {
            return ['status' => 'failed', 'required' => true];
        }
    }

    /**
     * @return array{status: string, required: bool}
     */
    private function checkWritableDirectory(string $path): array
    {
        return [
            'status' => is_dir($path) && is_writable($path) ? 'ok' : 'failed',
            'required' => true,
        ];
    }
}
