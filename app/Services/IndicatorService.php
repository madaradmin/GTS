<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Incident;
use App\Models\Priority;
use App\Models\Status;
use App\Models\User;
use Carbon\Carbon;

class IndicatorService
{
    /**
     * Obtiene el conjunto completo de indicadores analíticos y métricas operacionales
     * basados en consultas reales a PostgreSQL sin datos simulados.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getMetrics(array $filters = [], ?User $user = null): array
    {
        $tz = config('app.timezone', 'America/Mexico_City');
        $preset = $filters['period_preset'] ?? 'week';
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $categoryId = $filters['category_id'] ?? null;

        [$startDate, $endDate, $label] = $this->resolveDateRange($preset, $dateFrom, $dateTo, $tz);

        // 1. Query base con filtros aplicados y visibilidad por ámbito de usuario
        $query = Incident::query()->forUserScope($user);

        if ($startDate && $endDate) {
            $query->whereBetween('incidents.created_at', [$startDate, $endDate]);
        }

        if (! empty($categoryId)) {
            $query->where('incidents.category_id', $categoryId);
        }

        $total = (clone $query)->count();

        // 2. Desglose por Estado Operacional
        $statusCounts = (clone $query)
            ->join('statuses', 'incidents.status_id', '=', 'statuses.id')
            ->selectRaw('statuses.code, statuses.name, statuses.step_order, count(*) as count')
            ->groupBy('statuses.code', 'statuses.name', 'statuses.step_order')
            ->orderBy('statuses.step_order')
            ->get()
            ->keyBy('code');

        $statusesList = Status::orderBy('step_order')->get();
        $byStatus = [];
        foreach ($statusesList as $st) {
            $cnt = isset($statusCounts[$st->code]) ? (int) $statusCounts[$st->code]->count : 0;
            $byStatus[$st->code] = [
                'name' => $st->name,
                'count' => $cnt,
                'percentage' => $total > 0 ? round(($cnt / $total) * 100, 1) : 0,
                'is_terminal' => $st->is_terminal,
            ];
        }

        $openCount = ($byStatus['NUEVA']['count'] ?? 0)
            + ($byStatus['ASIGNADA']['count'] ?? 0)
            + ($byStatus['EN_ATENCION']['count'] ?? 0);

        $resolvedCount = ($byStatus['RESUELTA']['count'] ?? 0)
            + ($byStatus['CERRADA']['count'] ?? 0);

        // 3. Eficiencia de Resolución (%)
        $resolutionRate = $total > 0 ? round(($resolvedCount / $total) * 100, 1) : 0.0;

        // 4. Desglose por Prioridad
        $priorityCounts = (clone $query)
            ->join('priorities', 'incidents.priority_id', '=', 'priorities.id')
            ->selectRaw('priorities.name, priorities.slug, priorities.level, count(*) as count')
            ->groupBy('priorities.name', 'priorities.slug', 'priorities.level')
            ->orderBy('priorities.level')
            ->get()
            ->keyBy('slug');

        $prioritiesList = Priority::orderBy('level')->get();
        $byPriority = [];
        foreach ($prioritiesList as $pr) {
            $cnt = isset($priorityCounts[$pr->slug]) ? (int) $priorityCounts[$pr->slug]->count : 0;
            $byPriority[$pr->slug] = [
                'name' => $pr->name,
                'count' => $cnt,
                'percentage' => $total > 0 ? round(($cnt / $total) * 100, 1) : 0,
                'level' => $pr->level,
                'color' => $this->getPriorityColor($pr->slug),
            ];
        }

        // 5. MTTR (Tiempo Medio de Resolución)
        $resolvedIncidents = (clone $query)
            ->whereNotNull('incidents.resolved_at')
            ->whereNotNull('incidents.created_at')
            ->get(['incidents.created_at', 'incidents.resolved_at']);

        if ($resolvedIncidents->isNotEmpty()) {
            $totalResolutionMinutes = $resolvedIncidents->sum(function ($inc) {
                return Carbon::parse($inc->created_at)->diffInMinutes(Carbon::parse($inc->resolved_at));
            });
            $mttrMinutes = (int) round($totalResolutionMinutes / $resolvedIncidents->count());
            $mttrFormatted = $this->formatMinutes($mttrMinutes);
        } else {
            $mttrMinutes = 0;
            $mttrFormatted = '0 min';
        }

        $mttrTarget = 25; // SLA Objetivo AIFA: < 25 min
        $mttrWithinTarget = $resolvedIncidents->isNotEmpty() && $mttrMinutes <= $mttrTarget;

        // 6. Tiempo de Primera Respuesta / Atención (MTTA)
        $attendedIncidents = (clone $query)
            ->where(function ($q) {
                $q->whereNotNull('incidents.attended_at')
                    ->orWhereNotNull('incidents.assigned_at');
            })
            ->whereNotNull('incidents.created_at')
            ->get(['incidents.created_at', 'incidents.attended_at', 'incidents.assigned_at']);

        if ($attendedIncidents->isNotEmpty()) {
            $totalResponseMinutes = $attendedIncidents->sum(function ($inc) {
                $responseTimestamp = $inc->attended_at ?: $inc->assigned_at;

                return Carbon::parse($inc->created_at)->diffInMinutes(Carbon::parse($responseTimestamp));
            });
            $mttaMinutes = (int) round($totalResponseMinutes / $attendedIncidents->count());
            $mttaFormatted = $this->formatMinutes($mttaMinutes);
        } else {
            $mttaMinutes = 0;
            $mttaFormatted = '0 min';
        }

        $mttaTarget = 10; // SLA Objetivo de primera respuesta: < 10 min
        $mttaWithinTarget = $attendedIncidents->isNotEmpty() && $mttaMinutes <= $mttaTarget;

        // 7. Salud de Infraestructura por Subsistema / Categoría
        $categories = Category::orderBy('name')->get();
        $infrastructureHealth = [];
        $totalAssets = $categories->count();
        $activeAssets = 0;

        foreach ($categories as $cat) {
            $catTotal = (clone $query)->where('category_id', $cat->id)->count();
            $catOpen = (clone $query)->where('category_id', $cat->id)
                ->whereHas('status', fn ($s) => $s->where('is_terminal', false))
                ->count();
            $catResolved = (clone $query)->where('category_id', $cat->id)
                ->whereHas('status', fn ($s) => $s->where('is_terminal', true))
                ->count();

            $isOperational = $cat->status === 'ACTIVO' && $catOpen === 0;
            $isDegraded = $cat->status === 'ACTIVO' && $catOpen > 0;
            $isDown = $cat->status === 'FUERA DE SERVICIO' || $cat->status === 'INACTIVO';

            if ($cat->status === 'ACTIVO') {
                $activeAssets++;
            }

            $infrastructureHealth[] = [
                'id' => $cat->id,
                'name' => $cat->name,
                'code' => $cat->code,
                'slug' => $cat->slug,
                'icon' => $cat->icon ?: 'hub',
                'color' => $cat->color ?: '#3D82C6',
                'location' => $cat->location,
                'status' => $cat->status ?: 'ACTIVO',
                'total_incidents' => $catTotal,
                'open_incidents' => $catOpen,
                'resolved_incidents' => $catResolved,
                'health_state' => $isDown ? 'down' : ($isDegraded ? 'degraded' : 'optimal'),
                'health_label' => $isDown ? 'Fuera de Servicio' : ($isDegraded ? 'Con Incidencias' : 'Óptimo 100%'),
            ];
        }

        $globalAvailability = $totalAssets > 0 ? round(($activeAssets / $totalAssets) * 100, 1) : 100.0;

        return [
            'period_label' => $label,
            'preset' => $preset,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'category_id' => $categoryId,
            'total' => $total,
            'open_count' => $openCount,
            'resolved_count' => $resolvedCount,
            'resolution_rate' => $resolutionRate,
            'by_status' => $byStatus,
            'by_priority' => $byPriority,
            'mttr_minutes' => $mttrMinutes,
            'mttr_formatted' => $mttrFormatted,
            'mttr_target' => $mttrTarget,
            'mttr_within_target' => $mttrWithinTarget,
            'resolved_sample_count' => $resolvedIncidents->count(),
            'mtta_minutes' => $mttaMinutes,
            'mtta_formatted' => $mttaFormatted,
            'mtta_target' => $mttaTarget,
            'mtta_within_target' => $mttaWithinTarget,
            'attended_sample_count' => $attendedIncidents->count(),
            'infrastructure_health' => $infrastructureHealth,
            'global_availability' => $globalAvailability,
            'total_assets' => $totalAssets,
            'active_assets' => $activeAssets,
        ];
    }

    /**
     * Resuelve el rango de fechas basado en preset o valores manuales.
     */
    protected function resolveDateRange(string $preset, ?string $dateFrom, ?string $dateTo, string $tz): array
    {
        if (! empty($dateFrom) && ! empty($dateTo)) {
            return [
                Carbon::parse($dateFrom, $tz)->startOfDay(),
                Carbon::parse($dateTo, $tz)->endOfDay(),
                Carbon::parse($dateFrom)->format('d/m/Y').' - '.Carbon::parse($dateTo)->format('d/m/Y'),
            ];
        }

        return match ($preset) {
            'today' => [
                Carbon::now($tz)->startOfDay(),
                Carbon::now($tz)->endOfDay(),
                'Hoy ('.Carbon::now($tz)->format('d/m/Y').')',
            ],
            'month' => [
                Carbon::now($tz)->startOfMonth(),
                Carbon::now($tz)->endOfMonth(),
                'Mes en curso ('.Carbon::now($tz)->isoFormat('MMMM Y').')',
            ],
            'all' => [
                null,
                null,
                'Histórico Completo',
            ],
            default => [
                Carbon::now($tz)->subDays(7)->startOfDay(),
                Carbon::now($tz)->endOfDay(),
                'Últimos 7 días',
            ],
        };
    }

    /**
     * Formatea una cantidad de minutos en texto legible militar.
     */
    public function formatMinutes(int $minutes): string
    {
        if ($minutes <= 0) {
            return '0 min';
        }

        if ($minutes < 60) {
            return "{$minutes} min";
        }

        $hours = floor($minutes / 60);
        $remaining = $minutes % 60;

        return $remaining > 0 ? "{$hours} h {$remaining} min" : "{$hours} h";
    }

    /**
     * Retorna el color institucional representativo de la prioridad.
     */
    protected function getPriorityColor(string $slug): string
    {
        return match ($slug) {
            'critica' => '#EF4444',
            'alta' => '#F97316',
            'media' => '#10B981',
            'baja' => '#3B82F6',
            default => '#94A3B8',
        };
    }
}
