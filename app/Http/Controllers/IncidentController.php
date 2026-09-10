<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncidentRequest;
use App\Models\Category;
use App\Models\Incident;
use App\Models\Priority;
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
     * (Estructura base para permitir la redirección de store; la vista completa se profundiza en el Paso 22).
     */
    public function show(string $folio): View
    {
        $incident = Incident::with(['category', 'priority', 'status', 'reportedBy', 'assignedTo', 'histories.user', 'attachments'])
            ->where('folio', $folio)
            ->firstOrFail();

        Gate::authorize('view', $incident);

        return view('incidents.show', compact('incident'));
    }
}
