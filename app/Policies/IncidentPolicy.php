<?php

namespace App\Policies;

use App\Models\Incident;
use App\Models\User;

class IncidentPolicy
{
    /**
     * Determina si el usuario puede listar incidencias.
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Determina si el usuario puede consultar el expediente de una incidencia.
     */
    public function view(User $user, Incident $incident): bool
    {
        return $user->is_active;
    }

    /**
     * Determina si el usuario puede crear/despachar una nueva incidencia.
     * Solo APOC y Administrador tienen la facultad de abrir tickets.
     */
    public function create(User $user): bool
    {
        return $user->is_active && ($user->isApoc() || $user->isAdmin());
    }

    /**
     * Determina si el usuario puede asignar o reasignar un técnico a la incidencia.
     * Solo GTS y Administrador pueden gestionar asignaciones técnicas.
     * La incidencia no debe estar cerrada.
     */
    public function assign(User $user, Incident $incident): bool
    {
        if (!$user->is_active) {
            return false;
        }

        $isAuthorizedRole = $user->isGts() || $user->isAdmin();
        $isAssignableStatus = in_array($incident->status->code, ['NUEVA', 'ASIGNADA', 'EN_ATENCION'], true);

        return $isAuthorizedRole && $isAssignableStatus;
    }

    /**
     * Determina si el usuario puede iniciar la atención en campo o consola (EN ATENCIÓN).
     * Requiere rol GTS o Admin y que esté previamente ASIGNADA (o autoasignación desde NUEVA).
     */
    public function startAttention(User $user, Incident $incident): bool
    {
        if (!$user->is_active) {
            return false;
        }

        $isAuthorizedRole = $user->isGts() || $user->isAdmin();
        $isEligibleStatus = in_array($incident->status->code, ['NUEVA', 'ASIGNADA'], true);

        return $isAuthorizedRole && $isEligibleStatus;
    }

    /**
     * Determina si el usuario puede agregar notas técnicas de campo.
     * Solo personal GTS o Admin cuando la incidencia está activamente EN ATENCIÓN.
     */
    public function addNote(User $user, Incident $incident): bool
    {
        if (!$user->is_active) {
            return false;
        }

        return ($user->isGts() || $user->isAdmin()) && $incident->status->code === 'EN_ATENCION';
    }

    /**
     * Determina si el usuario puede marcar la incidencia como RESUELTA.
     * Segregación de funciones: Solo personal técnico GTS o Admin.
     * Requiere que la incidencia esté en estado EN ATENCIÓN.
     */
    public function resolve(User $user, Incident $incident): bool
    {
        if (!$user->is_active) {
            return false;
        }

        return ($user->isGts() || $user->isAdmin()) && $incident->status->code === 'EN_ATENCION';
    }

    /**
     * Determina si el usuario puede rechazar la solución y devolver a atención técnica.
     * Solo personal APOC o Admin cuando la incidencia está en estado RESUELTA.
     */
    public function rejectResolution(User $user, Incident $incident): bool
    {
        if (!$user->is_active) {
            return false;
        }

        return ($user->isApoc() || $user->isAdmin()) && $incident->status->code === 'RESUELTA';
    }

    /**
     * Determina si el usuario puede dar el visto bueno y CERRAR la incidencia.
     * Segregación de funciones estricta: GTS no puede auto-aprobarse el cierre.
     * Solo APOC o Admin pueden cerrar un ticket que esté previamente RESUELTO.
     */
    public function close(User $user, Incident $incident): bool
    {
        if (!$user->is_active) {
            return false;
        }

        return ($user->isApoc() || $user->isAdmin()) && $incident->status->code === 'RESUELTA';
    }

    /**
     * Determina si el usuario puede descargar o visualizar archivos adjuntos.
     */
    public function downloadAttachment(User $user, Incident $incident): bool
    {
        return $user->is_active;
    }

    /**
     * Eliminación de incidencias prohibida por normativa aeronáutica.
     */
    public function delete(User $user, Incident $incident): bool
    {
        return false;
    }
}
