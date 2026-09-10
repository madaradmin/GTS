<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Incident extends Model
{
    use HasFactory;

    protected $fillable = [
        'folio',
        'title',
        'description',
        'location',
        'category_id',
        'priority_id',
        'status_id',
        'reported_by_user_id',
        'assigned_to_user_id',
        'solution_description',
        'rejection_reason',
        'reported_at',
        'assigned_at',
        'attended_at',
        'resolved_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
            'assigned_at' => 'datetime',
            'attended_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * Categoría / Subsistema tecnológico afectado.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Nivel de prioridad y SLA asignado.
     */
    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class);
    }

    /**
     * Estado operacional actual en el ciclo de vida.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    /**
     * Operador APOC que reportó la incidencia.
     */
    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    /**
     * Especialista GTS asignado para atender la incidencia.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * Línea de tiempo y auditoría de eventos sobre la incidencia.
     */
    public function histories(): HasMany
    {
        return $this->hasMany(IncidentHistory::class)->orderBy('created_at', 'asc');
    }

    /**
     * Archivos y capturas diagnósticas adjuntas.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(IncidentAttachment::class);
    }

    /**
     * Scope para filtrar incidencias activas (no cerradas).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereHas('status', function ($q) {
            $q->where('is_terminal', false);
        });
    }

    /**
     * Scope para búsqueda rápida por folio, título o ubicación.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $term = trim($term);
        $like = $query->getConnection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        return $query->where(function ($q) use ($term, $like) {
            $q->where('folio', $like, "%{$term}%")
              ->orWhere('title', $like, "%{$term}%")
              ->orWhere('location', $like, "%{$term}%");
        });
    }

    /**
     * Scope para aplicar filtros combinados de búsqueda, estado, prioridad y categoría.
     *
     * @param array<string, mixed> $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(!empty($filters['search']), fn ($q) => $q->search($filters['search']))
            ->when(!empty($filters['status_id']), fn ($q) => $q->where('status_id', $filters['status_id']))
            ->when(!empty($filters['priority_id']), fn ($q) => $q->where('priority_id', $filters['priority_id']))
            ->when(!empty($filters['category_id']), fn ($q) => $q->where('category_id', $filters['category_id']));
    }
}

