<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class IncidentHistory extends Model
{
    use HasFactory;

    // La auditoría es estrictamente Append-Only (sin updated_at)
    public const UPDATED_AT = null;

    protected $fillable = [
        'incident_id',
        'user_id',
        'action',
        'field_name',
        'old_value',
        'new_value',
        'comment',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * Intercepción de eventos de modelo para blindar la inmutabilidad física (RN-04 / RF-05).
     * Por normativa aeronáutica de la AFAC y OACI, los registros periciales no pueden mutar ni eliminarse.
     */
    protected static function booted(): void
    {
        static::updating(function () {
            throw new RuntimeException('La bitácora de auditoría es estrictamente inmutable (Append-Only) por normativa aeronáutica.');
        });

        static::deleting(function () {
            throw new RuntimeException('La eliminación de registros de auditoría histórica está terminantemente prohibida.');
        });
    }

    /**
     * Incidencia sobre la que se ejecutó la acción.
     */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    /**
     * Usuario responsable que ejecutó la acción.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope para filtrado avanzado en la bitácora pericial de auditoría.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        $likeOperator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        // 1. Búsqueda por término (comentario, valores anterior/nuevo, campo o folio)
        if (! empty($filters['search'])) {
            $search = '%'.trim($filters['search']).'%';
            $query->where(function (Builder $q) use ($search, $likeOperator) {
                $q->where('comment', $likeOperator, $search)
                    ->orWhere('field_name', $likeOperator, $search)
                    ->orWhere('old_value', $likeOperator, $search)
                    ->orWhere('new_value', $likeOperator, $search)
                    ->orWhereHas('incident', function (Builder $iq) use ($search, $likeOperator) {
                        $iq->where('folio', $likeOperator, $search)
                            ->orWhere('title', $likeOperator, $search);
                    });
            });
        }

        // 2. Filtro por tipo de acción
        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        // 3. Filtro por usuario responsable
        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // 4. Filtro por incidencia específica (por ID)
        if (! empty($filters['incident_id'])) {
            $query->where('incident_id', $filters['incident_id']);
        }

        // 5. Filtro por folio de incidencia
        if (! empty($filters['folio'])) {
            $query->whereHas('incident', function (Builder $iq) use ($filters) {
                $iq->where('folio', trim($filters['folio']));
            });
        }

        // 6. Filtro por rango de fechas (Desde)
        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from'].' 00:00:00');
        }

        // 7. Filtro por rango de fechas (Hasta)
        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to'].' 23:59:59');
        }

        return $query;
    }

    /**
     * Retorna etiqueta en español amigable para la acción.
     */
    public function getActionLabel(): string
    {
        return match ($this->action) {
            'CREADA' => 'Creación de Expediente',
            'ASIGNACION' => 'Asignación Técnica',
            'CAMBIO_ESTADO' => 'Cambio de Estado',
            'INICIO_ATENCION' => 'Inicio de Atención',
            'NOTA_TECNICA' => 'Nota de Campo',
            'RESOLUCION' => 'Resolución Técnica',
            'RECHAZO_SOLUCION' => 'Rechazo de Solución',
            'CERRADA' => 'Cierre Definitivo (VoBo)',
            default => $this->action,
        };
    }

    /**
     * Retorna el icono de Google Material Symbols según el tipo de acción.
     */
    public function getActionIcon(): string
    {
        return match ($this->action) {
            'CREADA' => 'add_circle',
            'ASIGNACION' => 'person_add',
            'CAMBIO_ESTADO' => 'sync_alt',
            'INICIO_ATENCION' => 'engineering',
            'NOTA_TECNICA' => 'edit_note',
            'RESOLUCION' => 'task_alt',
            'RECHAZO_SOLUCION' => 'replay',
            'CERRADA' => 'verified',
            default => 'info',
        };
    }

    /**
     * Retorna las clases de color Tailwind para el badge táctico de la acción.
     */
    public function getActionBadgeClasses(): string
    {
        return match ($this->action) {
            'CREADA' => 'bg-primary/10 text-primary border border-primary/30',
            'ASIGNACION' => 'bg-sky-500/10 text-sky-400 border border-sky-500/30',
            'CAMBIO_ESTADO' => 'bg-amber-500/10 text-amber-400 border border-amber-500/30',
            'INICIO_ATENCION' => 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/30',
            'NOTA_TECNICA' => 'bg-cyan-500/10 text-cyan-400 border border-cyan-500/30',
            'RESOLUCION' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30',
            'RECHAZO_SOLUCION' => 'bg-rose-500/10 text-rose-400 border border-rose-500/30',
            'CERRADA' => 'bg-slate-700/60 text-slate-200 border border-slate-600/50',
            default => 'bg-surface-container-high text-on-surface-variant border border-border-subtle',
        };
    }
}
