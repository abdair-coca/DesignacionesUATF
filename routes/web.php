<?php

use App\Http\Controllers\CarreraController;
use App\Http\Controllers\DesignacionController;
use App\Http\Controllers\DesignacionMasivaController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\GestionController;
use App\Http\Controllers\GrupoController;
use App\Http\Controllers\MateriaController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::redirect('/', '/designaciones');

    // Catálogos
    Route::resource('carreras', CarreraController::class)->except('show');
    Route::resource('materias', MateriaController::class)->except('show');
    Route::resource('grupos', GrupoController::class)->except('show');
    Route::resource('docentes', DocenteController::class)->except('show');
    Route::resource('gestiones', GestionController::class)
        ->except('show')
        ->parameters(['gestiones' => 'gestion']);

    Route::get('designaciones/lista', [DesignacionController::class, 'lista'])
        ->name('designaciones.lista');
    Route::get('designaciones/carrera/{carrera}', [DesignacionController::class, 'carrera'])
        ->name('designaciones.carrera');
    Route::get('designaciones/asignar', [DesignacionMasivaController::class, 'asignarForm'])
        ->name('designaciones.asignar');
    Route::post('designaciones/asignar', [DesignacionMasivaController::class, 'asignarStore'])
        ->name('designaciones.asignar.store');
    Route::get('designaciones/copiar', [DesignacionMasivaController::class, 'copiarForm'])
        ->name('designaciones.copiar');
    Route::post('designaciones/copiar', [DesignacionMasivaController::class, 'copiarStore'])
        ->name('designaciones.copiar.store');

    Route::resource('designaciones', DesignacionController::class)
        ->except('show')
        ->parameters(['designaciones' => 'designacion']);
    Route::get('designaciones/{designacion}/historial', [DesignacionController::class, 'historial'])
        ->name('designaciones.historial');
});

require __DIR__.'/auth.php';
