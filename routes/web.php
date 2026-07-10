<?php

use App\Http\Controllers\DesignacionController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/designaciones');

Route::resource('designaciones', DesignacionController::class)
    ->except('show')
    ->parameters(['designaciones' => 'designacion']);
Route::get('designaciones/{designacion}/historial', [DesignacionController::class, 'historial'])
    ->name('designaciones.historial');
