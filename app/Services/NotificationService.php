<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\User;
use App\Notifications\IncidentNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Notifica la creación y despacho de una nueva incidencia.
     * - APOC (Reportante): Confirmación de registro exitoso.
     * - Agentes GTS: Alerta de nueva incidencia disponible para atención.
     * - Administradores: Notificación informativa de apertura.
     */
    public function notifyIncidentCreated(Incident $incident): void
    {
        try {
            $incident->loadMissing(['category', 'priority', 'reportedBy']);
            $isCritical = $incident->priority?->slug === 'critica';
            $actionUrl = route('incidents.show', $incident->folio);

            // 1. Notificación al Operador APOC reportante
            if ($incident->reportedBy && $incident->reportedBy->is_active) {
                $incident->reportedBy->notify(new IncidentNotification(
                    type: 'incident_created',
                    title: 'Incidencia Registrada',
                    message: "La incidencia {$incident->folio} ha sido despachada exitosamente a GTS.",
                    incidentId: $incident->id,
                    folio: $incident->folio,
                    actionUrl: $actionUrl,
                    icon: 'task_alt',
                    level: 'success'
                ));
            }

            // 2. Notificación a los Agentes GTS activos
            $gtsAgents = User::where('is_active', true)
                ->whereHas('role', fn ($q) => $q->where('slug', 'gts'))
                ->when($incident->reported_by_user_id, fn ($q, $id) => $q->where('id', '!=', $id))
                ->get();

            $gtsNotification = new IncidentNotification(
                type: 'new_incident_available',
                title: $isCritical ? '⚠️ Incidencia Crítica Disponible' : 'Nueva Incidencia Disponible',
                message: "Se registró {$incident->folio} en {$incident->location} ({$incident->category?->name}).",
                incidentId: $incident->id,
                folio: $incident->folio,
                actionUrl: $actionUrl,
                icon: $isCritical ? 'emergency' : 'notification_important',
                level: $isCritical ? 'critical' : 'warning'
            );

            foreach ($gtsAgents as $agent) {
                $agent->notify(clone $gtsNotification);
            }

            // 3. Notificación a los Administradores
            $admins = User::where('is_active', true)
                ->whereHas('role', fn ($q) => $q->where('slug', 'admin'))
                ->when($incident->reported_by_user_id, fn ($q, $id) => $q->where('id', '!=', $id))
                ->get();

            $adminNotification = new IncidentNotification(
                type: 'incident_created_admin',
                title: "Nueva Incidencia: {$incident->folio}",
                message: "Apertura de ticket por {$incident->reportedBy?->name} ({$incident->category?->name}).",
                incidentId: $incident->id,
                folio: $incident->folio,
                actionUrl: $actionUrl,
                icon: 'fiber_new',
                level: $isCritical ? 'critical' : 'info'
            );

            foreach ($admins as $admin) {
                $admin->notify(clone $adminNotification);
            }
        } catch (\Throwable $e) {
            Log::error("Error despachando notificaciones de creación para {$incident->folio}: ".$e->getMessage());
        }
    }

    /**
     * Notifica la asignación o reasignación de un técnico GTS.
     * - Técnico GTS asignado: Responsabilidad del ticket.
     * - APOC (Reportante): Información del técnico responsable.
     * - Administrador: Registro si es reasignación o caso crítico.
     */
    public function notifyIncidentAssigned(Incident $incident, User $assigner, User $technician, bool $isReassignment = false): void
    {
        try {
            $incident->loadMissing(['reportedBy', 'priority']);
            $actionUrl = route('incidents.show', $incident->folio);

            // 1. Notificación al Técnico GTS asignado (solo si no es quien se autoasignó)
            if ($assigner->id !== $technician->id && $technician->is_active) {
                $technician->notify(new IncidentNotification(
                    type: $isReassignment ? 'incident_reassigned' : 'incident_assigned',
                    title: $isReassignment ? 'Incidencia Reasignada' : 'Incidencia Asignada',
                    message: "Has sido asignado como responsable técnico de {$incident->folio} ({$incident->location}).",
                    incidentId: $incident->id,
                    folio: $incident->folio,
                    actionUrl: $actionUrl,
                    icon: 'assignment_ind',
                    level: 'info'
                ));
            }

            // 2. Notificación al APOC reportante
            if ($incident->reportedBy && $incident->reportedBy->is_active && $incident->reported_by_user_id !== $assigner->id) {
                $incident->reportedBy->notify(new IncidentNotification(
                    type: 'incident_assigned_reporter',
                    title: 'Especialista Asignado',
                    message: "La incidencia {$incident->folio} fue asignada a {$technician->name} para su atención.",
                    incidentId: $incident->id,
                    folio: $incident->folio,
                    actionUrl: $actionUrl,
                    icon: 'engineering',
                    level: 'info'
                ));
            }

            // 3. Notificación a Administradores si es reasignación o crítica
            if ($isReassignment || $incident->priority?->slug === 'critica') {
                $admins = User::where('is_active', true)
                    ->whereHas('role', fn ($q) => $q->where('slug', 'admin'))
                    ->whereNotIn('id', [$assigner->id, $technician->id, $incident->reported_by_user_id])
                    ->get();

                $adminNotification = new IncidentNotification(
                    type: 'incident_assignment_admin',
                    title: "Asignación Técnica: {$incident->folio}",
                    message: "Incidencia {$incident->folio} asignada a {$technician->name} por {$assigner->name}.",
                    incidentId: $incident->id,
                    folio: $incident->folio,
                    actionUrl: $actionUrl,
                    icon: 'swap_horiz',
                    level: 'info'
                );

                foreach ($admins as $admin) {
                    $admin->notify(clone $adminNotification);
                }
            }
        } catch (\Throwable $e) {
            Log::error("Error despachando notificaciones de asignación para {$incident->folio}: ".$e->getMessage());
        }
    }

    /**
     * Notifica la asignación o reasignación a un especialista o área externa (ej. TICS).
     */
    public function notifyIncidentAssignedToSpecialist(Incident $incident, User $assigner, string $specialist = 'TICS', bool $isReassignment = false): void
    {
        try {
            $incident->loadMissing(['reportedBy', 'priority']);
            $actionUrl = route('incidents.show', $incident->folio);

            // 1. Notificación al APOC reportante
            if ($incident->reportedBy && $incident->reportedBy->is_active && $incident->reported_by_user_id !== $assigner->id) {
                $incident->reportedBy->notify(new IncidentNotification(
                    type: 'incident_assigned_reporter',
                    title: 'Especialista Asignado',
                    message: "La incidencia {$incident->folio} fue asignada al especialista {$specialist} para su atención.",
                    incidentId: $incident->id,
                    folio: $incident->folio,
                    actionUrl: $actionUrl,
                    icon: 'engineering',
                    level: 'info'
                ));
            }

            // 2. Notificación a Administradores si es reasignación o crítica
            if ($isReassignment || $incident->priority?->slug === 'critica') {
                $admins = User::where('is_active', true)
                    ->whereHas('role', fn ($q) => $q->where('slug', 'admin'))
                    ->whereNotIn('id', [$assigner->id, $incident->reported_by_user_id])
                    ->get();

                $adminNotification = new IncidentNotification(
                    type: 'incident_assignment_admin',
                    title: "Asignación Técnica: {$incident->folio}",
                    message: "Incidencia {$incident->folio} asignada al especialista {$specialist} por {$assigner->name}.",
                    incidentId: $incident->id,
                    folio: $incident->folio,
                    actionUrl: $actionUrl,
                    icon: 'swap_horiz',
                    level: 'info'
                );

                foreach ($admins as $admin) {
                    $admin->notify(clone $adminNotification);
                }
            }
        } catch (\Throwable $e) {
            Log::error("Error despachando notificaciones de asignación a {$specialist} para {$incident->folio}: ".$e->getMessage());
        }
    }

    /**
     * Notifica el inicio de la atención técnica en sitio/remota (EN_ATENCION).
     * - APOC: Visibilidad de que el técnico ya está interviniendo la falla.
     */
    public function notifyAttentionStarted(Incident $incident, User $technician): void
    {
        try {
            $incident->loadMissing(['reportedBy']);
            $actionUrl = route('incidents.show', $incident->folio);

            if ($incident->reportedBy && $incident->reportedBy->is_active && $incident->reported_by_user_id !== $technician->id) {
                $incident->reportedBy->notify(new IncidentNotification(
                    type: 'attention_started',
                    title: 'Atención Técnica Iniciada',
                    message: "El especialista {$technician->name} ha comenzado los trabajos en {$incident->folio}.",
                    incidentId: $incident->id,
                    folio: $incident->folio,
                    actionUrl: $actionUrl,
                    icon: 'build',
                    level: 'info'
                ));
            }
        } catch (\Throwable $e) {
            Log::error("Error despachando notificaciones de inicio de atención para {$incident->folio}: ".$e->getMessage());
        }
    }

    /**
     * Notifica una nota técnica o avance en campo.
     * - APOC (Reportante): Actualización de avance.
     */
    public function notifyTechnicalNoteAdded(Incident $incident, User $author, string $note): void
    {
        try {
            $incident->loadMissing(['reportedBy', 'assignedTo']);
            $actionUrl = route('incidents.show', $incident->folio);
            $excerpt = mb_substr($note, 0, 80).(mb_strlen($note) > 80 ? '...' : '');

            // Notificar a APOC reportante
            if ($incident->reportedBy && $incident->reportedBy->is_active && $incident->reported_by_user_id !== $author->id) {
                $incident->reportedBy->notify(new IncidentNotification(
                    type: 'technical_note_added',
                    title: "Nota Técnica en {$incident->folio}",
                    message: "{$author->name}: {$excerpt}",
                    incidentId: $incident->id,
                    folio: $incident->folio,
                    actionUrl: $actionUrl,
                    icon: 'note_alt',
                    level: 'info'
                ));
            }

            // Si el técnico asignado es diferente al autor de la nota
            if ($incident->assignedTo && $incident->assignedTo->is_active && $incident->assigned_to_user_id !== $author->id) {
                $incident->assignedTo->notify(new IncidentNotification(
                    type: 'technical_note_added',
                    title: "Nota Técnica en {$incident->folio}",
                    message: "{$author->name}: {$excerpt}",
                    incidentId: $incident->id,
                    folio: $incident->folio,
                    actionUrl: $actionUrl,
                    icon: 'note_alt',
                    level: 'info'
                ));
            }
        } catch (\Throwable $e) {
            Log::error("Error despachando notificaciones de nota técnica para {$incident->folio}: ".$e->getMessage());
        }
    }

    /**
     * Notifica la resolución técnica de una incidencia (RESUELTA).
     * - APOC: Alerta de validación y visto bueno pendiente.
     * - Administradores: Notificación de resolución.
     */
    public function notifyIncidentResolved(Incident $incident, User $technician): void
    {
        try {
            $incident->loadMissing(['reportedBy']);
            $actionUrl = route('incidents.show', $incident->folio);

            // 1. Notificación a APOC para su validación de servicio
            if ($incident->reportedBy && $incident->reportedBy->is_active) {
                $incident->reportedBy->notify(new IncidentNotification(
                    type: 'incident_resolved',
                    title: 'Incidencia Resuelta',
                    message: "La incidencia {$incident->folio} fue resuelta por {$technician->name}. Pendiente de visto bueno de APOC.",
                    incidentId: $incident->id,
                    folio: $incident->folio,
                    actionUrl: $actionUrl,
                    icon: 'task_alt',
                    level: 'success'
                ));
            }

            // 2. Notificación a Administradores
            $admins = User::where('is_active', true)
                ->whereHas('role', fn ($q) => $q->where('slug', 'admin'))
                ->whereNotIn('id', [$technician->id, $incident->reported_by_user_id])
                ->get();

            $adminNotification = new IncidentNotification(
                type: 'incident_resolved_admin',
                title: "Incidencia Resuelta: {$incident->folio}",
                message: "{$technician->name} marcó como resuelta la incidencia {$incident->folio}.",
                incidentId: $incident->id,
                folio: $incident->folio,
                actionUrl: $actionUrl,
                icon: 'check_circle',
                level: 'success'
            );

            foreach ($admins as $admin) {
                $admin->notify(clone $adminNotification);
            }
        } catch (\Throwable $e) {
            Log::error("Error despachando notificaciones de resolución para {$incident->folio}: ".$e->getMessage());
        }
    }

    /**
     * Notifica el rechazo de la solución técnica por parte de APOC.
     * - Técnico GTS asignado: Solución devuelta a EN_ATENCION con motivo pericial.
     * - Administradores: Alerta de discrepancia operativa.
     */
    public function notifyResolutionRejected(Incident $incident, User $rejecter, string $reason): void
    {
        try {
            $incident->loadMissing(['assignedTo']);
            $actionUrl = route('incidents.show', $incident->folio);
            $excerpt = mb_substr($reason, 0, 80).(mb_strlen($reason) > 80 ? '...' : '');

            // 1. Notificar al Técnico GTS asignado
            if ($incident->assignedTo && $incident->assignedTo->is_active) {
                $incident->assignedTo->notify(new IncidentNotification(
                    type: 'resolution_rejected',
                    title: 'Solución No Aprobada',
                    message: "APOC ({$rejecter->name}) rechazó la solución de {$incident->folio}. Motivo: {$excerpt}",
                    incidentId: $incident->id,
                    folio: $incident->folio,
                    actionUrl: $actionUrl,
                    icon: 'cancel',
                    level: 'warning'
                ));
            }

            // 2. Notificar a Administradores
            $admins = User::where('is_active', true)
                ->whereHas('role', fn ($q) => $q->where('slug', 'admin'))
                ->whereNotIn('id', [$rejecter->id, $incident->assigned_to_user_id])
                ->get();

            $adminNotification = new IncidentNotification(
                type: 'resolution_rejected_admin',
                title: "Solución Rechazada: {$incident->folio}",
                message: "APOC ({$rejecter->name}) devolvió a atención la incidencia {$incident->folio}.",
                incidentId: $incident->id,
                folio: $incident->folio,
                actionUrl: $actionUrl,
                icon: 'report_problem',
                level: 'warning'
            );

            foreach ($admins as $admin) {
                $admin->notify(clone $adminNotification);
            }
        } catch (\Throwable $e) {
            Log::error("Error despachando notificaciones de rechazo para {$incident->folio}: ".$e->getMessage());
        }
    }

    /**
     * Notifica el cierre formal definitivo con visto bueno de APOC.
     * - Técnico GTS: Confirmación de cierre exitoso de su trabajo.
     * - Administradores: Registro de ticket completado.
     */
    public function notifyIncidentClosed(Incident $incident, User $closer): void
    {
        try {
            $incident->loadMissing(['assignedTo', 'reportedBy']);
            $actionUrl = route('incidents.show', $incident->folio);

            // 1. Notificar al Técnico GTS responsable
            if ($incident->assignedTo && $incident->assignedTo->is_active && $incident->assigned_to_user_id !== $closer->id) {
                $incident->assignedTo->notify(new IncidentNotification(
                    type: 'incident_closed',
                    title: 'Incidencia Cerrada',
                    message: "{$incident->folio} recibió el visto bueno de cierre por {$closer->getTaggedName()}.",
                    incidentId: $incident->id,
                    folio: $incident->folio,
                    actionUrl: $actionUrl,
                    icon: 'verified',
                    level: 'success'
                ));
            }

            // 2. Notificar a Administradores
            $admins = User::where('is_active', true)
                ->whereHas('role', fn ($q) => $q->where('slug', 'admin'))
                ->whereNotIn('id', [$closer->id, $incident->assigned_to_user_id])
                ->get();

            $adminNotification = new IncidentNotification(
                type: 'incident_closed_admin',
                title: "Incidencia Cerrada: {$incident->folio}",
                message: "El ticket {$incident->folio} fue cerrado formalmente por {$closer->getTaggedName()}.",
                incidentId: $incident->id,
                folio: $incident->folio,
                actionUrl: $actionUrl,
                icon: 'lock',
                level: 'info'
            );

            foreach ($admins as $admin) {
                $admin->notify(clone $adminNotification);
            }
        } catch (\Throwable $e) {
            Log::error("Error despachando notificaciones de cierre para {$incident->folio}: ".$e->getMessage());
        }
    }

    /**
     * Elimina de forma controlada y segura todas las notificaciones asociadas a un expediente de incidencia.
     * Garantiza que la incidencia eliminada desaparezca de los centros de notificaciones de todos los roles
     * sin dejar enlaces rotos ni generar errores 404 al consultar alertas.
     *
     * @return int Número de notificaciones eliminadas
     */
    public function deleteNotificationsForIncident(int $incidentId, string $folio): int
    {
        try {
            $driver = DB::connection()->getDriverName();

            if ($driver === 'pgsql') {
                return DB::table('notifications')
                    ->where(function ($q) use ($incidentId, $folio) {
                        $q->whereRaw("data::jsonb->>'folio' = ? OR data::jsonb->>'incident_id' = ?", [$folio, (string) $incidentId]);
                    })
                    ->delete();
            }

            return DB::table('notifications')
                ->where(function ($q) use ($incidentId, $folio) {
                    $q->where('data', 'LIKE', '%"folio":"'.$folio.'"%')
                        ->orWhere('data', 'LIKE', '%"incident_id":'.$incidentId.'%')
                        ->orWhere('data', 'LIKE', '%"incident_id":"'.$incidentId.'"%');
                })
                ->delete();
        } catch (\Throwable $e) {
            Log::error("Error eliminando notificaciones para incidencia {$folio} (ID: {$incidentId}): ".$e->getMessage());

            return 0;
        }
    }
}
