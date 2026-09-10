<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\Status;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class IncidentService
{
    public function __construct(
        protected FolioGeneratorService $folioGenerator,
        protected AuditService $auditService,
        protected FileStorageService $fileStorage
    ) {}

    /**
     * Crea y despacha una nueva incidencia con folio atómico, auditoría y evidencias.
     */
    public function createIncident(array $data, User $reporter, array $attachments = []): Incident
    {
        return DB::transaction(function () use ($data, $reporter, $attachments) {
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
            if (!empty($attachments)) {
                $this->fileStorage->storeAttachments($incident, $reporter, $attachments);

                $this->auditService->logAction(
                    incidentId: $incident->id,
                    userId: $reporter->id,
                    action: 'ADJUNTOS_CARGADOS',
                    comment: count($attachments) . ' archivo(s) de evidencia adjuntado(s) al expediente.'
                );
            }

            return $incident->load(['category', 'priority', 'status', 'reportedBy', 'attachments']);
        });
    }
}

