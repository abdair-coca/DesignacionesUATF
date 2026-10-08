<?php

use App\Http\Controllers\DesignacionController;
use App\Http\Controllers\FrontendAssetController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\VicerrectoradoDesignacionController;
use Illuminate\Support\Facades\Route;

Route::get('health', HealthController::class)->name('health');

Route::get('resources/assets/{path}', FrontendAssetController::class)
    ->where('path', '.*')
    ->name('resources.assets');

Route::middleware('auth')->group(function () {
    // Lleva directo a designaciones sin importar el rol
    Route::get('/', function () {
        return redirect()->route(auth()->user()?->esVicerrectorado()
            ? 'vicerrectorado.designaciones.index'
            : 'designaciones.index');
    });

    Route::prefix('vicerrectorado')
        ->name('vicerrectorado.')
        ->middleware('rol:vicerrectorado')
        ->group(function () {
            Route::get('designaciones', [VicerrectoradoDesignacionController::class, 'index'])
                ->name('designaciones.index');
            Route::post('designaciones/{id}/decisiones', [VicerrectoradoDesignacionController::class, 'guardarDecisiones'])
                ->whereNumber('id')
                ->name('designaciones.decisiones');
            Route::post('designaciones/{id}/estado', [VicerrectoradoDesignacionController::class, 'guardarRevision'])
                ->whereNumber('id')
                ->name('designaciones.estado');
            Route::get('designaciones/{id}/pdf', [VicerrectoradoDesignacionController::class, 'pdf'])
                ->whereNumber('id')
                ->name('designaciones.pdf');
            Route::get('designaciones/{id}', [VicerrectoradoDesignacionController::class, 'show'])
                ->whereNumber('id')
                ->name('designaciones.show');
        });

    // Rutas de asignaciones SIN la restricción de rol
    Route::get('designaciones', [DesignacionController::class, 'index'])->name('designaciones.index');
    Route::get('designaciones/{id}/pdf', [DesignacionController::class, 'pdf'])
        ->whereNumber('id')
        ->name('designaciones.pdf');
    Route::get('designaciones/docentes/buscar', [DesignacionController::class, 'buscarDocentes'])
        ->name('designaciones.docentes.buscar');
    Route::get('designaciones/{id}', [DesignacionController::class, 'show'])
        ->whereNumber('id')
        ->name('designaciones.show');

    Route::post('designaciones', [DesignacionController::class, 'store'])
        ->name('designaciones.store');
    Route::post('designaciones/{id}', [DesignacionController::class, 'update'])
        ->whereNumber('id')
        ->name('designaciones.update');
    Route::post('designaciones/{id}/detalle', [DesignacionController::class, 'actualizarDetalle'])
        ->whereNumber('id')
        ->name('designaciones.actualizar_detalle');

    Route::get('notificaciones', [NotificacionController::class, 'index'])->name('notificaciones.index');
    Route::post('notificaciones/leer-todas', [NotificacionController::class, 'marcarTodasLeidas'])->name('notificaciones.leer_todas');
    Route::post('notificaciones/{notificacion}/leer', [NotificacionController::class, 'marcarLeida'])->name('notificaciones.leer');
});

require __DIR__.'/auth.php';
