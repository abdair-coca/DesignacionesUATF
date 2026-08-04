<?php

use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\PropuestaController;
use App\Http\Controllers\RevisionPropuestaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/', function (Request $request) {
        if ($request->user()?->esVicerrectorado()) {
            return redirect()->route('revisiones.pendientes');
        }

        return redirect()->route('designaciones.index');
    });

    Route::middleware('rol:director_carrera')->group(function () {
        Route::get('designaciones', [PropuestaController::class, 'index'])->name('designaciones.index');
        Route::post('designaciones', [PropuestaController::class, 'crear'])->name('designaciones.crear');
        Route::post('designaciones/copiar/previsualizar', [PropuestaController::class, 'previsualizarCopia'])->name('designaciones.copiar.previsualizar');
        Route::post('designaciones/copiar', [PropuestaController::class, 'copiar'])->name('designaciones.copiar');
        Route::get('designaciones/{propuesta}/importar', [PropuestaController::class, 'importar'])->whereNumber('propuesta')->name('designaciones.importar');
        Route::post('designaciones/{propuesta}/importar/previsualizar', [PropuestaController::class, 'previsualizarImportacion'])->whereNumber('propuesta')->name('designaciones.importar.previsualizar');
        Route::post('designaciones/{propuesta}/importar', [PropuestaController::class, 'aplicarImportacion'])->whereNumber('propuesta')->name('designaciones.importar.aplicar');
        Route::put('designaciones/{propuesta}/asignaciones', [PropuestaController::class, 'guardar'])->whereNumber('propuesta')->name('designaciones.guardar');
        Route::post('designaciones/{propuesta}/enviar', [PropuestaController::class, 'enviar'])->whereNumber('propuesta')->name('designaciones.enviar');
        Route::post('designacion-versiones/{version}/retirar', [PropuestaController::class, 'retirar'])->whereNumber('version')->name('designaciones.versiones.retirar');
        Route::get('designaciones/{propuesta}', [PropuestaController::class, 'editar'])->whereNumber('propuesta')->name('designaciones.editar');
    });

    Route::get('notificaciones', [NotificacionController::class, 'index'])->name('notificaciones.index');
    Route::post('notificaciones/leer-todas', [NotificacionController::class, 'marcarTodasLeidas'])->name('notificaciones.leer_todas');
    Route::post('notificaciones/{notificacion}/leer', [NotificacionController::class, 'marcarLeida'])->name('notificaciones.leer');

    Route::middleware('rol:vicerrectorado')->group(function () {
        Route::get('revisiones/pendientes', [RevisionPropuestaController::class, 'pendientes'])->name('revisiones.pendientes');
        Route::get('revisiones/{version}/revisar', [RevisionPropuestaController::class, 'revisar'])->whereNumber('version')->name('revisiones.revisar');
        Route::post('revisiones/{version}/decidir', [RevisionPropuestaController::class, 'decidir'])->whereNumber('version')->name('revisiones.decidir');
    });
});

require __DIR__.'/auth.php';
