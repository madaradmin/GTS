<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\IncidentHistory;
use App\Models\Role;
use App\Models\Status;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
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
     * Retorna el catálogo de áreas responsables disponibles para asignación según los alcances operativos del usuario.
     * Excluye estrictamente el rol Administrador, el área no funcional TICS y el rol de procedencia que originó la incidencia.
     *
     * @return SupportCollection<int, array<string, mixed>>
     */
    public function getAssignableAreas(User $user, ?Incident $incident = null): SupportCollection
    {
        $areas = collect();

        // Determinar ID del rol creador de la incidencia para exclusión general
        $creatorRoleId = $incident?->reportedBy?->role_id;

        // 1. Áreas de roles del sistema según matriz de alcances operativos (excluyendo Administrador y rol creador)
        $query = Role::where('slug', '!=', 'admin');

        if ($creatorRoleId) {
            $query->where('id', '!=', $creatorRoleId);
        }

        if ($user->isAdmin()) {
            $roles = $query->orderBy('name')->get();
        } else {
            $assignableRoleIds = $user->assignableRoleIds();
            if (! empty($assignableRoleIds)) {
                $roles = $query->whereIn('id', $assignableRoleIds)->orderBy('name')->get();
            } else {
                $roles = $query->where('slug', 'gts')->get();
            }
        }

        foreach ($roles as $role) {
            $areas->push([
                'code' => $role->slug,
                'name' => $role->name,
                'role_id' => $role->id,
                'badge_bg' => $role->badge_bg ?? 'bg-slate-50',
                'badge_text' => $role->badge_text ?? 'text-slate-700',
                'badge_border' => $role->badge_border ?? 'border-slate-200',
            ]);
        }

        return $areas;
    }

    /**
     * Asigna o reasigna una incidencia a una o múltiples áreas responsables, y opcionalmente a un especialista técnico.
     * Soporta los 4 escenarios de negocio:
     * - Escenario 1: Una sola área, sin usuario.
     * - Escenario 2: Una sola área y un usuario compatible.
     * - Escenario 3: Varias áreas, sin usuario.
     * - Escenario 4: Varias áreas y un usuario compatible.
     *
     * @param  Incident  $incident  Incidencia a asignar
     * @param  User  $assigner  Usuario autenticado que ejecuta la acción
     * @param  array<int, string>  $areas  Códigos de las áreas seleccionadas (ej. ['gts'], ['tics'], ['gts', 'tics'])
     * @param  User|null  $technician  Especialista opcional asignado como responsable técnico
     * @param  string|null  $comment  Observaciones opcionales sobre la asignación
     */
    public function assign(Incident $incident, User $assigner, array $areas, ?User $technician = null, ?string $comment = null): Incident
    {
        // 1. Asegurar deduplicación y normalización de áreas
        $areas = array_values(array_unique(array_filter(array_map(fn ($a) => strtolower(trim((string) $a)), $areas))));

        // Si se provee un técnico, asegurar que su área esté incluida
        if ($technician && $technician->role) {
            $techRoleSlug = strtolower($technician->role->slug);
            if (! in_array($techRoleSlug, $areas, true)) {
                $areas[] = $techRoleSlug;
            }
        }

        // 2. Resolver metadatos de las áreas asignadas excluyendo admin y rol creador
        $resolvedAreas = [];
        $creatorRoleId = $incident->reportedBy?->role_id;

        foreach ($areas as $code) {
            if ($code === 'tics' || $code === 'admin') {
                continue;
            }

            $role = Role::whereRaw('LOWER(slug) = ?', [$code])->first()
                ?? Role::whereRaw('LOWER(name) = ?', [$code])->first();

            if ($role) {
                if ($role->slug === 'admin' || ($creatorRoleId && $role->id === $creatorRoleId)) {
                    continue;
                }

                $resolvedAreas[$role->slug] = [
                    'code' => $role->slug,
                    'name' => $role->name,
                    'role_id' => $role->id,
                ];
            } else {
                $resolvedAreas[$code] = [
                    'code' => $code,
                    'name' => strtoupper($code),
                    'role_id' => null,
                ];
            }
        }

        $resolvedAreas = array_values($resolvedAreas);

        // Si la lista de áreas quedó vacía, asignar GTS por defecto si no es el creador
        if (empty($resolvedAreas)) {
            $gtsRole = Role::where('slug', 'gts')->first();
            $resolvedAreas[] = [
                'code' => 'gts',
                'name' => $gtsRole?->name ?? 'GTS',
                'role_id' => $gtsRole?->id,
            ];
        }

        $isInitialAssignment = is_null($incident->assigned_to_user_id)
            && ! $incident->isAssignedToTics()
            && $incident->assignedAreas()->count() === 0;

        $previousTechnician = $incident->assignedTo?->name
            ?? ($incident->isAssignedToTics()
                ? 'TICS'
                : (! empty($incident->assignedAreaNames())
                    ? implode(', ', $incident->assignedAreaNames())
                    : 'Sin asignar'));
        $previousStatus = $incident->status->name;

        $incident = DB::transaction(function () use (
            $incident,
            $assigner,
            $resolvedAreas,
            $technician,
            $comment,
            $isInitialAssignment,
            $previousTechnician,
            $previousStatus
        ) {
            $areaNames = array_column($resolvedAreas, 'name');
            $areaLabels = implode(', ', $areaNames);

            // A. Si la incidencia se encuentra en estado NUEVA, transicionar a ASIGNADA (RN-02 / RN-03)
            if ($incident->status->code === 'NUEVA') {
                $assignedStatus = Status::where('code', 'ASIGNADA')->firstOrFail();
                $incident->status_id = $assignedStatus->id;

                $statusComment = $comment;
                if (empty($statusComment)) {
                    if ($technician) {
                        $statusComment = $assigner->id === $technician->id
                            ? "Incidencia tomada directamente por {$technician->name}."
                            : "Incidencia asignada a {$technician->name} ({$areaLabels}).";
                    } elseif (count($resolvedAreas) === 1 && $resolvedAreas[0]['code'] === 'tics') {
                        $statusComment = "Incidencia asignada al especialista TICS.";
                    } else {
                        $statusComment = "Incidencia asignada al área responsable {$areaLabels}.";
                    }
                }

                $this->auditService->logAction(
                    incidentId: $incident->id,
                    userId: $assigner->id,
                    action: 'CAMBIO_ESTADO',
                    fieldName: 'status_id',
                    oldValue: $previousStatus,
                    newValue: $assignedStatus->name,
                    comment: $statusComment
                );
            }

            // B. Sincronizar tabla incident_assigned_areas evitando colisiones
            $incident->assignedAreas()->delete();
            foreach ($resolvedAreas as $area) {
                $incident->assignedAreas()->create([
                    'area_code' => $area['code'],
                    'area_name' => mb_substr($area['name'], 0, 100),
                    'role_id' => $area['role_id'],
                ]);
            }

            // C. Actualizar columnas en incidents
            if ($technician) {
                $incident->assigned_to_user_id = $technician->id;
                $incident->assigned_specialist = null;
            } else {
                $incident->assigned_to_user_id = null;
                $incident->assigned_specialist = mb_substr($areaLabels, 0, 50);
            }

            if (is_null($incident->assigned_at)) {
                $incident->assigned_at = now();
            }
            $incident->save();

            // D. Registrar auditoría inmutable de asignación / reasignación
            $auditAction = $isInitialAssignment ? 'ASIGNADA' : 'REASIGNADA';

            if ($technician) {
                $fieldName = 'assigned_to_user_id';
                $newValue = $technician->name;
                $auditComment = $comment;
                if (empty($auditComment)) {
                    $auditComment = $isInitialAssignment
                        ? ($assigner->id === $technician->id
                            ? "El especialista {$technician->name} tomó la responsabilidad técnica de la incidencia."
                            : "Asignado a {$technician->name} ({$areaLabels}) por {$assigner->name}.")
                        : "Reasignado de {$previousTechnician} a {$technician->name} ({$areaLabels}) por {$assigner->name}.";
                }
            } else {
                $fieldName = 'assigned_specialist';
                $newValue = (count($resolvedAreas) === 1 && $resolvedAreas[0]['code'] === 'tics') ? 'TICS' : $areaLabels;
                $auditComment = $comment;
                if (empty($auditComment)) {
                    $auditComment = $isInitialAssignment
                        ? "Asignado a {$newValue} por {$assigner->name}."
                        : "Reasignado de {$previousTechnician} a {$newValue} por {$assigner->name}.";
                }
            }

            $this->auditService->logAction(
                incidentId: $incident->id,
                userId: $assigner->id,
                action: $auditAction,
                fieldName: $fieldName,
                oldValue: $previousTechnician,
                newValue: $newValue,
                comment: $auditComment
            );

            return $incident->fresh(['category', 'priority', 'status', 'reportedBy', 'assignedTo', 'assignedAreas', 'histories.user', 'attachments']);
        });

        // E. Despachar notificaciones
        if ($technician) {
            $this->notificationService?->notifyIncidentAssigned($incident, $assigner, $technician, ! $isInitialAssignment);
        } elseif (in_array('tics', array_map('strtolower', array_column($resolvedAreas, 'code')), true)) {
            $this->notificationService?->notifyIncidentAssignedToSpecialist($incident, $assigner, 'TICS', ! $isInitialAssignment);
        }

        return $incident;
    }

    /**
     * Asigna o reasigna un especialista individual a la incidencia (mantiene compatibilidad de llamadas legadas).
     *
     * @param  Incident  $incident  Incidencia a asignar
     * @param  User  $assigner  Usuario autenticado que ejecuta la acción (GTS o Admin)
     * @param  User  $technician  Especialista asignado como responsable técnico
     * @param  string|null  $comment  Observaciones opcionales sobre la asignación
     */
    public function assignIncident(Incident $incident, User $assigner, User $technician, ?string $comment = null): Incident
    {
        $areaSlug = $technician->role?->slug ?? 'gts';

        return $this->assign($incident, $assigner, [$areaSlug], $technician, $comment);
    }

    /**
     * Asigna o reasigna un especialista / área externa (ej. TICS) a la incidencia (mantiene compatibilidad legada).
     *
     * @param  Incident  $incident  Incidencia a asignar
     * @param  User  $assigner  Usuario autenticado que ejecuta la acción (GTS o Admin)
     * @param  string  $specialist  Nombre del especialista o área (por defecto 'TICS')
     * @param  string|null  $comment  Observaciones opcionales sobre la asignación
     */
    public function assignToSpecialist(Incident $incident, User $assigner, string $specialist = 'TICS', ?string $comment = null): Incident
    {
        $areaSlug = strtolower($specialist) === 'tics' ? 'tics' : $specialist;

        return $this->assign($incident, $assigner, [$areaSlug], null, $comment);
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
                $incident->assigned_at = $incident->assigned_at ?? now();

                if ($user->role && ! $incident->isAssignedToArea($user->role->slug)) {
                    $incident->assignedAreas()->firstOrCreate([
                        'area_code' => $user->role->slug,
                        'area_name' => $user->role->name,
                        'role_id' => $user->role_id,
                    ]);
                }

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

    /**
     * Retorna los especialistas o técnicos disponibles para asignación según los alcances operativos del usuario.
     * Excluye estrictamente usuarios con rol Administrador y usuarios del rol que originó la incidencia.
     *
     * @return Collection<int, User>
     */
    public function getAvailableTechnicians(?User $user = null, ?Incident $incident = null): Collection
    {
        $query = User::where('is_active', true)
            ->whereHas('role', fn ($rq) => $rq->where('slug', '!=', 'admin'))
            ->with('role')
            ->orderBy('name', 'asc');

        $creatorRoleId = $incident?->reportedBy?->role_id;
        if ($creatorRoleId) {
            $query->where('role_id', '!=', $creatorRoleId);
        }

        if ($user) {
            $assignableRoleIds = $user->assignableRoleIds();
            if (! empty($assignableRoleIds)) {
                if ($user->isAdmin()) {
                    return $query->where(function ($q) use ($assignableRoleIds) {
                        $q->whereIn('role_id', $assignableRoleIds)
                            ->where(function ($sq) {
                                $sq->whereHas('role.permissions', fn ($pq) => $pq->where('slug', 'incidents.attend'))
                                    ->orWhereHas('role', fn ($rq) => $rq->where('slug', 'gts'));
                            });
                    })->get();
                }

                return $query->whereIn('role_id', $assignableRoleIds)->get();
            }
        }

        return $query->whereHas('role', fn ($q) => $q->where('slug', 'gts'))->get();
    }
}
