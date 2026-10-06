<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\IndicatorService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IndicatorController extends Controller
{
    public function __construct(
        protected IndicatorService $indicatorService
    ) {}

    /**
     * Muestra el panel analítico de métricas e indicadores de rendimiento técnico (MTTR, MTTA, SLA, Eficiencia).
     */
    public function index(Request $request): View
    {
        abort_if(! $request->user()->is_active, 403, 'Usuario inactivo.');

        $filters = $request->only([
            'period_preset',
            'date_from',
            'date_to',
            'category_id',
        ]);

        $metrics = $this->indicatorService->getMetrics($filters, $request->user());
        $categories = Category::orderBy('name')->get();

        return view('indicators.index', [
            'metrics' => $metrics,
            'categories' => $categories,
            'filters' => $filters,
        ]);
    }
}
