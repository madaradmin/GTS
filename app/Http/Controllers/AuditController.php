<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Muestra la bitácora global de auditoría e historial pericial de GIT.
     * Acceso autorizado a roles activos APOC, GTS y Administrador (RF-05 / RN-04).
     */
    public function index(Request $request): View
    {
        abort_if(!$request->user()->is_active, 403, 'Usuario inactivo.');

        $filters = $request->only([
            'search',
            'action',
            'user_id',
            'folio',
            'date_from',
            'date_to',
        ]);

        $histories = $this->auditService->getGlobalAuditTrail($filters, 20);
        $statistics = $this->auditService->getStatistics();
        $actions = $this->auditService->getAvailableActions();
        $users = User::with('role')->orderBy('name')->get();

        return view('audit.index', [
            'histories' => $histories,
            'statistics' => $statistics,
            'actions' => $actions,
            'users' => $users,
            'filters' => $filters,
        ]);
    }
}

