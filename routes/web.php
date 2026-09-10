<?php

use App\Http\Controllers\IncidentController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Módulo de Incidencias Operativas (APOC / GTS)
    Route::get('/incidencias', [IncidentController::class, 'index'])->name('incidents.index');
    Route::get('/incidencias/nueva', [IncidentController::class, 'create'])->name('incidents.create');
    Route::post('/incidencias', [IncidentController::class, 'store'])->name('incidents.store');
    Route::get('/incidencias/{incident}', [IncidentController::class, 'show'])->name('incidents.show');
    Route::post('/incidencias/{incident}/asignar', [IncidentController::class, 'assign'])->name('incidents.assign');
    Route::post('/incidencias/{incident}/iniciar-atencion', [IncidentController::class, 'startAttention'])->name('incidents.start-attention');
    Route::post('/incidencias/{incident}/notas', [IncidentController::class, 'addNote'])->name('incidents.add-note');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
