<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\IncidentAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileStorageService
{
    /**
     * Almacena de forma segura una colección de archivos adjuntos para una incidencia.
     * Almacenamiento seguro privado (no accesible directamente vía URL pública).
     * Nombre aleatorizado con UUID para blindaje contra inyección de rutas y colisiones.
     *
     * @param  UploadedFile[]  $files
     * @return IncidentAttachment[]
     */
    public function storeAttachments(Incident $incident, User $uploader, array $files): array
    {
        $attachments = [];

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension() ?: 'bin';
            $mimeType = $file->getMimeType() ?? 'application/octet-stream';
            $fileSize = $file->getSize();

            // Nombre físico único y criptográfico para evitar sobreescrituras e inyecciones de ruta
            $safeFileName = Str::uuid()->toString().'.'.strtolower($extension);
            $directory = 'incidents/'.$incident->id;

            // Guardar en el disco 'local' privado (storage/app/private/incidents/{id}/)
            $storedPath = $file->storeAs($directory, $safeFileName, 'local');

            $attachments[] = IncidentAttachment::create([
                'incident_id' => $incident->id,
                'uploaded_by_user_id' => $uploader->id,
                'original_filename' => $originalName,
                'stored_path' => $storedPath,
                'mime_type' => $mimeType,
                'file_size_bytes' => $fileSize,
            ]);
        }

        return $attachments;
    }

    /**
     * Verifica si el archivo físico de la evidencia existe en el disco privado.
     */
    public function exists(IncidentAttachment $attachment): bool
    {
        return Storage::disk('local')->exists($attachment->stored_path);
    }

    /**
     * Entrega el archivo para visualización en el navegador (inline).
     */
    public function streamInline(IncidentAttachment $attachment): BinaryFileResponse
    {
        $path = Storage::disk('local')->path($attachment->stored_path);

        return response()->file($path, [
            'Content-Type' => $attachment->mime_type,
            'Content-Disposition' => 'inline; filename="'.addslashes($attachment->original_filename).'"',
        ]);
    }

    /**
     * Entrega el archivo forzando su descarga segura (attachment).
     */
    public function download(IncidentAttachment $attachment): StreamedResponse
    {
        return Storage::disk('local')->download($attachment->stored_path, $attachment->original_filename);
    }

    /**
     * Elimina todos los archivos físicos asociados a una incidencia en almacenamiento privado.
     */
    public function deleteIncidentFiles(Incident $incident): void
    {
        Storage::disk('local')->deleteDirectory('incidents/'.$incident->id);
    }
}
