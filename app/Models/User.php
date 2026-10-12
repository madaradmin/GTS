<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role_id',
        'badge_number',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Rol institucional asignado al usuario.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Incidencias reportadas por este usuario (usualmente APOC).
     */
    public function reportedIncidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'reported_by_user_id');
    }

    /**
     * Incidencias asignadas a este usuario para su atención (usualmente GTS).
     */
    public function assignedIncidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'assigned_to_user_id');
    }

    /**
     * Acciones y notas registradas en el historial de auditoría.
     */
    public function histories(): HasMany
    {
        return $this->hasMany(IncidentHistory::class);
    }

    /**
     * Comprueba si el usuario posee uno o varios roles específicos.
     */
    public function hasRole(string|array $roles): bool
    {
        if (! $this->relationLoaded('role')) {
            $this->load('role');
        }

        if (! $this->role) {
            return false;
        }

        $roles = is_array($roles) ? $roles : func_get_args();

        return in_array($this->role->slug, $roles, true);
    }

    /**
     * Comprueba si el usuario tiene rol APOC (incluyendo variantes APOC-CCO y APOC-CSA).
     */
    public function isApoc(): bool
    {
        return $this->hasRole('apoc', 'apoc-cco', 'apoc-csa');
    }

    /**
     * Comprueba si el usuario tiene rol APOC-CCO.
     */
    public function isApocCco(): bool
    {
        if ($this->hasRole('apoc-cco')) {
            return true;
        }

        if ($this->hasRole('apoc')) {
            return str_contains(strtolower($this->username ?? ''), 'cco')
                || str_contains(strtoupper($this->badge_number ?? ''), 'CCO');
        }

        return false;
    }

    /**
     * Comprueba si el usuario tiene rol APOC-CSA.
     */
    public function isApocCsa(): bool
    {
        if ($this->hasRole('apoc-csa')) {
            return true;
        }

        if ($this->hasRole('apoc')) {
            return str_contains(strtolower($this->username ?? ''), 'csa')
                || str_contains(strtoupper($this->badge_number ?? ''), 'CSA');
        }

        return false;
    }

    /**
     * Comprueba si el usuario tiene rol GTS.
     */
    public function isGts(): bool
    {
        return $this->hasRole('gts');
    }

    /**
     * Comprueba si el usuario tiene rol Administrador.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Scope para obtener especialistas técnicos GTS.
     */
    public function scopeGtsAgents($query)
    {
        return $query->whereHas('role', fn ($q) => $q->where('slug', 'gts'));
    }

    /**
     * Obtiene la etiqueta corta del rol del usuario (Admin, GTS, CCO, CSA, APOC).
     */
    public function getRoleTag(): string
    {
        if ($this->isAdmin()) {
            return 'Admin';
        }

        if ($this->isGts()) {
            return 'GTS';
        }

        if ($this->isApocCco()) {
            return 'CCO';
        }

        if ($this->isApocCsa()) {
            return 'CSA';
        }

        if ($this->isApoc()) {
            return 'APOC';
        }

        return $this->role?->name ?? 'Usuario';
    }

    /**
     * Obtiene el nombre del usuario con el tag de su rol: |ROL| Nombre.
     */
    public function getTaggedName(): string
    {
        return "|{$this->getRoleTag()}| {$this->name}";
    }

    /**
     * Comprueba si el usuario tiene un permiso específico (admin posee bypass total).
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (! $this->relationLoaded('role')) {
            $this->load('role');
        }

        return $this->role?->hasPermission($permission) ?? false;
    }

    /**
     * Comprueba si el usuario tiene al menos uno de los permisos indicados.
     *
     * @param  array<int, string>  $permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Comprueba si el usuario tiene todos los permisos indicados.
     *
     * @param  array<int, string>  $permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        foreach ($permissions as $permission) {
            if (! $this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Comprueba si el usuario puede visualizar incidencias de un rol específico.
     */
    public function canViewRole(Role|int|string $role): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (! $this->relationLoaded('role')) {
            $this->load('role');
        }

        return $this->role?->canViewRole($role) ?? false;
    }

    /**
     * Comprueba si el usuario puede atender incidencias de un rol específico.
     */
    public function canAttendRole(Role|int|string $role): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (! $this->relationLoaded('role')) {
            $this->load('role');
        }

        return $this->role?->canAttendRole($role) ?? false;
    }

    /**
     * Comprueba si el usuario puede asignar incidencias a un rol específico.
     */
    public function canAssignToRole(Role|int|string $role): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (! $this->relationLoaded('role')) {
            $this->load('role');
        }

        return $this->role?->canAssignToRole($role) ?? false;
    }

    /**
     * Retorna los IDs de roles que el usuario puede visualizar.
     *
     * @return array<int, int>
     */
    public function visibleRoleIds(): array
    {
        if ($this->isAdmin()) {
            return Role::pluck('id')->all();
        }

        if (! $this->relationLoaded('role')) {
            $this->load('role');
        }

        return $this->role?->visibleRoleIds() ?? [];
    }

    /**
     * Retorna los IDs de roles cuyas incidencias el usuario puede atender.
     *
     * @return array<int, int>
     */
    public function attendableRoleIds(): array
    {
        if ($this->isAdmin()) {
            return Role::pluck('id')->all();
        }

        if (! $this->relationLoaded('role')) {
            $this->load('role');
        }

        return $this->role?->attendableRoleIds() ?? [];
    }

    /**
     * Retorna los IDs de roles a los cuales el usuario puede asignar incidencias.
     *
     * @return array<int, int>
     */
    public function assignableRoleIds(): array
    {
        if ($this->isAdmin()) {
            return Role::pluck('id')->all();
        }

        if (! $this->relationLoaded('role')) {
            $this->load('role');
        }

        return $this->role?->assignableRoleIds() ?? [];
    }
}
