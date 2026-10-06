<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'incident_id',
        'uploaded_by_user_id',
        'original_filename',
        'stored_path',
        'mime_type',
        'file_size_bytes',
    ];

    protected function casts(): array
    {
        return [
            'file_size_bytes' => 'integer',
        ];
    }

    /**
     * Incidencia a la que pertenece esta evidencia.
     */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    /**
     * Usuario que cargó el archivo al expediente.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    /**
     * Relación con el usuario que cargó el archivo (alias para consistencia con auditorías y reportes).
     */
    public function user(): BelongsTo
    {
        return $this->uploadedBy();
    }

    /**
     * Alias de original_filename para compatibilidad con reportes.
     */
    public function getOriginalNameAttribute(): ?string
    {
        return $this->original_filename;
    }

    /**
     * Alias de file_size_bytes para compatibilidad con reportes.
     */
    public function getFileSizeAttribute(): ?int
    {
        return $this->file_size_bytes;
    }

    /**
     * Retorna el tamaño del archivo formateado en unidades legibles (B, KB, MB).
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size_bytes ?? 0;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0).' KB';
        }

        return $bytes.' B';
    }

    /**
     * Determina si el archivo es una imagen rasterizada visualizable.
     */
    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    /**
     * Determina si el archivo es un documento PDF.
     */
    public function getIsPdfAttribute(): bool
    {
        return ($this->mime_type ?? '') === 'application/pdf';
    }

    /**
     * Retorna el nombre del icono de Google Material Symbols correspondiente.
     */
    public function getFileIconAttribute(): string
    {
        if ($this->is_image) {
            return 'image';
        }
        if ($this->is_pdf) {
            return 'picture_as_pdf';
        }
        if (str_contains($this->mime_type ?? '', 'text')) {
            return 'description';
        }

        return 'attach_file';
    }

    /**
     * Retorna las clases de color Tailwind para el badge táctico según el formato del archivo.
     */
    public function getFileBadgeColorAttribute(): string
    {
        if ($this->is_image) {
            return 'text-cyan-400 bg-cyan-500/10 border-cyan-500/30';
        }
        if ($this->is_pdf) {
            return 'text-rose-400 bg-rose-500/10 border-rose-500/30';
        }

        return 'text-amber-400 bg-amber-500/10 border-amber-500/30';
    }
}
