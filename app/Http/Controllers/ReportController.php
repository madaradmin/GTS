<?php

namespace App\Http\Controllers;

use App\Mail\IncidentReportMail;
use App\Models\Category;
use App\Models\Incident;
use App\Models\Priority;
use App\Models\Status;
use App\Models\User;
use App\Services\ReportService;
use App\Services\WhatsAppReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService,
        protected WhatsAppReportService $whatsAppService
    ) {}

    /**
     * Muestra la interfaz principal del sistema profesional de reportes.
     * Incluye presets, filtros dinámicos, resumen ejecutivo en tiempo real y vista previa.
     */
    public function index(Request $request): View
    {
        abort_if(! $request->user()->is_active, 403, 'Usuario inactivo.');
        abort_if(! $request->user()->isAdmin() && ! $request->user()->hasPermission('reports.view'), 403, 'Acceso denegado: No posee permisos para consultar reportes.');

        $filters = $request->only([
            'report_type',
            'period_preset',
            'date_from',
            'date_to',
            'status_id',
            'priority_id',
            'category_id',
            'assigned_to',
            'reported_by',
            'search',
            'folio',
        ]);

        // Validación y diagnóstico de búsqueda puntual por folio (Sección 5.10)
        $search = trim($request->input('search', ''));
        $folioInput = trim($request->input('folio', ''));
        $termToValidate = ! empty($folioInput) ? $folioInput : $search;
        $searchWarning = null;

        if (! empty($termToValidate) && preg_match('/^INC/i', $termToValidate)) {
            $isValidFolioFormat = (bool) preg_match('/^INC-\d{4}-\d{3,6}$/i', $termToValidate);
            if (! $isValidFolioFormat) {
                $searchWarning = 'No se encontró una incidencia válida con ese folio.';
            } else {
                $folioExists = Incident::where('folio', strtoupper($termToValidate))->exists();
                if (! $folioExists) {
                    $searchWarning = 'No se encontró ninguna incidencia con ese folio.';
                }
            }
        }

        $query = $this->reportService->buildQuery($filters, null, $request->user());
        $summary = $this->reportService->calculateSummary($query);
        $incidents = (clone $query)->paginate(15)->withQueryString();
        $appliedFilters = $this->reportService->getFilterLabels($filters);

        // Catálogos para los selectores de filtros
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $priorities = Priority::where('is_active', true)->orderBy('level')->get();
        $statuses = Status::orderBy('step_order')->get();
        $gtsUsers = User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $apocUsersQuery = User::whereHas('role', fn ($q) => $q->whereIn('slug', ['apoc', 'apoc-cco', 'apoc-csa']))
            ->where('is_active', true)
            ->orderBy('name');

        $like = User::query()->getConnection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        if ($request->user()->isApocCco()) {
            $apocUsers = User::where(function ($q) use ($like) {
                $q->whereHas('role', fn ($rq) => $rq->where('slug', 'apoc-cco'))
                    ->orWhere(function ($legacy) use ($like) {
                        $legacy->whereHas('role', fn ($rq) => $rq->where('slug', 'apoc'))
                            ->where(function ($match) use ($like) {
                                $match->where('username', $like, '%cco%')
                                    ->orWhere('badge_number', $like, '%CCO%');
                            });
                    });
            })->where('is_active', true)->orderBy('name')->get();
        } elseif ($request->user()->isApocCsa()) {
            $apocUsers = User::where(function ($q) use ($like) {
                $q->whereHas('role', fn ($rq) => $rq->where('slug', 'apoc-csa'))
                    ->orWhere(function ($legacy) use ($like) {
                        $legacy->whereHas('role', fn ($rq) => $rq->where('slug', 'apoc'))
                            ->where(function ($match) use ($like) {
                                $match->where('username', $like, '%csa%')
                                    ->orWhere('badge_number', $like, '%CSA%');
                            });
                    });
            })->where('is_active', true)->orderBy('name')->get();
        } else {
            $apocUsers = $apocUsersQuery->get();
        }

        // Generar vista previa de mensaje WhatsApp
        $reportFolioPreview = 'PREV-'.date('Ymd');
        $whatsAppPreviewText = $this->whatsAppService->formatReportMessage(
            $summary,
            $filters,
            $reportFolioPreview,
            route('reports.index', $filters)
        );

        return view('reports.index', [
            'filters' => $filters,
            'appliedFilters' => $appliedFilters,
            'summary' => $summary,
            'incidents' => $incidents,
            'categories' => $categories,
            'priorities' => $priorities,
            'statuses' => $statuses,
            'gtsUsers' => $gtsUsers,
            'apocUsers' => $apocUsers,
            'whatsAppPreviewText' => $whatsAppPreviewText,
            'searchWarning' => $searchWarning,
        ]);
    }

    /**
     * Genera y descarga el informe oficial en formato PDF institucional.
     * Permite exportar sobre filtros o sobre una selección explícita de incidencias (1 ficha por página).
     */
    public function exportPdf(Request $request): Response|RedirectResponse
    {
        abort_if(! $request->user()->is_active, 403, 'Usuario inactivo.');
        abort_if(! $request->user()->isAdmin() && ! $request->user()->hasPermission('reports.export'), 403, 'Acceso denegado: No posee permisos para exportar reportes.');

        $filters = $request->only([
            'report_type',
            'period_preset',
            'date_from',
            'date_to',
            'status_id',
            'priority_id',
            'category_id',
            'assigned_to',
            'reported_by',
            'search',
            'folio',
        ]);

        $rawSelected = $request->input('selected_ids');
        $selectedIds = null;

        if ($request->has('selected_ids')) {
            if (is_string($rawSelected)) {
                $rawSelected = array_filter(explode(',', $rawSelected));
            }
            if (empty($rawSelected) || ! is_array($rawSelected)) {
                return back()->with('search_warning', 'Debe seleccionar al menos una incidencia para generar el reporte.');
            }

            // Validar que las incidencias existan físicamente en PostgreSQL y correspondan a folios/IDs válidos
            $validatedQuery = Incident::where(function ($q) use ($rawSelected) {
                $numericIds = array_values(array_filter($rawSelected, 'is_numeric'));
                $stringFolios = array_values(array_filter($rawSelected, fn ($x) => ! is_numeric($x)));
                if (! empty($numericIds)) {
                    $q->whereIn('id', $numericIds);
                }
                if (! empty($stringFolios)) {
                    $q->orWhereIn('folio', $stringFolios);
                }
            });

            $validatedQuery->forUserScope($request->user());

            $validatedIncidents = $validatedQuery->pluck('id')->toArray();

            if (empty($validatedIncidents)) {
                return back()->with('search_warning', 'No se encontraron incidencias válidas para los identificadores seleccionados.');
            }

            $selectedIds = $validatedIncidents;
        }

        $reportFolio = $this->reportService->generateReportFolio();
        $pdf = $this->reportService->generatePdf($filters, $request->user(), $reportFolio, $selectedIds);

        return $pdf->download("Reporte_GTS_{$reportFolio}.pdf");
    }

    /**
     * Valida la dirección del destinatario y despacha el reporte oficial por correo con el PDF adjunto.
     */
    public function sendEmail(Request $request): RedirectResponse
    {
        abort_if(! $request->user()->is_active, 403, 'Usuario inactivo.');
        abort_if(! $request->user()->isAdmin() && ! $request->user()->hasPermission('reports.export'), 403, 'Acceso denegado: No posee permisos para exportar reportes.');

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'filters' => ['nullable', 'array'],
            'selected_ids' => ['nullable'],
        ]);

        $filters = $validated['filters'] ?? [];
        $selectedIds = $this->resolveSelectedIds($validated['selected_ids'] ?? null, $request->user());

        $reportFolio = $this->reportService->generateReportFolio();
        $pdf = $this->reportService->generatePdf($filters, $request->user(), $reportFolio, $selectedIds);
        $pdfBinary = $pdf->output();

        $query = $this->reportService->buildQuery($filters, $selectedIds, $request->user());
        $summary = $this->reportService->calculateSummary($query);
        $appliedFilters = $this->reportService->getFilterLabels($filters);
        if (! empty($selectedIds)) {
            $appliedFilters['Selección'] = count($selectedIds).' incidencias específicas';
        }

        Mail::to($validated['email'])->send(new IncidentReportMail(
            summary: $summary,
            appliedFilters: $appliedFilters,
            pdfBinary: $pdfBinary,
            reportFolio: $reportFolio,
            requester: $request->user(),
            notes: $validated['notes'] ?? null
        ));

        return back()->with('status_message', "El reporte formal #{$reportFolio} ha sido despachado exitosamente por correo electrónico a {$validated['email']}.");
    }

    /**
     * Procesa el despacho o generación de enlace seguro para compartir el resumen por WhatsApp.
     */
    public function shareWhatsApp(Request $request): JsonResponse|RedirectResponse
    {
        abort_if(! $request->user()->is_active, 403, 'Usuario inactivo.');
        abort_if(! $request->user()->isAdmin() && ! $request->user()->hasPermission('reports.export'), 403, 'Acceso denegado: No posee permisos para exportar reportes.');

        $validated = $request->validate([
            'phone' => ['required', 'string', 'min:10', 'max:25'],
            'filters' => ['nullable', 'array'],
            'selected_ids' => ['nullable'],
        ]);

        $filters = $validated['filters'] ?? [];
        $selectedIds = $this->resolveSelectedIds($validated['selected_ids'] ?? null, $request->user());

        $query = $this->reportService->buildQuery($filters, $selectedIds, $request->user());
        $summary = $this->reportService->calculateSummary($query);
        $reportFolio = $this->reportService->generateReportFolio();

        $selectedFolios = null;
        if (! empty($selectedIds)) {
            $foliosQuery = Incident::whereIn('id', $selectedIds);
            $foliosQuery->forUserScope($request->user());
            $selectedFolios = $foliosQuery->pluck('folio')->toArray();
        }

        $reportUrlParams = $filters;
        if (! empty($selectedIds)) {
            $reportUrlParams['selected_ids'] = $selectedIds;
        }
        $reportUrl = route('reports.index', $reportUrlParams);

        $message = $this->whatsAppService->formatReportMessage(
            $summary,
            $filters,
            $reportFolio,
            $reportUrl,
            $selectedFolios
        );

        $result = $this->whatsAppService->sendOrGenerate($validated['phone'], $message);

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        if ($result['success'] && ! empty($result['url'])) {
            return redirect()->away($result['url']);
        }

        return back()->with('status_message', $result['message']);
    }

    /**
     * Resuelve y valida en backend los identificadores seleccionados de incidencias.
     *
     * @return array<int>|null
     */
    protected function resolveSelectedIds(mixed $rawSelected, ?User $user = null): ?array
    {
        if (empty($rawSelected)) {
            return null;
        }

        if (is_string($rawSelected)) {
            $rawSelected = array_filter(explode(',', $rawSelected));
        }

        if (! is_array($rawSelected)) {
            return null;
        }

        $numericIds = array_values(array_filter($rawSelected, 'is_numeric'));
        $stringFolios = array_values(array_filter($rawSelected, fn ($x) => ! is_numeric($x)));

        $query = Incident::where(function ($q) use ($numericIds, $stringFolios) {
            if (! empty($numericIds)) {
                $q->whereIn('id', $numericIds);
            }
            if (! empty($stringFolios)) {
                $q->orWhereIn('folio', $stringFolios);
            }
        });

        $query->forUserScope($user);

        $valid = $query->pluck('id')->toArray();

        return ! empty($valid) ? $valid : null;
    }
}
