<?php

namespace App\Providers;

use App\Models\Designacion;
use App\Observers\DesignacionObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Designacion::observe(DesignacionObserver::class);

        Paginator::useBootstrapFive();
    }
}
