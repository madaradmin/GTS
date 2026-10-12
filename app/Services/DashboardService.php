<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Incident;
use App\Models\IncidentHistory;
use App\Models\Priority;
use App\Models\Status;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class DashboardService
{
    /**
     * Calcula las métricas operacionales del turno y período seleccionado para el APOC y GTS.
     *
     * @param  string  $period  'today' | '24h' | 'week'
     * @return array<string, mixed>
     */
    /**
     * Calcula las métricas operacionales del turno y período seleccionado para el APOC y GTS.
     *
     * @param  string  $period  'today' | '24h' | 'week'
     * @return array<string, mixed>
     */
    public function getMetrics(string $period = 'today', ?User $user = null): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($period);

        $baseIncident = Incident::query()->forUserScope($user);

        // 1. Incidencias Abiertas (Activas en curso)
        $openQuery = (clone $baseIncident)->whereHas('status', function ($q) {
            $q->whereIn('code', ['NUEVA', 'ASIGNADA', 'EN_ATENCION']);
        });

        $totalOpen = (clone $openQuery)->count();
        $openCritical = (clone $openQuery)->whereHas('priority', fn ($q) => $q->where('slug', 'critica'))->count();
        $openHigh = (clone $openQuery)->whereHas('priority', fn ($q) => $q->where('slug', 'alta'))->count();

        // 2. Indicadores Operacionales Principales desde PostgreSQL
        $total = (clone $baseIncident)->count();
        $new = (clone $baseIncident)->whereHas('status', fn ($q) => $q->where('code', 'NUEVA'))->count();
        $inProcess = (clone $baseIncident)->whereHas('status', fn ($q) => $q->whereIn('code', ['ASIGNADA', 'EN_ATENCION']))->count();
        $resolved = (clone $baseIncident)->whereHas('status', fn ($q) => $q->whereIn('code', ['RESUELTA', 'CERRADA']))->count();
        $critical = (clone $openQuery)->whereHas('priority', fn ($q) => $q->where('slug', 'critica'))->count();

        // 3. En Atención Técnica Activa (GTS en consola o campo)
        $inAttention = (clone $baseIncident)->whereHas('status', fn ($q) => $q->where('code', 'EN_ATENCION'))->count();

        // 4. Resueltas en el período y pendientes de VoBo por APOC
        $resolvedInPeriod = (clone $baseIncident)->whereHas('status', fn ($q) => $q->whereIn('code', ['RESUELTA', 'CERRADA']))
            ->whereBetween('resolved_at', [$startDate, $endDate])
            ->count();

        $pendingVoBo = (clone $baseIncident)->whereHas('status', fn ($q) => $q->where('code', 'RESUELTA'))->count();

        // 5. Tiempo Promedio de Atención Técnica / MTTR (en minutos)
        $resolvedIncidents = (clone $baseIncident)->whereNotNull('resolved_at')
            ->whereNotNull('created_at')
            ->whereBetween('resolved_at', [$startDate, $endDate])
            ->get();

        if ($resolvedIncidents->isNotEmpty()) {
            $totalMinutes = $resolvedIncidents->sum(function (Incident $inc) {
                return $inc->created_at->diffInMinutes($inc->resolved_at);
            });
            $mttrMinutes = (int) round($totalMinutes / $resolvedIncidents->count());
        } else {
            // Fallback histórico general si no hay resueltas en la ventana actual
            $allResolved = (clone $baseIncident)->whereNotNull('resolved_at')->take(20)->get();
            if ($allResolved->isNotEmpty()) {
                $totalMinutes = $allResolved->sum(fn (Incident $i) => $i->created_at->diffInMinutes($i->resolved_at));
                $mttrMinutes = (int) round($totalMinutes / $allResolved->count());
            } else {
                $mttrMinutes = 18; // Promedio estándar referencial AIFA
            }
        }

        $mttrWithinTarget = $mttrMinutes <= 25; // SLA objetivo AIFA (< 25 min)

        return [
            'period' => $period,
            'total' => $total,
            'new' => $new,
            'in_process' => $inProcess,
            'resolved' => $resolved,
            'critical' => $critical,
            'total_open' => $totalOpen,
            'open_critical' => $openCritical,
            'open_high' => $openHigh,
            'in_attention' => $inAttention,
            'resolved_in_period' => $resolvedInPeriod,
            'pending_vobo' => $pendingVoBo,
            'mttr_minutes' => $mttrMinutes,
            'mttr_within_target' => $mttrWithinTarget,
        ];
    }

    /**
     * Obtiene el listado de incidencias prioritarias y recientes (Columna Izquierda 65%).
     */
    public function getRecentIncidents(int $limit = 6, ?User $user = null): Collection
    {
        $query = Incident::with([
            'category',
            'priority',
            'status',
            'assignedTo',
            'reportedBy.role',
        ]);

        $query->forUserScope($user);

        return $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->take($limit)
            ->get();
    }

    /**
     * Obtiene la cola de atención inmediata con tickets críticos o altos activos (Columna Derecha 35%).
     */
    public function getImmediateAttentionQueue(int $limit = 3, ?User $user = null): Collection
    {
        $query = Incident::with([
            'category',
            'priority',
            'status',
            'assignedTo',
            'reportedBy.role',
        ])
            ->whereHas('status', function ($q) {
                $q->whereIn('code', ['NUEVA', 'ASIGNADA', 'EN_ATENCION']);
            })
            ->whereHas('priority', function ($q) {
                $q->whereIn('slug', ['critica', 'alta']);
            });

        $query->forUserScope($user);

        return $query->join('priorities', 'incidents.priority_id', '=', 'priorities.id')
            ->orderByDesc('priorities.level')
            ->orderBy('incidents.created_at', 'asc')
            ->select('incidents.*')
            ->take($limit)
            ->get();
    }

    /**
     * Distribución de carga de incidencias activas agrupada por categoría/subsistema tecnológico.
     *
     * @return array<string, mixed>
     */
    public function getCategoryDistribution(?User $user = null): array
    {
        $categories = Category::where('is_active', true)->get();

        $activeQuery = Incident::whereHas('status', function ($q) {
            $q->whereIn('code', ['NUEVA', 'ASIGNADA', 'EN_ATENCION']);
        });

        $activeQuery->forUserScope($user);

        $activeIncidentsTotal = (clone $activeQuery)->count();

        $distribution = [];

        foreach ($categories as $cat) {
            $count = (clone $activeQuery)->where('category_id', $cat->id)->count();

            $percentage = $activeIncidentsTotal > 0
                ? (int) round(($count / $activeIncidentsTotal) * 100)
                : 0;

            $distribution[] = [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'icon' => $cat->icon ?? 'category',
                'color' => $cat->color ?? '#06B6D4',
                'count' => $count,
                'percentage' => $percentage,
            ];
        }

        return [
            'total_active' => $activeIncidentsTotal,
            'categories' => $distribution,
        ];
    }

    /**
     * Retorna el número de especialistas técnicos GTS activos en plataforma.
     */
    public function getActiveTechniciansCount(?User $user = null): int
    {
        if ($user && ! $user->isAdmin()) {
            $assignable = $user->assignableRoleIds();
            if (! empty($assignable)) {
                return User::whereIn('role_id', $assignable)
                    ->where('is_active', true)
                    ->count();
            }
        }

        return User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))
            ->where('is_active', true)
            ->count();
    }

    /**
     * Retorna los datos del turno operacional actual en el AIFA.
     */
    public function getCurrentShift(): array
    {
        $hour = (int) now()->format('H');

        if ($hour >= 7 && $hour < 19) {
            return [
                'name' => 'Guardia A (07:00 - 19:00)',
                'label' => 'Diurna',
                'type' => 'A',
            ];
        }

        return [
            'name' => 'Guardia B (19:00 - 07:00)',
            'label' => 'Nocturna',
            'type' => 'B',
        ];
    }

    /**
     * Resuelve el rango temporal de consulta para el filtro del Dashboard.
     */
    protected function resolveDateRange(string $period): array
    {
        return match ($period) {
            '24h' => [now()->subHours(24), now()],
            'week' => [now()->subDays(7)->startOfDay(), now()->endOfDay()],
            default => [now()->startOfDay(), now()->endOfDay()], // 'today'
        };
    }

    /**
     * Obtiene el desglose real de incidencias activas clasificadas por prioridad.
     *
     * @return array<string, mixed>
     */
    public function getPrioritiesBreakdown(?User $user = null): array
    {
        $priorities = Priority::orderBy('level', 'desc')->get();
        $activeStatusIds = Status::whereIn('code', ['NUEVA', 'ASIGNADA', 'EN_ATENCION'])->pluck('id');
        $activeQuery = Incident::whereIn('status_id', $activeStatusIds)->forUserScope($user);

        $totalActive = (clone $activeQuery)->count();

        $items = [];
        foreach ($priorities as $p) {
            $count = (clone $activeQuery)->where('priority_id', $p->id)->count();

            $percentage = $totalActive > 0 ? (int) round(($count / $totalActive) * 100) : 0;

            $items[] = [
                'name' => $p->name,
                'slug' => $p->slug,
                'level' => $p->level,
                'count' => $count,
                'percentage' => $percentage,
            ];
        }

        return [
            'total' => $totalActive,
            'items' => $items,
        ];
    }

    /**
     * Obtiene los últimos eventos periciales de auditoría para la timeline de actividad en vivo.
     */
    public function getRecentActivities(int $limit = 4, ?User $user = null): Collection
    {
        $query = IncidentHistory::with(['incident', 'user']);

        if ($user && ! $user->isAdmin() && ! $user->isGts() && ! $user->hasPermission('incidents.view_all')) {
            $query->whereHas('incident', fn ($q) => $q->forUserScope($user));
        }

        return $query->latest('id')
            ->take($limit)
            ->get();
    }

    /**
     * Monitoreo operacional en tiempo real de la infraestructura tecnológica (Catálogos).
     * Calcula dinámicamente el porcentaje y estado de operatividad de los sistemas registrados.
     *
     * @return array<string, mixed>
     */
    public function getSystemsHealth(): array
    {
        $total = Category::count();
        $active = Category::where('status', 'ACTIVO')->count();

        if ($total === 0) {
            return [
                'total' => 0,
                'active' => 0,
                'percentage' => 0.0,
                'state' => null,
                'state_label' => 'Sin datos',
                'color' => '#707B8A',
                'badge_bg' => 'rgba(112, 123, 138, 0.15)',
                'badge_border' => 'rgba(112, 123, 138, 0.3)',
                'systems' => [],
            ];
        }

        $percentage = (float) round(($active / $total) * 100, 1);

        if ($percentage >= 95.0) {
            $state = 'ÓPTIMO';
            $color = '#20C997';
            $badgeBg = 'rgba(32, 201, 151, 0.15)';
            $badgeBorder = 'rgba(32, 201, 151, 0.3)';
        } elseif ($percentage >= 80.0) {
            $state = 'INTERMITENTE';
            $color = '#E0A21A';
            $badgeBg = 'rgba(224, 162, 26, 0.15)';
            $badgeBorder = 'rgba(224, 162, 26, 0.3)';
        } else {
            $state = 'CRÍTICO';
            $color = '#D83B52';
            $badgeBg = 'rgba(216, 59, 82, 0.15)';
            $badgeBorder = 'rgba(216, 59, 82, 0.3)';
        }

        $categories = Category::orderBy('name')->get();

        $systems = $categories->map(function (Category $cat) {
            $status = $cat->status ?: ($cat->is_active ? 'ACTIVO' : 'INACTIVO');

            $statusLabel = match ($status) {
                'ACTIVO' => 'Operativo',
                'INACTIVO' => 'Inactivo',
                'FUERA DE SERVICIO' => 'Fuera de servicio',
                default => 'Operativo',
            };

            $statusColor = match ($status) {
                'ACTIVO' => '#20C997',
                'INACTIVO' => '#E0A21A',
                'FUERA DE SERVICIO' => '#D83B52',
                default => '#20C997',
            };

            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'code' => $cat->code,
                'icon' => $cat->icon ?: 'hub',
                'status' => $status,
                'status_label' => $statusLabel,
                'status_color' => $statusColor,
            ];
        })->all();

        return [
            'total' => $total,
            'active' => $active,
            'percentage' => $percentage,
            'state' => $state,
            'state_label' => $state,
            'color' => $color,
            'badge_bg' => $badgeBg,
            'badge_border' => $badgeBorder,
            'systems' => $systems,
        ];
    }
}
