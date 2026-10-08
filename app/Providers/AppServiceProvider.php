<?php

namespace App\Providers;

use App\Contracts\DesignacionesReadContract;
use App\Contracts\DesignacionesWriteContract;
use App\Services\Jachasun\JachasunDesignacionesReadAdapter;
use App\Services\Jachasun\JachasunDesignacionesWriteAdapter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DesignacionesReadContract::class, JachasunDesignacionesReadAdapter::class);
        $this->app->bind(DesignacionesWriteContract::class, JachasunDesignacionesWriteAdapter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Solo forzar HTTPS si la aplicación declara servir por ese esquema.
        // Si el servidor sirve HTTP (APP_URL=http://...), las URLs generadas
        // usan el esquema real del request; de lo contrario los formularios
        // postearían a https://... y el servidor respondería un 301 a HTTP,
        // convirtiendo el POST en GET y perdiendo el guardado.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
