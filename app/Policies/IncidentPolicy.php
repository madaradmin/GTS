<?php

namespace App\Policies;

use App\Models\Incident;
use App\Models\User;

class IncidentPolicy
{
    /**
     * Determina si el usuario puede listar incidencias en el sistema.
     */
    public function viewAny(User $user): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->isAdmin()
            || $user->hasAnyPermission(['incidents.view_all', 'incidents.view_department', 'incidents.view_assigned'])
            || (! \App\Models\Permission::exists() && ($user->isGts() || $user->isApoc()));
    }

    /**
     * Determina si el usuario puede consultar el expediente de una incidencia.
     * Considera asignación directa, permiso global y alcances operativos inter-rol.
     * La autoría no concede acceso si el usuario carece de permisos de visualización aplicables.
     */
    public function view(User $user, Incident $incident): bool
    {
        if (! $user->is_active) {
            return false;
        }

        // 1. Administrador posee bypass completo
        if ($user->isAdmin()) {
            return true;
        }

        // 2. Comprobar primero si el usuario cuenta con algún permiso de visualización aplicable.
        // Sin permiso de consulta válido, el usuario NO puede ver ninguna incidencia.
        $hasAnyViewPermission = $user->hasAnyPermission(['incidents.view_all', 'incidents.view_department', 'incidents.view_assigned'])
            || (! \App\Models\Permission::exists() && ($user->isGts() || $user->isApoc()));

        if (! $hasAnyViewPermission) {
            return false;
        }

        // 3. Si tiene permiso global para ver todas las incidencias
        if ($user->hasPermission('incidents.view_all')) {
            return true;
        }

        // 4. Si es el autor que reportó la incidencia (con permiso de consulta activo comprobado en paso 2)
        if ((int) $incident->reported_by_user_id === (int) $user->id) {
            return true;
        }

        // 5. Si tiene permiso de incidencias asignadas: sólo puede ver aquellas asignadas a él o a su área responsable
        if ($user->hasPermission('incidents.view_assigned')) {
            if ((int) $incident->assigned_to_user_id === (int) $user->id) {
                return true;
            }
            if ($user->role_id && $incident->assignedAreas()->where('role_id', $user->role_id)->exists()) {
                return true;
            }
        }

        // 6. Si tiene permiso de incidencias de área/departamento
        if ($user->hasPermission('incidents.view_department')) {
            if ($user->role_id && $incident->assignedAreas()->where('role_id', $user->role_id)->exists()) {
                return true;
            }

            // Matriz de alcance operativo inter-rol (canViewRole)
            if ($incident->reportedBy && $incident->reportedBy->role_id) {
                if ($user->canViewRole($incident->reportedBy->role_id)) {
                    return true;
                }
            }

            // Compatibilidad departamental CCO / CSA
            if ($user->isApocCco() && $incident->isFromCco()) {
                return true;
            }

            if ($user->isApocCsa() && $incident->isFromCsa()) {
                return true;
            }
        }

        // 6. Fallback para entornos de pruebas sin seeding de permisos
        if (! \App\Models\Permission::exists()) {
            if ($user->isGts()) {
                return true;
            }
            if ($user->isApoc()) {
                return (int) $incident->reported_by_user_id === (int) $user->id
                    || (int) $incident->assigned_to_user_id === (int) $user->id
                    || ($user->isApocCco() && $incident->isFromCco())
                    || ($user->isApocCsa() && $incident->isFromCsa());
            }
        }

        return false;
    }

    /**
     * Determina si el usuario puede registrar y despachar una nueva incidencia.
     */
    public function create(User $user): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->isAdmin()
            || $user->hasPermission('incidents.create')
            || (! \App\Models\Permission::exists() && ($user->isApoc() || $user->isGts()));
    }

    /**
     * Determina si el usuario puede asignar o reasignar un técnico a la incidencia.
     */
    public function assign(User $user, Incident $incident): bool
    {
        if (! $user->is_active) {
            return false;
        }

        $isAuthorized = $user->isAdmin() 
            || $user->hasPermission('incidents.assign')
            || (! \App\Models\Permission::exists() && $user->isGts());
        $isAssignableStatus = in_array($incident->status->code, ['NUEVA', 'ASIGNADA', 'EN_ATENCION'], true);

        return $isAuthorized && $isAssignableStatus;
    }

    /**
     * Determina si el usuario puede autoasignarse una incidencia abierta.
     */
    public function take(User $user, Incident $incident): bool
    {
        if (! $user->is_active) {
            return false;
        }

        $hasPermission = $user->isAdmin() 
            || $user->hasPermission('incidents.take')
            || (! \App\Models\Permission::exists() && $user->isGts());
        $isAvailableStatus = in_array($incident->status->code, ['NUEVA', 'ASIGNADA'], true);

        return $hasPermission && $isAvailableStatus;
    }

    /**
     * Determina si el usuario puede liberar la asignación técnica de una incidencia.
     */
    public function release(User $user, Incident $incident): bool
    {
        if (! $user->is_active) {
            return false;
        }

        $hasPermission = $user->isAdmin() || $user->hasPermission('incidents.release');
        $isAssignedToUser = (int) $incident->assigned_to_user_id === (int) $user->id || $user->isAdmin();

        return $hasPermission && $isAssignedToUser;
    }

    /**
     * Determina si el usuario puede iniciar la atención en campo o consola (EN ATENCIÓN).
     */
    public function startAttention(User $user, Incident $incident): bool
    {
        if (! $user->is_active) {
            return false;
        }

        $isEligibleStatus = in_array($incident->status->code, ['NUEVA', 'ASIGNADA'], true);
        if (! $isEligibleStatus) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $hasPermission = $user->hasPermission('incidents.attend');

        // Validar alcance de atención técnica sobre el creador de la incidencia
        $canAttendScope = true;
        if ($incident->reportedBy && $incident->reportedBy->role_id) {
            $canAttendScope = $user->canAttendRole($incident->reportedBy->role_id);
        }

        return $hasPermission && $canAttendScope;
    }

    /**
     * Determina si el usuario puede agregar notas técnicas de campo.
     */
    public function addNote(User $user, Incident $incident): bool
    {
        if (! $user->is_active) {
            return false;
        }

        $hasPermission = $user->isAdmin() || $user->hasPermission('incidents.attend');

        return $hasPermission && $incident->status->code === 'EN_ATENCION';
    }

    /**
     * Determina si el usuario puede marcar la incidencia como RESUELTA.
     */
    public function resolve(User $user, Incident $incident): bool
    {
        if (! $user->is_active) {
            return false;
        }

        $hasPermission = $user->isAdmin() 
            || $user->hasPermission('incidents.resolve')
            || (! \App\Models\Permission::exists() && $user->isGts());

        return $hasPermission && $incident->status->code === 'EN_ATENCION';
    }

    /**
     * Determina si el usuario puede rechazar la solución y devolver a atención técnica.
     */
    public function rejectResolution(User $user, Incident $incident): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if (! $this->view($user, $incident)) {
            return false;
        }

        $hasPermission = $user->isAdmin() 
            || $user->hasPermission('incidents.reopen')
            || (! \App\Models\Permission::exists() && $user->isApoc());

        return $hasPermission && $incident->status->code === 'RESUELTA';
    }

    /**
     * Determina si el usuario puede dar el visto bueno y CERRAR la incidencia.
     */
    public function close(User $user, Incident $incident): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if (! $this->view($user, $incident)) {
            return false;
        }

        $hasPermission = $user->isAdmin() 
            || $user->hasPermission('incidents.vobo_close')
            || (! \App\Models\Permission::exists() && ($user->isApoc() || $user->isGts()));

        return $hasPermission && $incident->status->code === 'RESUELTA';
    }

    /**
     * Determina si el usuario puede descargar o visualizar archivos adjuntos.
     */
    public function downloadAttachment(User $user, Incident $incident): bool
    {
        return $this->view($user, $incident);
    }

    /**
     * Determina si el usuario puede cargar nuevas evidencias a la incidencia.
     */
    public function uploadAttachment(User $user, Incident $incident): bool
    {
        return $this->view($user, $incident) && $incident->status->code !== 'CERRADA';
    }

    /**
     * Determina si el usuario puede consultar la bitácora pericial de auditoría.
     */
    public function viewAudit(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->hasPermission('audit.view'));
    }

    /**
     * Determina si el usuario puede eliminar definitivamente una incidencia.
     */
    public function delete(User $user, Incident $incident): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->hasPermission('incidents.delete'));
    }
}
