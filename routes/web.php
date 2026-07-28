<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DesignacionController;
use App\Http\Controllers\DesignacionMasivaController;
use App\Http\Controllers\RevisionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::redirect('/', '/dashboard');
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('designaciones/lista', [DesignacionController::class, 'lista'])
        ->name('designaciones.lista');
    Route::get('designaciones/carrera/{carrera}', [DesignacionController::class, 'carrera'])
        ->name('designaciones.carrera');
    Route::post('designaciones/carrera/{carrera}/guardar', [DesignacionController::class, 'guardarRoster'])
        ->name('designaciones.carrera.guardar');
    Route::post('designaciones/carrera/{carrera}/copiar-anterior', [DesignacionController::class, 'copiarAnterior'])
        ->name('designaciones.carrera.copiar_anterior');
    Route::post('designaciones/pegar', [DesignacionMasivaController::class, 'pegar'])
        ->name('designaciones.pegar');
    Route::post('designaciones/deshacer-pegado', [DesignacionMasivaController::class, 'deshacerPegado'])
        ->name('designaciones.deshacer-pegado');
    Route::post('designaciones/previsualizar-pegado', [DesignacionMasivaController::class, 'previsualizar'])
        ->name('designaciones.previsualizar-pegado');

    Route::resource('designaciones', DesignacionController::class)
        ->except('show')
        ->parameters(['designaciones' => 'designacion']);
    Route::get('designaciones/{designacion}/historial', [DesignacionController::class, 'historial'])
        ->name('designaciones.historial');

    // Revisiones
    Route::post('revisiones/solicitar', [RevisionController::class, 'solicitar'])->name('revisiones.solicitar');
    Route::get('revisiones/pendientes', [RevisionController::class, 'pendientes'])->name('revisiones.pendientes');
    Route::get('revisiones/{revision}/revisar', [RevisionController::class, 'revisar'])->name('revisiones.revisar');
    Route::post('revisiones/{revision}/procesar', [RevisionController::class, 'procesar'])->name('revisiones.procesar');
    Route::post('revisiones/{revision}/completar', [RevisionController::class, 'completar'])->name('revisiones.completar');
});

require __DIR__.'/auth.php';
