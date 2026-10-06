<?php

namespace App\Services;

use App\Models\Incident;
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
     * Obtiene el conjunto completo de incidencias para supervisión y auditoría administrativa.
     * Consulta todas las incidencias del sistema sin scopes restrictivos de estado ni de usuario.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getAllIncidentsAudit(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Incident::query()
            ->with([
                'category',
                'priority',
                'status',
                'reportedBy.role',
                'assignedTo.role',
            ]);

        // 1. Búsqueda por término (folio, título, ubicación, descripción, nombres)
        if (! empty($filters['search'])) {
            $term = trim($filters['search']);
            $like = $query->getConnection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
            $query->where(function ($sub) use ($term, $like) {
                $sub->where('folio', $like, "%{$term}%")
                    ->orWhere('title', $like, "%{$term}%")
                    ->orWhere('location', $like, "%{$term}%")
                    ->orWhere('description', $like, "%{$term}%")
                    ->orWhereHas('category', fn ($c) => $c->where('name', $like, "%{$term}%"))
                    ->orWhereHas('status', fn ($s) => $s->where('name', $like, "%{$term}%"))
                    ->orWhereHas('reportedBy', fn ($u) => $u->where('name', $like, "%{$term}%")->orWhere('badge_number', $like, "%{$term}%"))
                    ->orWhereHas('assignedTo', fn ($u) => $u->where('name', $like, "%{$term}%"));
            });
        }

        // 2. Filtro por Folio específico
        if (! empty($filters['folio'])) {
            $query->where('folio', trim($filters['folio']));
        }

        // 3. Filtro por Estado (opcional, sin restringir por defecto)
        if (! empty($filters['status_id'])) {
            $query->where('status_id', $filters['status_id']);
        }

        // 4. Filtro por Categoría / Subsistema
        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // 5. Filtro por Prioridad
        if (! empty($filters['priority_id'])) {
            $query->where('priority_id', $filters['priority_id']);
        }

        // 6. Filtro por Acción Auditada (si se filtra por acción, traer incidencias con dicho evento)
        if (! empty($filters['action'])) {
            $query->whereHas('histories', fn ($h) => $h->where('action', $filters['action']));
        }

        // 7. Filtro por Usuario (reportado por o asignado a)
        if (! empty($filters['user_id'])) {
            $userId = $filters['user_id'];
            $query->where(function ($sub) use ($userId) {
                $sub->where('reported_by_user_id', $userId)
                    ->orWhere('assigned_to_user_id', $userId);
            });
        }

        // 8. Filtro por Rango de Fechas (Desde)
        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from'].' 00:00:00');
        }

        // 9. Filtro por Rango de Fechas (Hasta)
        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to'].' 23:59:59');
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'incidents_page')
            ->withQueryString();
    }

    /**
     * Obtiene el listado cronológico de auditoría paginado con filtros periciales.
     *
     * @param  array<string, mixed>  $filters
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
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Calcula métricas agregadas de actividad pericial para visualización en dashboard de auditoría.
     *
     * @return array<string, int>
     */
    public function getStatistics(): array
    {
        return [
            'total_incidents' => Incident::count(),
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
