<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Incident;
use App\Models\Status;
use App\Models\User;
use App\Services\AuditService;
use App\Services\FileStorageService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Muestra la bitácora global de auditoría e historial pericial de GIT.
     * Acceso autorizado a roles activos APOC, GTS y Administrador (RF-05 / RN-04).
     */
    public function index(Request $request): View
    {
        abort_if(! $request->user()->is_active, 403, 'Usuario inactivo.');
        abort_if(! $request->user()->isAdmin() && ! $request->user()->hasPermission('audit.view'), 403, 'Acceso denegado: No posee permisos para consultar la bitácora de auditoría.');
        Gate::authorize('viewAudit', Incident::class);

        $filters = $request->only([
            'search',
            'action',
            'user_id',
            'folio',
            'date_from',
            'date_to',
            'status_id',
            'category_id',
            'priority_id',
        ]);

        $incidents = $this->auditService->getAllIncidentsAudit($filters, 20);
        $histories = $this->auditService->getGlobalAuditTrail($filters, 20);
        $statistics = $this->auditService->getStatistics();
        $actions = $this->auditService->getAvailableActions();
        $users = User::with('role')->orderBy('name')->get();
        $statuses = Status::orderBy('step_order')->get();
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        return view('audit.index', [
            'incidents' => $incidents,
            'histories' => $histories,
            'statistics' => $statistics,
            'actions' => $actions,
            'users' => $users,
            'statuses' => $statuses,
            'categories' => $categories,
            'filters' => $filters,
        ]);
    }

    /**
     * Elimina de forma segura y permanente un expediente de incidencia (exclusivo Administrador).
     * Garantiza la integridad referencial en PostgreSQL y la eliminación física de evidencias en disco.
     */
    public function destroyIncident(
        Request $request,
        Incident $incident,
        FileStorageService $fileStorageService,
        NotificationService $notificationService
    ): RedirectResponse {
        abort_if(! $request->user()->is_active, 403, 'Usuario inactivo.');
        abort_if(! $request->user()->isAdmin() && ! $request->user()->hasPermission('incidents.delete'), 403, 'Acceso denegado: No posee permisos para eliminar incidencias.');
        Gate::authorize('delete', $incident);

        $folio = $incident->folio;
        $incidentId = $incident->id;

        DB::transaction(function () use ($incident, $fileStorageService, $notificationService, $incidentId, $folio) {
            // 1. Purgar notificaciones asociadas para todos los roles
            $notificationService->deleteNotificationsForIncident($incidentId, $folio);

            // 2. Purgar archivos físicos de evidencias en almacenamiento
            $fileStorageService->deleteIncidentFiles($incident);

            // 3. Eliminar registro de incidencia (cascadeOnDelete en incident_histories e incident_attachments)
            $incident->delete();
        });

        return redirect()->route('audit.index')
            ->with('status_message', "El expediente de la incidencia #{$folio} y sus archivos asociados han sido eliminados definitivamente del sistema.");
    }
}
