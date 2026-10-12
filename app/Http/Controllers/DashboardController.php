<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    /**
     * Muestra el Dashboard Operativo táctico de APOC / GTS (RF-07).
     */
    public function index(Request $request): View
    {
        abort_if(! $request->user()->is_active, 403, 'Usuario inactivo.');

        $period = in_array($request->query('period'), ['today', '24h', 'week'], true)
            ? $request->query('period')
            : 'today';

        $user = $request->user();

        $metrics = $this->dashboardService->getMetrics($period, $user);
        $recentIncidents = $this->dashboardService->getRecentIncidents(6, $user);
        $immediateAttention = $this->dashboardService->getImmediateAttentionQueue(3, $user);
        $distribution = $this->dashboardService->getCategoryDistribution($user);
        $activeTechnicians = $this->dashboardService->getActiveTechniciansCount($user);
        $currentShift = $this->dashboardService->getCurrentShift();
        $prioritiesSummary = $this->dashboardService->getPrioritiesBreakdown($user);
        $recentActivities = $this->dashboardService->getRecentActivities(4, $user);
        $systemsHealth = $this->dashboardService->getSystemsHealth();

        return view('dashboard', [
            'metrics' => $metrics,
            'recentIncidents' => $recentIncidents,
            'immediateAttention' => $immediateAttention,
            'distribution' => $distribution,
            'activeTechnicians' => $activeTechnicians,
            'currentShift' => $currentShift,
            'prioritiesSummary' => $prioritiesSummary,
            'recentActivities' => $recentActivities,
            'systemsHealth' => $systemsHealth,
        ]);
    }

    /**
     * Retorna las últimas incidencias en tiempo real con optimización de ETag/304,
     * respetando estrictamente los alcances operativos y la matriz de visibilidad del usuario.
     */
    public function recentIncidents(Request $request): Response|JsonResponse|View
    {
        abort_if(! $request->user()->is_active, 403, 'Usuario inactivo.');

        $user = $request->user();
        $recentIncidents = $this->dashboardService->getRecentIncidents(6, $user);

        // Generar ETag determinista basado en los IDs y timestamps de actualización
        $etagSeed = $recentIncidents->map(fn ($inc) => "{$inc->id}:{$inc->updated_at?->timestamp}")->join('|');
        $etag = '"' . md5($etagSeed . ':' . ($user->role_id ?? 'none')) . '"';

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304)->header('ETag', $etag);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'incidents' => $recentIncidents->take(5)->map(function ($inc) {
                    return [
                        'id' => $inc->id,
                        'folio' => $inc->folio,
                        'show_url' => route('incidents.show', $inc->folio),
                        'created_at_formatted' => $inc->created_at->format('d/m/Y H:i'),
                        'category_name' => $inc->category?->name ?? 'CCTV',
                        'category_slug' => $inc->category?->slug ?? 'cctv',
                        'status' => [
                            'code' => $inc->status?->code ?? 'NUEVA',
                            'name' => $inc->status?->name ?? 'Nueva',
                        ],
                        'priority' => [
                            'slug' => $inc->priority?->slug ?? 'media',
                            'name' => $inc->priority?->name ?? 'Media',
                        ],
                        'origin_badge' => $inc->origin_badge,
                    ];
                }),
                'timestamp' => now()->toIso8601String(),
            ])
            ->header('ETag', $etag)
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache');
        }

        return response()
            ->view('dashboard.partials.recent-incidents-rows', [
                'recentIncidents' => $recentIncidents,
            ])
            ->header('ETag', $etag)
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache');
    }
}
