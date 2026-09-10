<?php

namespace App\Services;

use App\Models\IncidentHistory;

class AuditService
{
    /**
     * Registra un evento inmutable en la bitácora de auditoría.
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
}
