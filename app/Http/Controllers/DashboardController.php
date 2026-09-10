<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
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
        abort_if(!$request->user()->is_active, 403, 'Usuario inactivo.');

        $period = in_array($request->query('period'), ['today', '24h', 'week'], true)
            ? $request->query('period')
            : 'today';

        $metrics = $this->dashboardService->getMetrics($period);
        $recentIncidents = $this->dashboardService->getRecentIncidents(6);
        $immediateAttention = $this->dashboardService->getImmediateAttentionQueue(3);
        $distribution = $this->dashboardService->getCategoryDistribution();
        $activeTechnicians = $this->dashboardService->getActiveTechniciansCount();
        $currentShift = $this->dashboardService->getCurrentShift();

        return view('dashboard', [
            'metrics' => $metrics,
            'recentIncidents' => $recentIncidents,
            'immediateAttention' => $immediateAttention,
            'distribution' => $distribution,
            'activeTechnicians' => $activeTechnicians,
            'currentShift' => $currentShift,
        ]);
    }
}

