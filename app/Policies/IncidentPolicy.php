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
     * APOC-CCO y APOC-CSA pueden consultar incidencias pertenecientes a su mismo rol/ámbito operativo.
     * GTS y Administrador tienen visibilidad global.
     */
    public function view(User $user, Incident $incident): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdmin() || $user->isGts()) {
            return true;
        }

        if ($user->isApocCco()) {
            return $incident->isFromCco();
        }

        if ($user->isApocCsa()) {
            return $incident->isFromCsa();
        }

        if ($user->isApoc()) {
            return (int) $incident->reported_by_user_id === (int) $user->id;
        }

        return false;
    }

    /**
     * Determina si el usuario puede crear/despachar una nueva incidencia.
     * APOC, GTS y Administrador tienen la facultad de abrir tickets.
     */
    public function create(User $user): bool
    {
        return $user->is_active && ($user->isApoc() || $user->isGts() || $user->isAdmin());
    }

    /**
     * Determina si el usuario puede asignar o reasignar un técnico a la incidencia.
     * Solo GTS y Administrador pueden gestionar asignaciones técnicas.
     * La incidencia no debe estar cerrada.
     */
    public function assign(User $user, Incident $incident): bool
    {
        if (! $user->is_active) {
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
        if (! $user->is_active) {
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
        if (! $user->is_active) {
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
        if (! $user->is_active) {
            return false;
        }

        return ($user->isGts() || $user->isAdmin()) && $incident->status->code === 'EN_ATENCION';
    }

    /**
     * Determina si el usuario puede rechazar la solución y devolver a atención técnica.
     * Solo personal APOC o Admin cuando la incidencia está en estado RESUELTA y tienen acceso al ticket.
     */
    public function rejectResolution(User $user, Incident $incident): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if (! $this->view($user, $incident)) {
            return false;
        }

        return ($user->isApoc() || $user->isAdmin()) && $incident->status->code === 'RESUELTA';
    }

    /**
     * Determina si el usuario puede dar el visto bueno y CERRAR la incidencia.
     * Permitido a personal APOC (respetando confidencialidad CCO/CSA), GTS y Administrador
     * cuando la incidencia está previamente en estado RESUELTA.
     */
    public function close(User $user, Incident $incident): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if (! $this->view($user, $incident)) {
            return false;
        }

        return ($user->isApoc() || $user->isGts() || $user->isAdmin()) && $incident->status->code === 'RESUELTA';
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
     * Permitido a usuarios activos cuando la incidencia no está cerrada e inmutable y tienen acceso al ticket.
     */
    public function uploadAttachment(User $user, Incident $incident): bool
    {
        return $this->view($user, $incident) && $incident->status->code !== 'CERRADA';
    }

    /**
     * Determina si el usuario puede consultar la bitácora pericial de auditoría.
     * Acceso reservado exclusivamente al Administrador del Sistema.
     */
    public function viewAudit(User $user): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    /**
     * Determina si el usuario puede eliminar definitivamente una incidencia.
     * Exclusivo para el Administrador del Sistema bajo confirmación y auditoría pericial (Fase 4).
     */
    public function delete(User $user, Incident $incident): bool
    {
        return $user->is_active && $user->isAdmin();
    }
}
