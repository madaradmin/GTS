<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\IncidentHistory;
use App\Models\Status;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class IncidentService
{
    public function __construct(
        protected FolioGeneratorService $folioGenerator,
        protected AuditService $auditService,
        protected FileStorageService $fileStorage,
        protected NotificationService $notificationService
    ) {}

    /**
     * Crea y despacha una nueva incidencia con folio atómico, auditoría y evidencias.
     */
    public function createIncident(array $data, User $reporter, array $attachments = []): Incident
    {
        $incident = DB::transaction(function () use ($data, $reporter, $attachments) {
            // 1. Generar folio único no colisionable
            $folio = $this->folioGenerator->generateNextFolio();

            // 2. Obtener el estado inicial del ciclo de vida (NUEVA)
            $initialStatus = Status::where('code', 'NUEVA')->firstOrFail();

            // 3. Crear el expediente de la incidencia
            $incident = Incident::create([
                'folio' => $folio,
                'title' => trim($data['title']),
                'description' => trim($data['description']),
                'location' => trim($data['location']),
                'category_id' => (int) $data['category_id'],
                'priority_id' => (int) $data['priority_id'],
                'status_id' => $initialStatus->id,
                'reported_by_user_id' => $reporter->id,
                'reported_at' => now(),
            ]);

            // 4. Registrar auditoría inicial inmutable (RF-08 / RN-04)
            $this->auditService->logAction(
                incidentId: $incident->id,
                userId: $reporter->id,
                action: 'CREADA',
                fieldName: 'status_id',
                oldValue: null,
                newValue: $initialStatus->name,
                comment: "Incidencia despachada con Folio {$folio} desde canal APOC."
            );

            // 5. Procesar archivos adjuntos si existen
            if (! empty($attachments)) {
                $stored = $this->fileStorage->storeAttachments($incident, $reporter, $attachments);

                if (! empty($stored)) {
                    $this->auditService->logAction(
                        incidentId: $incident->id,
                        userId: $reporter->id,
                        action: 'ADJUNTOS_CARGADOS',
                        fieldName: 'attachments',
                        comment: count($stored).' archivo(s) de evidencia adjuntado(s) al expediente.'
                    );
                }
            }

            return $incident->load(['category', 'priority', 'status', 'reportedBy', 'attachments']);
        });

        $this->notificationService->notifyIncidentCreated($incident);

        return $incident;
    }

    /**
     * Asigna o reasigna un especialista GTS a la incidencia y actualiza el estado operacional.
     *
     * @param  Incident  $incident  Incidencia a asignar
     * @param  User  $assigner  Usuario autenticado que ejecuta la acción (GTS o Admin)
     * @param  User  $technician  Especialista GTS asignado como responsable técnico
     * @param  string|null  $comment  Observaciones opcionales sobre la asignación
     */
    public function assignIncident(Incident $incident, User $assigner, User $technician, ?string $comment = null): Incident
    {
        $isInitialAssignment = is_null($incident->assigned_to_user_id) && ! $incident->isAssignedToTics();

        $incident = DB::transaction(function () use ($incident, $assigner, $technician, $comment, $isInitialAssignment) {
            $previousTechnician = $incident->isAssignedToTics() ? 'TICS' : ($incident->assignedTo?->name ?? 'Sin asignar');
            $previousStatus = $incident->status->name;

            // 1. Si la incidencia se encuentra en estado NUEVA, transicionar a ASIGNADA (RN-02 / RN-03)
            if ($incident->status->code === 'NUEVA') {
                $assignedStatus = Status::where('code', 'ASIGNADA')->firstOrFail();
                $incident->status_id = $assignedStatus->id;

                $this->auditService->logAction(
                    incidentId: $incident->id,
                    userId: $assigner->id,
                    action: 'CAMBIO_ESTADO',
                    fieldName: 'status_id',
                    oldValue: $previousStatus,
                    newValue: $assignedStatus->name,
                    comment: $comment ?? ($assigner->id === $technician->id
                        ? "Incidencia tomada directamente por {$technician->name}."
                        : "Incidencia asignada a {$technician->name}.")
                );
            }

            // 2. Actualizar técnico asignado y limpiar especialista asignado
            $incident->assigned_to_user_id = $technician->id;
            $incident->assigned_specialist = null;
            if (is_null($incident->assigned_at)) {
                $incident->assigned_at = now();
            }
            $incident->save();

            // 3. Registrar auditoría de asignación o reasignación inmutable (RF-08 / RN-04)
            $auditAction = $isInitialAssignment ? 'ASIGNADA' : 'REASIGNADA';
            $auditComment = $comment;
            if (empty($auditComment)) {
                $auditComment = $isInitialAssignment
                    ? ($assigner->id === $technician->id
                        ? "El especialista {$technician->name} tomó la responsabilidad técnica de la incidencia."
                        : "Asignado a {$technician->name} por {$assigner->name}.")
                    : "Reasignado de {$previousTechnician} a {$technician->name} por {$assigner->name}.";
            }

            $this->auditService->logAction(
                incidentId: $incident->id,
                userId: $assigner->id,
                action: $auditAction,
                fieldName: 'assigned_to_user_id',
                oldValue: $previousTechnician,
                newValue: $technician->name,
                comment: $auditComment
            );

            return $incident->fresh(['category', 'priority', 'status', 'reportedBy', 'assignedTo', 'histories.user', 'attachments']);
        });

        $this->notificationService?->notifyIncidentAssigned($incident, $assigner, $technician, ! $isInitialAssignment);

        return $incident;
    }

    /**
     * Asigna o reasigna un especialista / área externa (ej. TICS) a la incidencia.
     *
     * @param  Incident  $incident  Incidencia a asignar
     * @param  User  $assigner  Usuario autenticado que ejecuta la acción (GTS o Admin)
     * @param  string  $specialist  Nombre del especialista o área (por defecto 'TICS')
     * @param  string|null  $comment  Observaciones opcionales sobre la asignación
     */
    public function assignToSpecialist(Incident $incident, User $assigner, string $specialist = 'TICS', ?string $comment = null): Incident
    {
        $isInitialAssignment = is_null($incident->assigned_to_user_id) && ! $incident->isAssignedToTics();

        $incident = DB::transaction(function () use ($incident, $assigner, $specialist, $comment, $isInitialAssignment) {
            $previousTechnician = $incident->isAssignedToTics() ? 'TICS' : ($incident->assignedTo?->name ?? 'Sin asignar');
            $previousStatus = $incident->status->name;

            // 1. Si la incidencia se encuentra en estado NUEVA, transicionar a ASIGNADA (RN-02 / RN-03)
            if ($incident->status->code === 'NUEVA') {
                $assignedStatus = Status::where('code', 'ASIGNADA')->firstOrFail();
                $incident->status_id = $assignedStatus->id;

                $this->auditService->logAction(
                    incidentId: $incident->id,
                    userId: $assigner->id,
                    action: 'CAMBIO_ESTADO',
                    fieldName: 'status_id',
                    oldValue: $previousStatus,
                    newValue: $assignedStatus->name,
                    comment: $comment ?? "Incidencia asignada al especialista {$specialist}."
                );
            }

            // 2. Actualizar técnico asignado (null), especialista ('TICS') y timestamp
            $incident->assigned_to_user_id = null;
            $incident->assigned_specialist = $specialist;
            if (is_null($incident->assigned_at)) {
                $incident->assigned_at = now();
            }
            $incident->save();

            // 3. Registrar auditoría inmutable
            $auditAction = $isInitialAssignment ? 'ASIGNADA' : 'REASIGNADA';
            $auditComment = $comment;
            if (empty($auditComment)) {
                $auditComment = $isInitialAssignment
                    ? "Asignado a {$specialist} por {$assigner->name}."
                    : "Reasignado de {$previousTechnician} a {$specialist} por {$assigner->name}.";
            }

            $this->auditService->logAction(
                incidentId: $incident->id,
                userId: $assigner->id,
                action: $auditAction,
                fieldName: 'assigned_specialist',
                oldValue: $previousTechnician,
                newValue: $specialist,
                comment: $auditComment
            );

            return $incident->fresh(['category', 'priority', 'status', 'reportedBy', 'assignedTo', 'histories.user', 'attachments']);
        });

        $this->notificationService?->notifyIncidentAssignedToSpecialist($incident, $assigner, $specialist, ! $isInitialAssignment);

        return $incident;
    }

    /**
     * Inicia la atención técnica y diagnóstico en sitio o consola (transición a EN_ATENCION).
     *
     * @param  Incident  $incident  Incidencia objetivo
     * @param  User  $user  Especialista técnico que inicia la atención (GTS o Admin)
     * @param  string|null  $comment  Observaciones opcionales iniciales
     */
    public function startAttention(Incident $incident, User $user, ?string $comment = null): Incident
    {
        $incident = DB::transaction(function () use ($incident, $user, $comment) {
            $previousStatus = $incident->status->name;

            // 1. Si la incidencia no tenía técnico asignado ni especialista, auto-asignar al técnico que inicia atención (RN-02 / RN-03)
            if (is_null($incident->assigned_to_user_id) && ! $incident->isAssignedToTics()) {
                $incident->assigned_to_user_id = $user->id;
                $incident->assigned_at = now();

                $this->auditService->logAction(
                    incidentId: $incident->id,
                    userId: $user->id,
                    action: 'ASIGNADA',
                    fieldName: 'assigned_to_user_id',
                    oldValue: 'Sin asignar',
                    newValue: $user->name,
                    comment: 'Incidencia asumida directamente al iniciar atención técnica.'
                );
            }

            // 2. Transicionar estado operacional a EN_ATENCION
            $inAttentionStatus = Status::where('code', 'EN_ATENCION')->firstOrFail();
            $incident->status_id = $inAttentionStatus->id;
            $incident->attended_at = now();
            $incident->save();

            // 3. Registrar auditoría inmutable de cambio de estado
            $this->auditService->logAction(
                incidentId: $incident->id,
                userId: $user->id,
                action: 'CAMBIO_ESTADO',
                fieldName: 'status_id',
                oldValue: $previousStatus,
                newValue: $inAttentionStatus->name,
                comment: $comment ?? "Atención técnica iniciada por el especialista {$user->name}."
            );

            return $incident->fresh(['category', 'priority', 'status', 'reportedBy', 'assignedTo', 'histories.user', 'attachments']);
        });

        $this->notificationService?->notifyAttentionStarted($incident, $user);

        return $incident;
    }

    /**
     * Registra una nota técnica de campo o avance de diagnóstico en la bitácora de la incidencia.
     *
     * @param  Incident  $incident  Incidencia en atención activa
     * @param  User  $user  Especialista técnico o administrador
     * @param  string  $note  Contenido detallado del avance técnico
     */
    public function addTechnicalNote(Incident $incident, User $user, string $note): IncidentHistory
    {
        $history = $this->auditService->logAction(
            incidentId: $incident->id,
            userId: $user->id,
            action: 'NOTA_TECNICA',
            fieldName: null,
            oldValue: null,
            newValue: null,
            comment: trim($note)
        );

        $this->notificationService?->notifyTechnicalNoteAdded($incident, $user, $note);

        return $history;
    }

    /**
     * Marca la incidencia como RESUELTA por el personal técnico GTS, documentando la solución aplicada.
     *
     * @param  Incident  $incident  Incidencia en atención técnica
     * @param  User  $technician  Especialista técnico o Administrador que resuelve
     * @param  string  $solutionDescription  Detalle técnico exhaustivo de la solución aplicada
     */
    public function resolveIncident(Incident $incident, User $technician, string $solutionDescription): Incident
    {
        $incident = DB::transaction(function () use ($incident, $technician, $solutionDescription) {
            $previousStatus = $incident->status->name;
            $resolvedStatus = Status::where('code', 'RESUELTA')->firstOrFail();

            // 1. Actualizar estado operacional, solución técnica y timestamp de resolución (RN-02)
            $incident->status_id = $resolvedStatus->id;
            $incident->solution_description = trim($solutionDescription);
            $incident->resolved_at = now();
            $incident->save();

            // 2. Registrar auditoría de cambio de estado a RESUELTA (RN-04)
            $this->auditService->logAction(
                incidentId: $incident->id,
                userId: $technician->id,
                action: 'CAMBIO_ESTADO',
                fieldName: 'status_id',
                oldValue: $previousStatus,
                newValue: $resolvedStatus->name,
                comment: "Incidencia marcada como RESUELTA por el especialista {$technician->name}."
            );

            // 3. Registrar auditoría específica de la solución aplicada (RF-05 / RN-02 / RN-04)
            $this->auditService->logAction(
                incidentId: $incident->id,
                userId: $technician->id,
                action: 'RESUELTA',
                fieldName: 'solution_description',
                oldValue: null,
                newValue: null,
                comment: trim($solutionDescription)
            );

            return $incident->fresh(['category', 'priority', 'status', 'reportedBy', 'assignedTo', 'histories.user', 'attachments']);
        });

        $this->notificationService?->notifyIncidentResolved($incident, $technician);

        return $incident;
    }

    /**
     * Rechaza la solución técnica reportada por GTS y devuelve la incidencia al estado activo EN_ATENCION.
     *
     * @param  Incident  $incident  Incidencia en estado RESUELTA
     * @param  User  $actor  Operador APOC o Administrador que rechaza
     * @param  string  $reason  Justificación obligatoria detallando por qué la falla persiste
     */
    public function rejectResolution(Incident $incident, User $actor, string $reason): Incident
    {
        $incident = DB::transaction(function () use ($incident, $actor, $reason) {
            $previousStatus = $incident->status->name;
            $inAttentionStatus = Status::where('code', 'EN_ATENCION')->firstOrFail();

            // 1. Revertir estado a EN_ATENCION y registrar el motivo de rechazo (RN-02)
            $incident->status_id = $inAttentionStatus->id;
            $incident->rejection_reason = trim($reason);
            $incident->resolved_at = null; // Se reinicia el tiempo de resolución para reabrir la ventana técnica
            $incident->save();

            // 2. Auditoría inmutable del cambio de estado regresivo
            $this->auditService->logAction(
                incidentId: $incident->id,
                userId: $actor->id,
                action: 'CAMBIO_ESTADO',
                fieldName: 'status_id',
                oldValue: $previousStatus,
                newValue: $inAttentionStatus->name,
                comment: "Solución rechazada por {$actor->name} (APOC). Incidencia reingresada a atención técnica."
            );

            // 3. Auditoría inmutable del rechazo con la justificación operativa (RF-05 / RN-02 / RN-04)
            $this->auditService->logAction(
                incidentId: $incident->id,
                userId: $actor->id,
                action: 'RECHAZO_SOLUCION',
                fieldName: 'rejection_reason',
                oldValue: null,
                newValue: null,
                comment: trim($reason)
            );

            return $incident->fresh(['category', 'priority', 'status', 'reportedBy', 'assignedTo', 'histories.user', 'attachments']);
        });

        $this->notificationService?->notifyResolutionRejected($incident, $actor, $reason);

        return $incident;
    }

    /**
     * Da el visto bueno operacional y cierra definitivamente la incidencia (transición a CERRADA).
     *
     * @param  Incident  $incident  Incidencia en estado RESUELTA
     * @param  User  $closer  Operador APOC o Administrador que otorga el cierre
     * @param  string|null  $comment  Observaciones de conformidad o cierre operacional (opcional)
     */
    public function closeIncident(Incident $incident, User $closer, ?string $comment = null): Incident
    {
        $incident = DB::transaction(function () use ($incident, $closer, $comment) {
            $previousStatus = $incident->status->name;
            $closedStatus = Status::where('code', 'CERRADA')->firstOrFail();

            // 1. Transicionar estado a CERRADA y registrar timestamp final (RN-02)
            $incident->status_id = $closedStatus->id;
            $incident->closed_at = now();
            $incident->save();

            // 2. Auditoría inmutable de cambio de estado a CERRADA
            $this->auditService->logAction(
                incidentId: $incident->id,
                userId: $closer->id,
                action: 'CAMBIO_ESTADO',
                fieldName: 'status_id',
                oldValue: $previousStatus,
                newValue: $closedStatus->name,
                comment: $comment ?? "Visto bueno operacional otorgado por {$closer->getTaggedName()}. Incidencia cerrada definitivamente."
            );

            // 3. Auditoría inmutable de cierre definitivo del expediente (RF-05 / RN-02 / RN-04)
            $this->auditService->logAction(
                incidentId: $incident->id,
                userId: $closer->id,
                action: 'CERRADA',
                fieldName: 'closed_at',
                oldValue: null,
                newValue: $incident->closed_at->toIso8601String(),
                comment: $comment ?? "Expediente archivado en modo inmutable con visto bueno de {$closer->getTaggedName()}."
            );

            return $incident->fresh(['category', 'priority', 'status', 'reportedBy', 'assignedTo', 'histories.user', 'attachments']);
        });

        $this->notificationService?->notifyIncidentClosed($incident, $closer);

        return $incident;
    }
}
