<?php

namespace App\Providers;

use App\Auth\Demo\DemoUserProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class DemoAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Auth::provider('demo', function ($app, array $config): DemoUserProvider {
            return new DemoUserProvider(
                $app['hash'],
                config('demo-auth.accounts', []),
                (string) config('demo-auth.password', ''),
            );
        });
    }
}
