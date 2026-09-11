<?php

namespace App\Http\Controllers;

use App\Mail\IncidentReportMail;
use App\Models\Category;
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
        abort_if(!$request->user()->is_active, 403, 'Usuario inactivo.');

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
        ]);

        $query = $this->reportService->buildQuery($filters);
        $summary = $this->reportService->calculateSummary($query);
        $incidents = (clone $query)->paginate(15)->withQueryString();
        $appliedFilters = $this->reportService->getFilterLabels($filters);

        // Catálogos para los selectores de filtros
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $priorities = Priority::where('is_active', true)->orderBy('level')->get();
        $statuses = Status::orderBy('step_order')->get();
        $gtsUsers = User::whereHas('role', fn($q) => $q->where('slug', 'gts'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $apocUsers = User::whereHas('role', fn($q) => $q->where('slug', 'apoc'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Generar vista previa de mensaje WhatsApp
        $reportFolioPreview = 'PREV-' . date('Ymd');
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
        ]);
    }

    /**
     * Genera y descarga el informe oficial en formato PDF institucional con los mismos filtros aplicados.
     */
    public function exportPdf(Request $request): Response
    {
        abort_if(!$request->user()->is_active, 403, 'Usuario inactivo.');

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
        ]);

        $reportFolio = $this->reportService->generateReportFolio();
        $pdf = $this->reportService->generatePdf($filters, $request->user(), $reportFolio);

        return $pdf->download("Reporte_GTS_{$reportFolio}.pdf");
    }

    /**
     * Valida la dirección del destinatario y despacha el reporte oficial por correo con el PDF adjunto.
     */
    public function sendEmail(Request $request): RedirectResponse
    {
        abort_if(!$request->user()->is_active, 403, 'Usuario inactivo.');

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'filters' => ['nullable', 'array'],
        ]);

        $filters = $validated['filters'] ?? [];
        $reportFolio = $this->reportService->generateReportFolio();
        $pdf = $this->reportService->generatePdf($filters, $request->user(), $reportFolio);
        $pdfBinary = $pdf->output();

        $query = $this->reportService->buildQuery($filters);
        $summary = $this->reportService->calculateSummary($query);
        $appliedFilters = $this->reportService->getFilterLabels($filters);

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
        abort_if(!$request->user()->is_active, 403, 'Usuario inactivo.');

        $validated = $request->validate([
            'phone' => ['required', 'string', 'min:10', 'max:25'],
            'filters' => ['nullable', 'array'],
        ]);

        $filters = $validated['filters'] ?? [];
        $query = $this->reportService->buildQuery($filters);
        $summary = $this->reportService->calculateSummary($query);
        $reportFolio = $this->reportService->generateReportFolio();

        $reportUrl = route('reports.index', $filters);
        $message = $this->whatsAppService->formatReportMessage(
            $summary,
            $filters,
            $reportFolio,
            $reportUrl
        );

        $result = $this->whatsAppService->sendOrGenerate($validated['phone'], $message);

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        if ($result['success'] && !empty($result['url'])) {
            return redirect()->away($result['url']);
        }

        return back()->with('status_message', $result['message']);
    }
}

