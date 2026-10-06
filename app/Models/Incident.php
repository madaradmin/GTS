<?php

namespace App\Models;

use App\Services\NotificationService;
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
        'assigned_specialist',
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
     * Intercepción de eventos de ciclo de vida del modelo Incident.
     */
    protected static function booted(): void
    {
        static::deleting(function (Incident $incident) {
            // Purgar de forma controlada y en cascada todas las notificaciones asociadas al expediente
            app(NotificationService::class)->deleteNotificationsForIncident($incident->id, $incident->folio);
        });
    }

    /**
     * Utilizar el folio inmutable para el enlace de modelos en rutas (Route Model Binding).
     */
    public function getRouteKeyName(): string
    {
        return 'folio';
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
     * Determina el grupo operativo emisor de la incidencia (CCO, CSA, GTS, ADMIN).
     */
    public function getOriginGroupAttribute(): ?string
    {
        if (! $this->relationLoaded('reportedBy')) {
            $this->load('reportedBy.role');
        }

        $reporter = $this->reportedBy;
        if (! $reporter) {
            return null;
        }

        if ($reporter->isApocCco()) {
            return 'CCO';
        }

        if ($reporter->isApocCsa()) {
            return 'CSA';
        }

        // Compatibilidad con usuarios de prueba o roles heredados
        if ($reporter->role && $reporter->role->slug === 'apoc') {
            if (str_contains(strtolower($reporter->username ?? ''), 'cco') || str_contains(strtoupper($reporter->badge_number ?? ''), 'CCO')) {
                return 'CCO';
            }
            if (str_contains(strtolower($reporter->username ?? ''), 'csa') || str_contains(strtoupper($reporter->badge_number ?? ''), 'CSA')) {
                return 'CSA';
            }

            return 'APOC';
        }

        if ($reporter->isGts()) {
            return 'GTS';
        }

        if ($reporter->isAdmin()) {
            return 'ADMIN';
        }

        return $reporter->role?->name ?? 'GTS';
    }

    /**
     * Comprueba si la incidencia fue reportada por un operador del grupo APOC-CCO.
     */
    public function isFromCco(): bool
    {
        return $this->origin_group === 'CCO';
    }

    /**
     * Comprueba si la incidencia fue reportada por un operador del grupo APOC-CSA.
     */
    public function isFromCsa(): bool
    {
        return $this->origin_group === 'CSA';
    }

    /**
     * Comprueba si la incidencia fue reportada por un especialista o personal de GTS.
     */
    public function isFromGts(): bool
    {
        return $this->origin_group === 'GTS';
    }

    /**
     * Comprueba si la incidencia fue reportada por un Administrador del sistema.
     */
    public function isFromAdmin(): bool
    {
        return $this->origin_group === 'ADMIN';
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
            $q->where('incidents.folio', $like, "%{$term}%")
                ->orWhere('incidents.title', $like, "%{$term}%")
                ->orWhere('incidents.location', $like, "%{$term}%")
                ->orWhere('incidents.description', $like, "%{$term}%")
                ->orWhereHas('category', fn ($c) => $c->where('name', $like, "%{$term}%"))
                ->orWhereHas('reportedBy', fn ($u) => $u->where('name', $like, "%{$term}%")->orWhere('badge_number', $like, "%{$term}%"))
                ->orWhereHas('assignedTo', fn ($u) => $u->where('name', $like, "%{$term}%"))
                ->orWhere('incidents.assigned_specialist', $like, "%{$term}%");
        });
    }

    /**
     * Determina si la incidencia está asignada al área/especialista TICS.
     */
    public function isAssignedToTics(): bool
    {
        return $this->assigned_specialist === 'TICS';
    }

    /**
     * Retorna el nombre visible del especialista asignado (Usuario GTS o TICS).
     */
    public function getSpecialistNameAttribute(): ?string
    {
        if ($this->isAssignedToTics()) {
            return 'TICS';
        }

        return $this->assignedTo?->name;
    }

    /**
     * Scope para filtrar incidencias pertenecientes al ámbito operativo de APOC-CCO.
     */
    public function scopeForCco(Builder $query): Builder
    {
        $like = $query->getConnection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        return $query->whereHas('reportedBy', function (Builder $q) use ($like) {
            $q->whereHas('role', fn (Builder $rq) => $rq->where('slug', 'apoc-cco'))
                ->orWhere(function (Builder $legacy) use ($like) {
                    $legacy->whereHas('role', fn (Builder $rq) => $rq->where('slug', 'apoc'))
                        ->where(function (Builder $match) use ($like) {
                            $match->where('username', $like, '%cco%')
                                ->orWhere('badge_number', $like, '%CCO%');
                        });
                });
        });
    }

    /**
     * Scope para filtrar incidencias pertenecientes al ámbito operativo de APOC-CSA.
     */
    public function scopeForCsa(Builder $query): Builder
    {
        $like = $query->getConnection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        return $query->whereHas('reportedBy', function (Builder $q) use ($like) {
            $q->whereHas('role', fn (Builder $rq) => $rq->where('slug', 'apoc-csa'))
                ->orWhere(function (Builder $legacy) use ($like) {
                    $legacy->whereHas('role', fn (Builder $rq) => $rq->where('slug', 'apoc'))
                        ->where(function (Builder $match) use ($like) {
                            $match->where('username', $like, '%csa%')
                                ->orWhere('badge_number', $like, '%CSA%');
                        });
                });
        });
    }

    /**
     * Scope para aplicar visibilidad de incidencias según el perfil del usuario autenticado.
     * APOC-CCO: únicamente incidencias del ámbito CCO (propias y de otros operadores CCO).
     * APOC-CSA: únicamente incidencias del ámbito CSA (propias y de otros operadores CSA).
     * GTS y ADMINISTRADOR: visibilidad global sin restricción.
     */
    public function scopeForUserScope(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query;
        }

        if ($user->isAdmin() || $user->isGts()) {
            return $query;
        }

        if ($user->isApocCco()) {
            return $query->forCco();
        }

        if ($user->isApocCsa()) {
            return $query->forCsa();
        }

        if ($user->isApoc()) {
            return $query->where('incidents.reported_by_user_id', $user->id);
        }

        return $query;
    }

    /**
     * Scope para aplicar filtros combinados de búsqueda, estado, prioridad y categoría.
     *
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(! empty($filters['search']), fn ($q) => $q->search($filters['search']))
            ->when(! empty($filters['status_id']), fn ($q) => $q->where('incidents.status_id', $filters['status_id']))
            ->when(! empty($filters['priority_id']), fn ($q) => $q->where('incidents.priority_id', $filters['priority_id']))
            ->when(! empty($filters['category_id']), fn ($q) => $q->where('incidents.category_id', $filters['category_id']))
            ->when(! empty($filters['assigned_to']), fn ($q) => $q->where('incidents.assigned_to_user_id', $filters['assigned_to']))
            ->when(! empty($filters['reported_by']), fn ($q) => $q->where('incidents.reported_by_user_id', $filters['reported_by']));
    }
}
