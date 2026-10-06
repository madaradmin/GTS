<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Incident;
use App\Models\Priority;
use App\Models\Status;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPDFInstance;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class ReportService
{
    public function __construct(
        protected WhatsAppReportService $whatsAppService
    ) {}

    /**
     * Construye la consulta de incidencias basada en los filtros multidimensionales seleccionados
     * o en un conjunto específico de identificadores/folios seleccionados.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<int|string>|null  $selectedIds
     */
    public function buildQuery(array $filters = [], ?array $selectedIds = null, ?User $user = null): Builder
    {
        $query = Incident::query()->with([
            'category',
            'priority',
            'status',
            'reportedBy.role',
            'assignedTo.role',
            'histories.user.role',
            'attachments.user',
        ]);

        // Aislamiento estricto de incidencias por ámbito operativo según rol del usuario
        $query->forUserScope($user);

        // Si se especificó una selección explícita de incidencias (por ID o Folio)
        if (! empty($selectedIds)) {
            $numericIds = array_values(array_filter($selectedIds, fn ($id) => is_numeric($id)));
            $stringFolios = array_values(array_filter($selectedIds, fn ($id) => ! is_numeric($id)));

            return $query->where(function (Builder $q) use ($numericIds, $stringFolios) {
                if (! empty($numericIds)) {
                    $q->whereIn('incidents.id', $numericIds);
                }
                if (! empty($stringFolios)) {
                    $q->orWhereIn('incidents.folio', $stringFolios);
                }
            })->orderBy('incidents.created_at', 'desc');
        }

        $search = trim($filters['search'] ?? '');
        $isSpecificFolio = ! empty($filters['folio']) || (bool) preg_match('/^INC-\d{4}-\d+/i', $search);

        // 1. Presets temporales predefinidos (solo si no es búsqueda directa por folio puntual)
        if (! $isSpecificFolio && ! empty($filters['period_preset']) && empty($filters['date_from']) && empty($filters['date_to'])) {
            $tz = config('app.timezone', 'America/Mexico_City');
            match ($filters['period_preset']) {
                'today' => $query->whereBetween('incidents.created_at', [
                    Carbon::now($tz)->startOfDay(),
                    Carbon::now($tz)->endOfDay(),
                ]),
                'last_7_days', 'week' => $query->whereBetween('incidents.created_at', [
                    Carbon::now($tz)->subDays(7)->startOfDay(),
                    Carbon::now($tz)->endOfDay(),
                ]),
                'this_month', 'month' => $query->whereBetween('incidents.created_at', [
                    Carbon::now($tz)->startOfMonth(),
                    Carbon::now($tz)->endOfMonth(),
                ]),
                default => null,
            };
        }

        // 2. Rango manual de fechas
        if (! empty($filters['date_from'])) {
            $from = Carbon::parse($filters['date_from'])->startOfDay();
            $query->where('incidents.created_at', '>=', $from);
        }

        if (! empty($filters['date_to'])) {
            $to = Carbon::parse($filters['date_to'])->endOfDay();
            $query->where('incidents.created_at', '<=', $to);
        }

        // 3. Filtro de Estado Operacional
        if (! empty($filters['status_id'])) {
            $query->where('incidents.status_id', $filters['status_id']);
        }

        // 4. Filtro de Nivel de Prioridad
        if (! empty($filters['priority_id'])) {
            $query->where('incidents.priority_id', $filters['priority_id']);
        }

        // 5. Filtro de Subsistema / Categoría
        if (! empty($filters['category_id'])) {
            $query->where('incidents.category_id', $filters['category_id']);
        }

        // 6. Filtro de Técnico GTS Asignado
        if (! empty($filters['assigned_to'])) {
            $query->where('incidents.assigned_to_user_id', $filters['assigned_to']);
        }

        // 7. Filtro de Reportante APOC
        if (! empty($filters['reported_by'])) {
            $query->where('incidents.reported_by_user_id', $filters['reported_by']);
        }

        // 8. Preset de Tipo de Reporte Operativo
        if (! empty($filters['report_type'])) {
            match ($filters['report_type']) {
                'critical' => $query->whereHas('priority', fn ($p) => $p->where('slug', 'critica')),
                'high' => $query->whereHas('priority', fn ($p) => $p->where('slug', 'alta')),
                'open' => $query->whereHas('status', fn ($s) => $s->where('is_terminal', false)),
                'resolved' => $query->whereHas('status', fn ($s) => $s->whereIn('code', ['RESUELTA', 'CERRADA'])),
                default => null,
            };
        }

        // 9. Búsqueda por folio directo o por término libre
        if (! empty($filters['folio'])) {
            $query->where('incidents.folio', strtoupper(trim($filters['folio'])));
        } elseif (! empty($search)) {
            if ($isSpecificFolio) {
                $query->where('incidents.folio', strtoupper($search));
            } else {
                $query->search($search);
            }
        }

        return $query->orderBy('incidents.created_at', 'desc');
    }

    /**
     * Calcula métricas y resumen ejecutivo sobre el conjunto filtrado.
     *
     * @return array<string, mixed>
     */
    public function calculateSummary(Builder $query): array
    {
        $baseQuery = (clone $query)->reorder();
        $total = $baseQuery->count();

        // Conteo por código de estado
        $byStatus = [
            'NUEVA' => 0,
            'ASIGNADA' => 0,
            'EN_ATENCION' => 0,
            'RESUELTA' => 0,
            'CERRADA' => 0,
        ];

        $statusCounts = (clone $query)
            ->reorder()
            ->join('statuses', 'incidents.status_id', '=', 'statuses.id')
            ->selectRaw('statuses.code, count(*) as count')
            ->groupBy('statuses.code')
            ->pluck('count', 'code')
            ->toArray();

        foreach ($statusCounts as $code => $count) {
            $byStatus[$code] = (int) $count;
        }

        // Conteo por nivel de prioridad
        $byPriority = [
            'Crítica' => 0,
            'Alta' => 0,
            'Media' => 0,
            'Baja' => 0,
        ];

        $priorityCounts = (clone $query)
            ->reorder()
            ->join('priorities', 'incidents.priority_id', '=', 'priorities.id')
            ->selectRaw('priorities.name, count(*) as count')
            ->groupBy('priorities.name')
            ->pluck('count', 'name')
            ->toArray();

        foreach ($priorityCounts as $name => $count) {
            $byPriority[$name] = (int) $count;
        }

        // Conteo por subsistema / categoría
        $byCategory = (clone $query)
            ->reorder()
            ->join('categories', 'incidents.category_id', '=', 'categories.id')
            ->selectRaw('categories.name, count(*) as count')
            ->groupBy('categories.name')
            ->pluck('count', 'name')
            ->toArray();

        // Tasa de resolución efectiva (Resueltas + Cerradas / Total)
        $completed = ($byStatus['RESUELTA'] ?? 0) + ($byStatus['CERRADA'] ?? 0);
        $resolutionRate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;

        // MTTR Promedio (en minutos)
        $resolvedRecords = (clone $query)
            ->reorder()
            ->whereNotNull('incidents.resolved_at')
            ->whereNotNull('incidents.created_at')
            ->get(['incidents.created_at', 'incidents.resolved_at']);

        if ($resolvedRecords->isNotEmpty()) {
            $totalMinutes = $resolvedRecords->sum(function ($inc) {
                return Carbon::parse($inc->created_at)->diffInMinutes(Carbon::parse($inc->resolved_at));
            });
            $avgMinutes = (int) round($totalMinutes / $resolvedRecords->count());
        } else {
            $avgMinutes = 0;
        }

        $mttrFormatted = $this->formatMinutes($avgMinutes);

        return [
            'total' => $total,
            'by_status' => $byStatus,
            'by_priority' => $byPriority,
            'by_category' => $byCategory,
            'completed' => $completed,
            'resolution_rate' => $resolutionRate,
            'avg_resolution_time_minutes' => $avgMinutes,
            'avg_resolution_time_formatted' => $mttrFormatted,
        ];
    }

    /**
     * Formatea minutos a representación legible tipo "Xh Ym" o "X min".
     */
    protected function formatMinutes(int $minutes): string
    {
        if ($minutes <= 0) {
            return 'N/D';
        }

        if ($minutes < 60) {
            return "{$minutes} min";
        }

        $hours = intdiv($minutes, 60);
        $rem = $minutes % 60;

        return $rem > 0 ? "{$hours}h {$rem}m" : "{$hours}h";
    }

    /**
     * Genera un folio único para el documento de reporte emitido.
     */
    public function generateReportFolio(): string
    {
        return 'REP-'.Carbon::now(config('app.timezone', 'America/Mexico_City'))->format('Ymd').'-'.strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
    }

    /**
     * Genera la instancia DomPDF para exportación institucional.
     * Permite exportar sobre filtros o sobre una selección explícita de incidencias (1 ficha por hoja).
     *
     * @param  array<string, mixed>  $filters
     * @param  array<int|string>|null  $selectedIds
     */
    public function generatePdf(array $filters, User $requester, ?string $reportFolio = null, ?array $selectedIds = null): DomPDFInstance
    {
        $query = $this->buildQuery($filters, $selectedIds, $requester);
        $summary = $this->calculateSummary($query);
        $incidents = $query->with([
            'category',
            'priority',
            'status',
            'reportedBy.role',
            'assignedTo.role',
            'histories.user.role',
            'attachments.user',
        ])->get();

        $reportFolio = $reportFolio ?? $this->generateReportFolio();
        $generatedAt = Carbon::now(config('app.timezone', 'America/Mexico_City'))->format('d/m/Y H:i:s');

        // Mapeo legible de filtros aplicados
        $appliedFilters = $this->getFilterLabels($filters);
        if (! empty($selectedIds)) {
            $appliedFilters['Selección'] = "{$incidents->count()} incidencias seleccionadas";
        }

        // Codificación de logotipos institucionales para renderizado nítido en Dompdf
        $gtsLogoPath = public_path('images/gts-logo.png');
        $aifaLogoPath = public_path('images/aifa-logo.png');
        $gtsLogoBase64 = file_exists($gtsLogoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($gtsLogoPath)) : '';
        $aifaLogoBase64 = file_exists($aifaLogoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($aifaLogoPath)) : '';

        /** @var DomPDFInstance $pdf */
        $pdf = Pdf::loadView('reports.pdf', [
            'incidents' => $incidents,
            'summary' => $summary,
            'filters' => $filters,
            'appliedFilters' => $appliedFilters,
            'requester' => $requester,
            'reportFolio' => $reportFolio,
            'generatedAt' => $generatedAt,
            'gtsLogoBase64' => $gtsLogoBase64,
            'aifaLogoBase64' => $aifaLogoBase64,
        ]);

        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption('isPhpEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);

        return $pdf;
    }

    /**
     * Resuelve etiquetas legibles para los filtros aplicados en el encabezado del reporte.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, string>
     */
    public function getFilterLabels(array $filters): array
    {
        $labels = [];

        if (! empty($filters['folio'])) {
            $labels['Folio Específico'] = strtoupper($filters['folio']);
        }

        if (! empty($filters['period_preset'])) {
            $labels['Período'] = match ($filters['period_preset']) {
                'today' => 'Turno de Hoy',
                'last_7_days', 'week' => 'Últimos 7 Días',
                'this_month', 'month' => 'Mes en Curso',
                default => ucfirst($filters['period_preset']),
            };
        }

        if (! empty($filters['date_from']) || ! empty($filters['date_to'])) {
            $from = $filters['date_from'] ?? 'Inicio histórico';
            $to = $filters['date_to'] ?? 'Fecha actual';
            $labels['Rango Manual'] = "{$from} al {$to}";
        }

        if (! empty($filters['status_id'])) {
            $status = Status::find($filters['status_id']);
            if ($status) {
                $labels['Estado'] = $status->name;
            }
        }

        if (! empty($filters['priority_id'])) {
            $priority = Priority::find($filters['priority_id']);
            if ($priority) {
                $labels['Prioridad'] = $priority->name;
            }
        }

        if (! empty($filters['category_id'])) {
            $category = Category::find($filters['category_id']);
            if ($category) {
                $labels['Subsistema'] = $category->name;
            }
        }

        if (! empty($filters['assigned_to'])) {
            $user = User::find($filters['assigned_to']);
            if ($user) {
                $labels['Especialista GTS'] = $user->name;
            }
        }

        if (! empty($filters['reported_by'])) {
            $user = User::find($filters['reported_by']);
            if ($user) {
                $labels['Reportante APOC'] = $user->name;
            }
        }

        if (! empty($filters['search'])) {
            $labels['Término'] = "\"{$filters['search']}\"";
        }

        if (empty($labels)) {
            $labels['Criterio'] = 'Todas las incidencias (Sin filtros)';
        }

        return $labels;
    }
}
