<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttachmentRequest;
use App\Models\Incident;
use App\Models\IncidentAttachment;
use App\Services\AuditService;
use App\Services\FileStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IncidentAttachmentController extends Controller
{
    public function __construct(
        protected FileStorageService $fileStorageService,
        protected AuditService $auditService
    ) {}

    /**
     * Carga y almacena de forma segura nuevas evidencias en el expediente (RF-06).
     */
    public function store(StoreAttachmentRequest $request, Incident $incident): RedirectResponse
    {
        Gate::authorize('uploadAttachment', $incident);

        $files = $request->file('attachments', []);
        $stored = $this->fileStorageService->storeAttachments($incident, $request->user(), $files);

        $count = count($stored);
        $this->auditService->logAction(
            incidentId: $incident->id,
            userId: $request->user()->id,
            action: 'ADJUNTOS_CARGADOS',
            fieldName: 'attachments',
            comment: "{$count} archivo(s) de evidencia adjuntado(s) por {$request->user()->name}."
        );

        return redirect()->route('incidents.show', $incident->folio)
            ->with('success', "{$count} archivo(s) de evidencia adjuntado(s) exitosamente al expediente.");
    }

    /**
     * Visualiza una evidencia en línea (inline stream en navegador para imágenes y documentos).
     */
    public function show(Incident $incident, IncidentAttachment $attachment): BinaryFileResponse
    {
        Gate::authorize('downloadAttachment', $incident);

        abort_if($attachment->incident_id !== $incident->id, 404, 'La evidencia no pertenece a este expediente.');
        abort_if(! $this->fileStorageService->exists($attachment), 404, 'El archivo físico no se encuentra disponible.');

        return $this->fileStorageService->streamInline($attachment);
    }

    /**
     * Descarga de forma segura el archivo de evidencia con headers forzados de descarga.
     */
    public function download(Incident $incident, IncidentAttachment $attachment): StreamedResponse
    {
        Gate::authorize('downloadAttachment', $incident);

        abort_if($attachment->incident_id !== $incident->id, 404, 'La evidencia no pertenece a este expediente.');
        abort_if(! $this->fileStorageService->exists($attachment), 404, 'El archivo físico no se encuentra disponible.');

        return $this->fileStorageService->download($attachment);
    }
}
