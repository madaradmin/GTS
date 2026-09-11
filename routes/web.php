<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IncidentAttachmentController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Módulo de Incidencias Operativas (APOC / GTS)
    Route::get('/incidencias', [IncidentController::class, 'index'])->name('incidents.index');
    Route::get('/incidencias/nueva', [IncidentController::class, 'create'])->name('incidents.create');
    Route::post('/incidencias', [IncidentController::class, 'store'])->name('incidents.store');
    Route::get('/incidencias/{incident}', [IncidentController::class, 'show'])->name('incidents.show');
    Route::post('/incidencias/{incident}/asignar', [IncidentController::class, 'assign'])->name('incidents.assign');
    Route::post('/incidencias/{incident}/iniciar-atencion', [IncidentController::class, 'startAttention'])->name('incidents.start-attention');
    Route::post('/incidencias/{incident}/notas', [IncidentController::class, 'addNote'])->name('incidents.add-note');
    Route::post('/incidencias/{incident}/resolver', [IncidentController::class, 'resolve'])->name('incidents.resolve');
    Route::post('/incidencias/{incident}/rechazar-solucion', [IncidentController::class, 'rejectResolution'])->name('incidents.reject-resolution');
    Route::post('/incidencias/{incident}/cerrar', [IncidentController::class, 'close'])->name('incidents.close');

    // Módulo de Trazabilidad y Auditoría (Fase 6 - Paso 26)
    Route::get('/auditoria', [AuditController::class, 'index'])->name('audit.index');

    // Módulo de Evidencias y Archivos Adjuntos (Fase 6 - Paso 27)
    Route::post('/incidencias/{incident}/adjuntos', [IncidentAttachmentController::class, 'store'])->name('incidents.attachments.store');
    Route::get('/incidencias/{incident}/adjuntos/{attachment}/ver', [IncidentAttachmentController::class, 'show'])->name('incidents.attachments.show');
    Route::get('/incidencias/{incident}/adjuntos/{attachment}/descargar', [IncidentAttachmentController::class, 'download'])->name('incidents.attachments.download');

    // Sistema de Notificaciones por Perfil (Paso 2)
    Route::get('/notificaciones', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notificaciones/{id}/leer', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
    Route::post('/notificaciones/leer-todas', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-as-read');

    // Módulo Profesional de Reportes Operativos (Paso 5)
    Route::get('/reportes', [\App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reportes/pdf', [\App\Http\Controllers\ReportController::class, 'exportPdf'])->name('reports.pdf');
    Route::post('/reportes/email', [\App\Http\Controllers\ReportController::class, 'sendEmail'])->name('reports.email');
    Route::post('/reportes/whatsapp', [\App\Http\Controllers\ReportController::class, 'shareWhatsApp'])->name('reports.whatsapp');

    // Módulo de Catálogos e Infraestructura Tecnológica (Paso 7)
    Route::get('/catalogos', [\App\Http\Controllers\CatalogController::class, 'index'])->name('catalogs.index');
    Route::post('/catalogos', [\App\Http\Controllers\CatalogController::class, 'store'])->name('catalogs.store');
    Route::put('/catalogos/{category}', [\App\Http\Controllers\CatalogController::class, 'update'])->name('catalogs.update');
    Route::post('/catalogos/{category}/status', [\App\Http\Controllers\CatalogController::class, 'updateStatus'])->name('catalogs.status');
    Route::delete('/catalogos/{category}', [\App\Http\Controllers\CatalogController::class, 'destroy'])->name('catalogs.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    // Estado de Turno de Agentes GTS (Paso 8)
    Route::post('/profile/shift-status', [\App\Http\Controllers\AgentShiftController::class, 'update'])->name('profile.shift-status');
});

require __DIR__.'/auth.php';
