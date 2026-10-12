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
        if (! $this->relationLoaded('reportedBy') && $this->exists) {
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
     * Obtiene los metadatos visuales del distintivo táctico de procedencia (área emisora).
     * Soporta roles fijos (CCO, CSA, GTS, ADMIN) y cualquier rol personalizado actual y futuro.
     *
     * @return array{label: string, title: string, bg: string, text: string, border: string}|null
     */
    public function getOriginBadgeAttribute(): ?array
    {
        if (! $this->relationLoaded('reportedBy') && $this->exists) {
            $this->load('reportedBy.role');
        }

        $reporter = $this->reportedBy;
        if (! $reporter) {
            return null;
        }

        // 1. APOC-CCO
        if ($this->isFromCco()) {
            return [
                'label' => 'CCO',
                'title' => 'Procedencia: APOC-CCO',
                'bg' => 'bg-amber-500/10',
                'text' => 'text-amber-400',
                'border' => 'border-amber-500/20',
            ];
        }

        // 2. APOC-CSA
        if ($this->isFromCsa()) {
            return [
                'label' => 'CSA',
                'title' => 'Procedencia: APOC-CSA',
                'bg' => 'bg-orange-500/10',
                'text' => 'text-orange-400',
                'border' => 'border-orange-500/20',
            ];
        }

        // 3. GTS
        if ($this->isFromGts()) {
            return [
                'label' => 'GTS',
                'title' => 'Procedencia: GTS',
                'bg' => 'bg-cyan-500/10',
                'text' => 'text-cyan-400',
                'border' => 'border-cyan-500/20',
            ];
        }

        // 4. Administrador
        if ($this->isFromAdmin()) {
            return [
                'label' => 'ADMIN',
                'title' => 'Procedencia: Administrador',
                'bg' => 'bg-purple-500/10',
                'text' => 'text-purple-400',
                'border' => 'border-purple-500/20',
            ];
        }

        // 5. Roles personalizados o dinámicos presentes y futuros
        $role = $reporter->role;
        $label = $role?->name ?? $this->origin_group ?? 'GTS';

        $bg = ! empty($role?->badge_bg) ? $role->badge_bg : 'bg-slate-500/10';
        $text = ! empty($role?->badge_text) ? $role->badge_text : 'text-slate-400';
        $border = ! empty($role?->badge_border) ? $role->badge_border : 'border-slate-500/20';

        return [
            'label' => $label,
            'title' => 'Procedencia: ' . $label,
            'bg' => $bg,
            'text' => $text,
            'border' => $border,
        ];
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
     * Áreas o departamentos responsables asignados a la incidencia.
     */
    public function assignedAreas(): HasMany
    {
        return $this->hasMany(IncidentAssignedArea::class);
    }

    /**
     * Retorna la lista de códigos de áreas asignadas.
     *
     * @return array<int, string>
     */
    public function assignedAreaCodes(): array
    {
        if ($this->relationLoaded('assignedAreas')) {
            return $this->assignedAreas->pluck('area_code')->all();
        }

        return $this->assignedAreas()->pluck('area_code')->all();
    }

    /**
     * Retorna la lista de nombres visibles de áreas asignadas.
     *
     * @return array<int, string>
     */
    public function assignedAreaNames(): array
    {
        if ($this->relationLoaded('assignedAreas')) {
            return $this->assignedAreas->pluck('area_name')->all();
        }

        return $this->assignedAreas()->pluck('area_name')->all();
    }

    /**
     * Determina si la incidencia está asignada a un área específica.
     */
    public function isAssignedToArea(string $areaCode): bool
    {
        return in_array(strtolower($areaCode), array_map('strtolower', $this->assignedAreaCodes()), true);
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
                ->orWhereHas('assignedAreas', fn ($a) => $a->where('area_name', $like, "%{$term}%")->orWhere('area_code', $like, "%{$term}%"))
                ->orWhere('incidents.assigned_specialist', $like, "%{$term}%");
        });
    }

    /**
     * Determina si la incidencia está asignada al área/especialista TICS.
     */
    public function isAssignedToTics(): bool
    {
        return $this->assigned_specialist === 'TICS' || $this->isAssignedToArea('tics');
    }

    /**
     * Retorna el nombre visible del especialista asignado (Usuario GTS o TICS).
     */
    public function getSpecialistNameAttribute(): ?string
    {
        if ($this->assignedTo) {
            return $this->assignedTo->name;
        }

        if ($this->isAssignedToTics() && (empty($this->assignedAreaCodes()) || $this->assignedAreaCodes() === ['tics'])) {
            return 'TICS';
        }

        $names = $this->assignedAreaNames();
        if (! empty($names)) {
            return implode(', ', $names);
        }

        if ($this->isAssignedToTics()) {
            return 'TICS';
        }

        return null;
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
     * Scope para aplicar visibilidad dinámica de incidencias según permisos y alcances operativos del usuario.
     */
    public function scopeVisibleToUser(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? \Illuminate\Support\Facades\Auth::user();

        if (! $user) {
            return $query;
        }

        if ($user->isAdmin() || $user->hasPermission('incidents.view_all')) {
            return $query;
        }

        $canViewAssigned = $user->hasPermission('incidents.view_assigned');
        $canViewDepartment = $user->hasPermission('incidents.view_department');
        $isUnseededFallback = ! \App\Models\Permission::exists() && ($user->isGts() || $user->isApoc());

        if (! $canViewAssigned && ! $canViewDepartment && ! $isUnseededFallback) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $sub) use ($user, $canViewAssigned, $canViewDepartment, $isUnseededFallback) {
            $applied = true;
            $sub->where('incidents.reported_by_user_id', $user->id);

            if ($canViewAssigned) {
                $sub->orWhere('incidents.assigned_to_user_id', $user->id);
                if ($user->role_id) {
                    $sub->orWhereHas('assignedAreas', fn (Builder $aq) => $aq->where('role_id', $user->role_id));
                }
            }

            if ($canViewDepartment) {
                $deptCondition = function (Builder $dq) use ($user) {
                    $dq->where('incidents.reported_by_user_id', $user->id);

                    $visibleRoleIds = $user->visibleRoleIds();
                    if (! empty($visibleRoleIds)) {
                        $dq->orWhereHas('reportedBy', function (Builder $rq) use ($visibleRoleIds) {
                            $rq->whereIn('role_id', $visibleRoleIds);
                        })->orWhereHas('assignedAreas', function (Builder $aq) use ($visibleRoleIds) {
                            $aq->whereIn('role_id', $visibleRoleIds);
                        });
                    }

                    if ($user->isApocCco()) {
                        $dq->orWhere(fn (Builder $q) => $q->forCco());
                    }

                    if ($user->isApocCsa()) {
                        $dq->orWhere(fn (Builder $q) => $q->forCsa());
                    }
                };

                if ($applied) {
                    $sub->orWhere($deptCondition);
                } else {
                    $sub->where($deptCondition);
                    $applied = true;
                }
            }

            if ($isUnseededFallback) {
                if ($user->isGts()) {
                    if ($applied) {
                        $sub->orWhereRaw('1 = 1');
                    } else {
                        $sub->whereRaw('1 = 1');
                    }
                } else {
                    $unseededCondition = function (Builder $uq) use ($user) {
                        $uq->where('incidents.reported_by_user_id', $user->id)
                            ->orWhere('incidents.assigned_to_user_id', $user->id);
                        if ($user->isApocCco()) {
                            $uq->orWhere(fn (Builder $q) => $q->forCco());
                        }
                        if ($user->isApocCsa()) {
                            $uq->orWhere(fn (Builder $q) => $q->forCsa());
                        }
                    };

                    if ($applied) {
                        $sub->orWhere($unseededCondition);
                    } else {
                        $sub->where($unseededCondition);
                    }
                }
            }
        });
    }

    /**
     * Scope para aplicar visibilidad de incidencias según el perfil del usuario autenticado.
     * Mantiene compatibilidad delegando a scopeVisibleToUser.
     */
    public function scopeForUserScope(Builder $query, ?User $user): Builder
    {
        return $this->scopeVisibleToUser($query, $user);
    }

    /**
     * Scope para filtrar incidencias que el usuario tiene permitido atender/tomar.
     */
    public function scopeAttendableByUser(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if (! $user->isAdmin() && ! $user->isGts() && ! $user->hasPermission('incidents.attend')) {
            return $query->whereRaw('1 = 0');
        }

        $query->whereHas('status', fn (Builder $sq) => $sq->whereIn('code', ['NUEVA', 'ASIGNADA', 'EN_ATENCION']));

        if ($user->isAdmin() || $user->isGts() || $user->hasPermission('incidents.view_all')) {
            return $query;
        }

        $attendableRoleIds = $user->attendableRoleIds();

        return $query->where(function (Builder $sub) use ($user, $attendableRoleIds) {
            $sub->where('incidents.assigned_to_user_id', $user->id);

            if (! empty($attendableRoleIds)) {
                $sub->orWhereHas('reportedBy', function (Builder $rq) use ($attendableRoleIds) {
                    $rq->whereIn('role_id', $attendableRoleIds);
                });
            }
        });
    }

    /**
     * Scope para filtrar incidencias que el usuario tiene permitido asignar.
     */
    public function scopeAssignableByUser(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if (! $user->isAdmin() && ! $user->isGts() && ! $user->hasPermission('incidents.assign')) {
            return $query->whereRaw('1 = 0');
        }

        $query->whereHas('status', fn (Builder $sq) => $sq->where('is_terminal', false));

        if ($user->isAdmin() || $user->isGts() || $user->hasPermission('incidents.view_all')) {
            return $query;
        }

        $visibleRoleIds = $user->visibleRoleIds();

        return $query->where(function (Builder $sub) use ($user, $visibleRoleIds) {
            $sub->where('incidents.reported_by_user_id', $user->id)
                ->orWhere('incidents.assigned_to_user_id', $user->id);

            if (! empty($visibleRoleIds)) {
                $sub->orWhereHas('reportedBy', function (Builder $rq) use ($visibleRoleIds) {
                    $rq->whereIn('role_id', $visibleRoleIds);
                });
            }
        });
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
