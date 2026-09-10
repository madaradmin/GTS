<?php

namespace App\Services;

use App\Models\IncidentHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AuditService
{
    /**
     * Registra un evento inmutable en la bitácora de auditoría (Append-Only).
     */
    public function logAction(
        int $incidentId,
        int $userId,
        string $action,
        ?string $fieldName = null,
        ?string $oldValue = null,
        ?string $newValue = null,
        ?string $comment = null
    ): IncidentHistory {
        return IncidentHistory::create([
            'incident_id' => $incidentId,
            'user_id' => $userId,
            'action' => $action,
            'field_name' => $fieldName,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'comment' => $comment,
            'created_at' => now(),
        ]);
    }

    /**
     * Obtiene el listado cronológico de auditoría paginado con filtros periciales.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getGlobalAuditTrail(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return IncidentHistory::query()
            ->with([
                'incident.category',
                'incident.priority',
                'incident.status',
                'user.role',
            ])
            ->filter($filters)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    /**
     * Calcula métricas agregadas de actividad pericial para visualización en dashboard de auditoría.
     *
     * @return array<string, int>
     */
    public function getStatistics(): array
    {
        return [
            'total_events' => IncidentHistory::count(),
            'state_changes' => IncidentHistory::where('action', 'CAMBIO_ESTADO')->count(),
            'technical_notes' => IncidentHistory::where('action', 'NOTA_TECNICA')->count(),
            'resolutions' => IncidentHistory::where('action', 'RESOLUCION')->count(),
            'rejections' => IncidentHistory::where('action', 'RECHAZO_SOLUCION')->count(),
            'closures' => IncidentHistory::where('action', 'CERRADA')->count(),
        ];
    }

    /**
     * Retorna el catálogo tipificado de acciones registradas para selectores de filtrado.
     *
     * @return array<string, string>
     */
    public function getAvailableActions(): array
    {
        return [
            'CREADA' => 'Creación de Expediente',
            'ASIGNACION' => 'Asignación Técnica',
            'CAMBIO_ESTADO' => 'Cambio de Estado',
            'INICIO_ATENCION' => 'Inicio de Atención',
            'NOTA_TECNICA' => 'Nota de Campo',
            'RESOLUCION' => 'Resolución Técnica',
            'RECHAZO_SOLUCION' => 'Rechazo de Solución',
            'CERRADA' => 'Cierre Definitivo (VoBo)',
        ];
    }
}
