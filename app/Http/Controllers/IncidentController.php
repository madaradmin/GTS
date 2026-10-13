<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddNoteRequest;
use App\Http\Requests\AssignIncidentRequest;
use App\Http\Requests\CloseIncidentRequest;
use App\Http\Requests\RejectResolutionRequest;
use App\Http\Requests\ResolveIncidentRequest;
use App\Http\Requests\StoreIncidentRequest;
use App\Models\Category;
use App\Models\Incident;
use App\Models\Priority;
use App\Models\Status;
use App\Models\User;
use App\Services\FileStorageService;
use App\Services\FolioGeneratorService;
use App\Services\IncidentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class IncidentController extends Controller
{
    public function __construct(
        protected IncidentService $incidentService,
        protected FolioGeneratorService $folioGenerator
    ) {}

    /**
     * Muestra el listado general de incidencias con búsqueda en tiempo real y filtrado multidimensional.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Incident::class);

        $user = $request->user();

        // Seguridad de Perfil: APOC no tiene asignaciones técnicas
        if ($request->has('assigned_to')) {
            abort_if($user->isApoc(), 403, 'Acceso denegado: El perfil APOC no tiene asignaciones técnicas.');
        }

        $filters = $request->only(['search', 'status_id', 'priority_id', 'category_id', 'assigned_to', 'reported_by']);

        // Aislamiento estricto de incidencias por ámbito operativo según rol del usuario
        $query = Incident::with(['category', 'priority', 'status', 'reportedBy.role', 'assignedTo', 'assignedAreas.role'])
            ->forUserScope($user);

        $incidents = $query->filter($filters)
            ->orderByRaw("
                CASE 
                    WHEN status_id IN (SELECT id FROM statuses WHERE code IN ('NUEVA', 'ASIGNADA', 'EN_ATENCION')) THEN 1 
                    ELSE 2 
                END ASC
            ")
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $priorities = Priority::where('is_active', true)->orderBy('level')->get();
        $statuses = Status::orderBy('step_order')->get();

        $baseMetricsQuery = Incident::query()->forUserScope($user);
        if ($request->routeIs('incidents.my')) {
            if (($user->isGts() || $user->hasPermission('incidents.attend')) && ! empty($filters['assigned_to'])) {
                $baseMetricsQuery->where('assigned_to_user_id', $filters['assigned_to']);
            } elseif (! empty($filters['reported_by'])) {
                $baseMetricsQuery->where('reported_by_user_id', $filters['reported_by']);
            }
        }

        $metrics = [
            'total' => (clone $baseMetricsQuery)->count(),
            'nuevas' => (clone $baseMetricsQuery)->whereHas('status', fn ($q) => $q->where('code', 'NUEVA'))->count(),
            'en_atencion' => (clone $baseMetricsQuery)->whereHas('status', fn ($q) => $q->where('code', 'EN_ATENCION'))->count(),
            'criticas' => (clone $baseMetricsQuery)->whereHas('priority', fn ($q) => $q->where('slug', 'critica'))
                ->whereHas('status', fn ($q) => $q->where('is_terminal', false))
                ->count(),
            'resueltas' => (clone $baseMetricsQuery)->whereHas('status', fn ($q) => $q->where('code', 'RESUELTA'))->count(),
        ];

        return view('incidents.index', compact(
            'incidents',
            'categories',
            'priorities',
            'statuses',
            'filters',
            'metrics'
        ));
    }

    /**
     * Muestra el listado de "Mis incidencias" para el usuario autenticado (APOC / GTS / Administrador).
     */
    public function myIncidents(Request $request): View
    {
        Gate::authorize('viewAny', Incident::class);

        $user = $request->user();

        if ($user->isGts() || ($user->hasPermission('incidents.attend') && ! $user->isAdmin() && ! $user->isApoc())) {
            $request->merge(['assigned_to' => $user->id]);
        } elseif ($user->isApoc() || (! $user->isAdmin() && ! $user->hasPermission('incidents.attend'))) {
            $request->merge(['reported_by' => $user->id]);
        } elseif ($user->isAdmin() && ! $request->has('assigned_to') && ! $request->has('reported_by')) {
            $request->merge(['reported_by' => $user->id]);
        }

        return $this->index($request);
    }

    /**
     * Muestra el formulario para crear y despachar una nueva incidencia.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', Incident::class);

        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $priorities = Priority::where('is_active', true)->orderBy('level')->get();
        $preliminaryFolio = $this->folioGenerator->getNextFolioPreview();

        return view('incidents.create', compact('categories', 'priorities', 'preliminaryFolio'));
    }

    /**
     * Almacena una nueva incidencia en la base de datos tras validar las reglas de negocio.
     */
    public function store(StoreIncidentRequest $request): RedirectResponse
    {
        $attachments = $request->file('attachments', []);

        $incident = $this->incidentService->createIncident(
            data: $request->validated(),
            reporter: $request->user(),
            attachments: is_array($attachments) ? $attachments : [$attachments]
        );

        return redirect()->route('incidents.show', $incident->folio)
            ->with('success', "Incidencia {$incident->folio} creada y despachada a GTS exitosamente.");
    }

    /**
     * Muestra el detalle de una incidencia por su folio único.
     */
    public function show(Incident $incident): View
    {
        Gate::authorize('view', $incident);

        $incident->load([
            'category',
            'priority',
            'status',
            'reportedBy.role',
            'assignedTo.role',
            'assignedAreas.role',
            'histories.user',
            'attachments.uploadedBy',
        ]);

        $currentUser = request()->user();
        $availableAreas = $this->incidentService->getAssignableAreas($currentUser, $incident);
        $availableTechnicians = $this->incidentService->getAvailableTechnicians($currentUser, $incident);
        $gtsUsers = $availableTechnicians;

        return view('incidents.show', compact('incident', 'gtsUsers', 'availableAreas', 'availableTechnicians'));
    }

    /**
     * Asigna o reasigna áreas responsables y un especialista técnico a la incidencia.
     */
    public function assign(AssignIncidentRequest $request, Incident $incident): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        Gate::authorize('assign', $incident);

        $areas = (array) $request->validated('areas', []);
        $technicianId = $request->validated('assigned_to_user_id');
        $comment = $request->validated('comment');

        $technician = $technicianId && is_numeric($technicianId)
            ? User::find($technicianId)
            : null;

        $this->incidentService->assign(
            incident: $incident,
            assigner: $request->user(),
            areas: $areas,
            technician: $technician,
            comment: $comment
        );

        if ($technician) {
            $isSelfAssign = $request->user()->id === $technician->id;
            $message = $isSelfAssign
                ? "Has tomado la responsabilidad técnica de la incidencia {$incident->folio}."
                : "Incidencia {$incident->folio} asignada al especialista {$technician->name} exitosamente.";
        } else {
            $areaNames = $incident->fresh()->assignedAreaNames();
            $label = ! empty($areaNames) ? implode(', ', $areaNames) : 'las áreas seleccionadas';
            $message = "Incidencia {$incident->folio} asignada exitosamente al área responsable: {$label}.";
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'folio' => $incident->folio,
            ]);
        }

        return redirect()->route('incidents.show', $incident->folio)
            ->with('success', $message);
    }

    /**
     * Inicia formalmente la atención técnica y diagnóstico de la incidencia (transición a EN_ATENCION).
     */
    public function startAttention(Request $request, Incident $incident): RedirectResponse
    {
        Gate::authorize('startAttention', $incident);

        $this->incidentService->startAttention(
            incident: $incident,
            user: $request->user(),
            comment: $request->input('comment')
        );

        return redirect()->route('incidents.show', $incident->folio)
            ->with('success', "Atención técnica iniciada. La incidencia {$incident->folio} se encuentra activamente EN ATENCIÓN.");
    }

    /**
     * Registra una nota técnica de campo o avance de diagnóstico durante el estado EN_ATENCION.
     */
    public function addNote(AddNoteRequest $request, Incident $incident): RedirectResponse
    {
        Gate::authorize('addNote', $incident);

        $this->incidentService->addTechnicalNote(
            incident: $incident,
            user: $request->user(),
            note: $request->validated('note')
        );

        return redirect()->route('incidents.show', $incident->folio)
            ->with('success', 'Nota técnica de campo registrada exitosamente en la bitácora.');
    }

    /**
     * Marca la incidencia como RESUELTA documentando la solución técnica aplicada (RF-05 / RN-02).
     */
    public function resolve(ResolveIncidentRequest $request, Incident $incident): RedirectResponse
    {
        Gate::authorize('resolve', $incident);

        $this->incidentService->resolveIncident(
            incident: $incident,
            technician: $request->user(),
            solutionDescription: $request->validated('solution_description')
        );

        return redirect()->route('incidents.show', $incident->folio)
            ->with('success', "Incidencia {$incident->folio} resuelta exitosamente. Pendiente de validación y visto bueno por APOC.");
    }

    /**
     * Rechaza la solución técnica presentada por GTS y reingresa el ticket a EN_ATENCION (RF-05 / RN-02).
     */
    public function rejectResolution(RejectResolutionRequest $request, Incident $incident): RedirectResponse
    {
        Gate::authorize('rejectResolution', $incident);

        $this->incidentService->rejectResolution(
            incident: $incident,
            actor: $request->user(),
            reason: $request->validated('rejection_reason')
        );

        return redirect()->route('incidents.show', $incident->folio)
            ->with('success', "Solución de la incidencia {$incident->folio} rechazada. El expediente ha retornado al estado EN ATENCIÓN para nuevo diagnóstico por GTS.");
    }

    /**
     * Valida el restablecimiento de servicio, otorga el visto bueno y cierra definitivamente el ticket (RF-05 / RN-02).
     */
    public function close(CloseIncidentRequest $request, Incident $incident): RedirectResponse
    {
        Gate::authorize('close', $incident);

        $closer = $request->user();
        $this->incidentService->closeIncident(
            incident: $incident,
            closer: $closer,
            comment: $request->validated('comment')
        );

        return redirect()->route('incidents.show', $incident->folio)
            ->with('success', "Incidencia {$incident->folio} cerrada definitivamente con visto bueno de {$closer->getTaggedName()}. Expediente archivado en modo inmutable.");
    }

    /**
     * Eliminación de una incidencia con borrado seguro de evidencias (exclusivo Administrador).
     */
    public function destroy(
        Request $request,
        Incident $incident,
        FileStorageService $fileStorageService
    ): RedirectResponse {
        abort_if(! $request->user()->is_active, 403, 'Usuario inactivo.');
        abort_if(! $request->user()->isAdmin() && ! $request->user()->hasPermission('incidents.delete'), 403, 'Acceso denegado: No posee permisos para eliminar incidencias.');
        Gate::authorize('delete', $incident);

        $folio = $incident->folio;

        DB::transaction(function () use ($incident, $fileStorageService) {
            $fileStorageService->deleteIncidentFiles($incident);
            $incident->delete();
        });

        return redirect()->route('incidents.index')
            ->with('status_message', "La incidencia #{$folio} ha sido eliminada exitosamente.");
    }
}
