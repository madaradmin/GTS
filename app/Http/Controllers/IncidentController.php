<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignIncidentRequest;
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
}
