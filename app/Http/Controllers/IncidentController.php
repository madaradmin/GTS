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
use App\Services\FolioGeneratorService;
use App\Services\IncidentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $filters = $request->only(['search', 'status_id', 'priority_id', 'category_id']);

        $incidents = Incident::with(['category', 'priority', 'status', 'reportedBy', 'assignedTo'])
            ->filter($filters)
            ->orderByRaw("
                CASE 
                    WHEN status_id IN (SELECT id FROM statuses WHERE code IN ('NUEVA', 'ASIGNADA', 'EN_ATENCION')) THEN 1 
                    ELSE 2 
                END ASC
            ")
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $priorities = Priority::where('is_active', true)->orderBy('level')->get();
        $statuses   = Status::orderBy('step_order')->get();

        $metrics = [
            'total' => Incident::count(),
            'nuevas' => Incident::whereHas('status', fn($q) => $q->where('code', 'NUEVA'))->count(),
            'en_atencion' => Incident::whereHas('status', fn($q) => $q->where('code', 'EN_ATENCION'))->count(),
            'criticas' => Incident::whereHas('priority', fn($q) => $q->where('slug', 'critica'))
                ->whereHas('status', fn($q) => $q->where('is_terminal', false))
                ->count(),
            'resueltas' => Incident::whereHas('status', fn($q) => $q->where('code', 'RESUELTA'))->count(),
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
            'reportedBy',
            'assignedTo',
            'histories.user',
            'attachments.uploadedBy',
        ]);

        $gtsUsers = User::where('is_active', true)
            ->whereHas('role', fn($q) => $q->where('slug', 'gts'))
            ->orderBy('name')
            ->get();

        return view('incidents.show', compact('incident', 'gtsUsers'));
    }

    /**
     * Asigna o reasigna un especialista GTS a la incidencia.
     */
    public function assign(AssignIncidentRequest $request, Incident $incident): RedirectResponse
    {
        Gate::authorize('assign', $incident);

        $technician = User::findOrFail($request->validated('assigned_to_user_id'));

        $this->incidentService->assignIncident(
            incident: $incident,
            assigner: $request->user(),
            technician: $technician,
            comment: $request->validated('comment')
        );

        $message = $request->user()->id === $technician->id
            ? "Has tomado la responsabilidad técnica de la incidencia {$incident->folio}."
            : "Incidencia {$incident->folio} asignada al especialista {$technician->name} exitosamente.";

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
            ->with('success', "Nota técnica de campo registrada exitosamente en la bitácora.");
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

        $this->incidentService->closeIncident(
            incident: $incident,
            closer: $request->user(),
            comment: $request->validated('comment')
        );

        return redirect()->route('incidents.show', $incident->folio)
            ->with('success', "Incidencia {$incident->folio} cerrada definitivamente con visto bueno de APOC. Expediente archivado en modo inmutable.");
    }
}
