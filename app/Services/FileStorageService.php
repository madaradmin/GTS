<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\IncidentAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class FileStorageService
{
    /**
     * Almacena de forma segura una colección de archivos adjuntos para una incidencia.
     *
     * @param UploadedFile[] $files
     * @return IncidentAttachment[]
     */
    public function storeAttachments(Incident $incident, User $uploader, array $files): array
    {
        $attachments = [];

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile || !$file->isValid()) {
                continue;
            }

            $originalName = $file->getClientOriginalName();
            $extension    = $file->getClientOriginalExtension();
            $mimeType     = $file->getMimeType() ?? 'application/octet-stream';
            $fileSize     = $file->getSize();

            // Nombre físico único y criptográfico para evitar sobreescrituras e inyecciones de ruta
            $safeFileName = Str::uuid()->toString() . '.' . strtolower($extension);
            $directory    = 'incidents/' . $incident->id;

            // Guardar en el disco 'local' (storage/app/private/)
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
}

