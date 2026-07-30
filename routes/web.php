<?php

use App\Http\Controllers\DesignacionController;
use App\Http\Controllers\DesignacionMasivaController;
use App\Http\Controllers\PropuestaController;
use App\Http\Controllers\RevisionController;
use App\Http\Controllers\RevisionPropuestaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/', function (Request $request) {
        if ($request->user()?->esVicerrectorado()) {
            return redirect()->route('versiones.pendientes');
        }

        return redirect()->route('propuestas.index');
    });

    Route::middleware('rol:director_carrera')->group(function () {
        Route::get('propuestas', [PropuestaController::class, 'index'])->name('propuestas.index');
        Route::post('propuestas', [PropuestaController::class, 'crear'])->name('propuestas.crear');
        Route::get('propuestas/{propuesta}', [PropuestaController::class, 'editar'])->name('propuestas.editar');
        Route::put('propuestas/{propuesta}/designaciones', [PropuestaController::class, 'guardar'])->name('propuestas.guardar');
        Route::post('propuestas/{propuesta}/enviar', [PropuestaController::class, 'enviar'])->name('propuestas.enviar');
        Route::post('propuesta-versiones/{version}/retirar', [PropuestaController::class, 'retirar'])->name('propuestas.versiones.retirar');

        Route::get('designaciones/lista', [DesignacionController::class, 'lista'])
            ->name('designaciones.lista');
        Route::get('designaciones/carrera/{carrera}', [DesignacionController::class, 'carrera'])
            ->name('designaciones.carrera');
        Route::post('designaciones/carrera/{carrera}/guardar', [DesignacionController::class, 'guardarRoster'])
            ->name('designaciones.carrera.guardar');
        Route::post('designaciones/carrera/{carrera}/copiar-anterior', [DesignacionController::class, 'copiarAnterior'])
            ->name('designaciones.carrera.copiar_anterior');
        Route::post('designaciones/carrera/{carrera}/previsualizar-copia', [DesignacionController::class, 'previsualizarCopia'])
            ->name('designaciones.carrera.previsualizar_copia');
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

        Route::post('revisiones/crear-propuesta', [RevisionController::class, 'crearPropuesta'])->name('revisiones.crear_propuesta');
        Route::post('revisiones/solicitar', [RevisionController::class, 'solicitar'])->name('revisiones.solicitar');
        Route::post('revisiones/{revision}/retirar', [RevisionController::class, 'retirar'])->name('revisiones.retirar');
        Route::delete('revisiones/{revision}', [RevisionController::class, 'destroy'])->name('revisiones.destroy');
    });

    Route::middleware('rol:vicerrectorado')->group(function () {
        Route::get('versiones/pendientes', [RevisionPropuestaController::class, 'pendientes'])->name('versiones.pendientes');
        Route::get('versiones/{version}/revisar', [RevisionPropuestaController::class, 'revisar'])->name('versiones.revisar');
        Route::post('versiones/{version}/decidir', [RevisionPropuestaController::class, 'decidir'])->name('versiones.decidir');

        Route::get('revisiones/pendientes', [RevisionController::class, 'pendientes'])->name('revisiones.pendientes');
        Route::get('revisiones/{revision}/revisar', [RevisionController::class, 'revisar'])->name('revisiones.revisar');
        Route::post('revisiones/{revision}/procesar', [RevisionController::class, 'procesar'])->name('revisiones.procesar');
        Route::post('revisiones/{revision}/completar', [RevisionController::class, 'completar'])->name('revisiones.completar');
    });
});

require __DIR__.'/auth.php';
