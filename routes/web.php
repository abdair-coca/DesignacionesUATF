<?php

use App\Http\Controllers\DesignacionController;
use App\Http\Controllers\DesignacionMasivaController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::redirect('/', '/designaciones');

    Route::get('designaciones/lista', [DesignacionController::class, 'lista'])
        ->name('designaciones.lista');
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
