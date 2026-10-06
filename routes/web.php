<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IncidentAttachmentController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\IndicatorController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Módulo de Incidencias Operativas (APOC / GTS)
    Route::get('/Todas las incidencias', [IncidentController::class, 'index'])->name('incidents.index');
    Route::get('/Mis incidencias', [IncidentController::class, 'myIncidents'])->name('incidents.my');

    // Redirecciones de compatibilidad para evitar enlaces rotos y preservar URLs canónicas
    Route::get('/incidencias', function (Request $request) {
        return redirect()->route('incidents.index', $request->query());
    });
    Route::get('/incidencias/mis-incidencias', function (Request $request) {
        return redirect()->route('incidents.my', $request->query());
    });
    Route::get('/incidencias/nueva', [IncidentController::class, 'create'])->name('incidents.create');
    Route::post('/incidencias', [IncidentController::class, 'store'])->name('incidents.store');
    Route::get('/incidencias/{incident}', [IncidentController::class, 'show'])->name('incidents.show');
    Route::post('/incidencias/{incident}/asignar', [IncidentController::class, 'assign'])->name('incidents.assign');
    Route::post('/incidencias/{incident}/iniciar-atencion', [IncidentController::class, 'startAttention'])->name('incidents.start-attention');
    Route::post('/incidencias/{incident}/notas', [IncidentController::class, 'addNote'])->name('incidents.add-note');
    Route::post('/incidencias/{incident}/resolver', [IncidentController::class, 'resolve'])->name('incidents.resolve');
    Route::post('/incidencias/{incident}/rechazar-solucion', [IncidentController::class, 'rejectResolution'])->name('incidents.reject-resolution');
    Route::post('/incidencias/{incident}/cerrar', [IncidentController::class, 'close'])->name('incidents.close');
    Route::delete('/incidencias/{incident}', [IncidentController::class, 'destroy'])->name('incidents.destroy')->middleware('role:admin');

    // Módulo de Trazabilidad y Auditoría (Exclusivo Administrador)
    Route::get('/auditoria', [AuditController::class, 'index'])->name('audit.index')->middleware('role:admin');
    Route::delete('/auditoria/incidencias/{incident}', [AuditController::class, 'destroyIncident'])->name('audit.incidents.destroy')->middleware('role:admin');

    // Módulo de Evidencias y Archivos Adjuntos (Fase 6 - Paso 27)
    Route::post('/incidencias/{incident}/adjuntos', [IncidentAttachmentController::class, 'store'])->name('incidents.attachments.store');
    Route::get('/incidencias/{incident}/adjuntos/{attachment}/ver', [IncidentAttachmentController::class, 'show'])->name('incidents.attachments.show');
    Route::get('/incidencias/{incident}/adjuntos/{attachment}/descargar', [IncidentAttachmentController::class, 'download'])->name('incidents.attachments.download');

    // Sistema de Notificaciones por Perfil (Paso 2)
    Route::get('/notificaciones', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notificaciones/{id}/leer', [NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
    Route::post('/notificaciones/leer-todas', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-as-read');

    // Módulo Profesional de Reportes Operativos (Paso 5)
    Route::get('/reportes', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reportes/pdf', [ReportController::class, 'exportPdf'])->name('reports.pdf');
    Route::post('/reportes/email', [ReportController::class, 'sendEmail'])->name('reports.email');
    Route::post('/reportes/whatsapp', [ReportController::class, 'shareWhatsApp'])->name('reports.whatsapp');

    // Módulo de Indicadores y Métricas Operacionales OACI / AFAC (Fase 3)
    Route::get('/indicadores', [IndicatorController::class, 'index'])->name('indicators.index');

    // Módulo de Catálogos e Infraestructura Tecnológica (Paso 7)
    Route::get('/catalogos', [CatalogController::class, 'index'])->name('catalogs.index');
    Route::post('/catalogos', [CatalogController::class, 'store'])->name('catalogs.store');
    Route::put('/catalogos/{category}', [CatalogController::class, 'update'])->name('catalogs.update');
    Route::post('/catalogos/{category}/status', [CatalogController::class, 'updateStatus'])->name('catalogs.status');
    Route::delete('/catalogos/{category}', [CatalogController::class, 'destroy'])->name('catalogs.destroy');

    // Módulo de Gestión de Usuarios y Cuadrillas (Fase 6 - Exclusivo Administrador)
    Route::middleware('role:admin')->group(function () {
        Route::get('/configuracion/usuarios', [UserController::class, 'index'])->name('users.index');
        Route::post('/configuracion/usuarios', [UserController::class, 'store'])->name('users.store');
        Route::put('/configuracion/usuarios/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/configuracion/usuarios/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::delete('/configuracion/usuarios/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
