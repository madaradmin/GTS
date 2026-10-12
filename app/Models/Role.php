<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'badge_bg',
        'badge_text',
        'badge_border',
        'is_protected',
    ];

    /**
     * Slugs de roles protegidos contra eliminación.
     * Única excepción: ADMINISTRADOR.
     */
    public const PROTECTED_SLUGS = [
        'admin',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_protected' => 'boolean',
        ];
    }

    /**
     * Determina si el rol está institucionalmente protegido contra eliminación.
     * Regla estricta: Únicamente ADMINISTRADOR (admin) no puede eliminarse.
     */
    public function isProtectedFromDeletion(): bool
    {
        return $this->slug === 'admin';
    }

    /**
     * Determina si el rol es un rol de sistema institucionalmente protegido.
     */
    public function isProtectedSystemRole(): bool
    {
        return $this->slug === 'admin';
    }

    /**
     * Determina si el rol puede ser eliminado.
     * Regla: Si el rol NO es ADMINISTRADOR -> puede eliminarse.
     *        Si el rol ES ADMINISTRADOR -> no puede eliminarse.
     */
    public function canDelete(): bool
    {
        return ! $this->isProtectedFromDeletion();
    }

    /**
     * Usuarios asociados a este rol.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Permisos asignados a este rol.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')->withTimestamps();
    }

    /**
     * Alcances operativos originados por este rol (source).
     */
    public function operationalScopes(): HasMany
    {
        return $this->hasMany(RoleOperationalScope::class, 'source_role_id');
    }

    /**
     * Alcances operativos donde este rol es el objetivo (target).
     */
    public function targetOperationalScopes(): HasMany
    {
        return $this->hasMany(RoleOperationalScope::class, 'target_role_id');
    }

    /**
     * Comprueba si el rol posee un permiso específico (admin posee todos por bypass).
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->slug === 'admin') {
            return true;
        }

        if ($this->relationLoaded('permissions')) {
            return $this->permissions->contains('slug', $permission);
        }

        return $this->permissions()->where('slug', $permission)->exists();
    }

    /**
     * Concede un permiso al rol si no lo tiene asignado.
     */
    public function givePermission(Permission|string $permission): void
    {
        $permissionId = $permission instanceof Permission
            ? $permission->id
            : Permission::where('slug', $permission)->value('id');

        if ($permissionId) {
            $this->permissions()->syncWithoutDetaching([$permissionId]);
        }
    }

    /**
     * Revoca un permiso del rol.
     */
    public function revokePermission(Permission|string $permission): void
    {
        $permissionId = $permission instanceof Permission
            ? $permission->id
            : Permission::where('slug', $permission)->value('id');

        if ($permissionId) {
            $this->permissions()->detach($permissionId);
        }
    }

    /**
     * Sincroniza la lista de permisos del rol por IDs o slugs.
     *
     * @param  array<int, int|string|Permission>  $permissions
     */
    public function syncPermissions(array $permissions): void
    {
        $ids = [];
        foreach ($permissions as $perm) {
            if ($perm instanceof Permission) {
                $ids[] = $perm->id;
            } elseif (is_numeric($perm)) {
                $ids[] = (int) $perm;
            } elseif (is_string($perm)) {
                $id = Permission::where('slug', $perm)->value('id');
                if ($id) {
                    $ids[] = $id;
                }
            }
        }

        $this->permissions()->sync($ids);
    }

    /**
     * Resuelve el ID de un rol a partir de una instancia, ID numérico o slug.
     */
    public function resolveRoleId(Role|int|string $role): ?int
    {
        if ($role instanceof Role) {
            return $role->id;
        }

        if (is_numeric($role)) {
            return (int) $role;
        }

        return static::where('slug', $role)->value('id');
    }

    /**
     * Comprueba si este rol puede visualizar incidencias de otro rol.
     */
    public function canViewRole(Role|int|string $role): bool
    {
        if ($this->slug === 'admin') {
            return true;
        }

        $targetId = $this->resolveRoleId($role);
        if (! $targetId) {
            return false;
        }

        if ($this->relationLoaded('operationalScopes')) {
            return $this->operationalScopes
                ->where('target_role_id', $targetId)
                ->where('scope_type', 'view')
                ->isNotEmpty();
        }

        return $this->operationalScopes()
            ->where('target_role_id', $targetId)
            ->where('scope_type', 'view')
            ->exists();
    }

    /**
     * Comprueba si este rol puede atender incidencias de otro rol.
     */
    public function canAttendRole(Role|int|string $role): bool
    {
        if ($this->slug === 'admin') {
            return true;
        }

        $targetId = $this->resolveRoleId($role);
        if (! $targetId) {
            return false;
        }

        if ($this->relationLoaded('operationalScopes')) {
            return $this->operationalScopes
                ->where('target_role_id', $targetId)
                ->where('scope_type', 'attend')
                ->isNotEmpty();
        }

        return $this->operationalScopes()
            ->where('target_role_id', $targetId)
            ->where('scope_type', 'attend')
            ->exists();
    }

    /**
     * Comprueba si este rol puede asignar incidencias a otro rol.
     */
    public function canAssignToRole(Role|int|string $role): bool
    {
        if ($this->slug === 'admin') {
            return true;
        }

        $targetId = $this->resolveRoleId($role);
        if (! $targetId) {
            return false;
        }

        if ($this->relationLoaded('operationalScopes')) {
            return $this->operationalScopes
                ->where('target_role_id', $targetId)
                ->where('scope_type', 'assign')
                ->isNotEmpty();
        }

        return $this->operationalScopes()
            ->where('target_role_id', $targetId)
            ->where('scope_type', 'assign')
            ->exists();
    }

    /**
     * Retorna los IDs de roles que este rol tiene permitido visualizar.
     *
     * @return array<int, int>
     */
    public function visibleRoleIds(): array
    {
        if ($this->slug === 'admin') {
            return static::pluck('id')->all();
        }

        if ($this->relationLoaded('operationalScopes')) {
            return $this->operationalScopes
                ->where('scope_type', 'view')
                ->pluck('target_role_id')
                ->unique()
                ->values()
                ->all();
        }

        return $this->operationalScopes()
            ->where('scope_type', 'view')
            ->pluck('target_role_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Retorna los IDs de roles cuyas incidencias este rol puede atender.
     *
     * @return array<int, int>
     */
    public function attendableRoleIds(): array
    {
        if ($this->slug === 'admin') {
            return static::pluck('id')->all();
        }

        if ($this->relationLoaded('operationalScopes')) {
            return $this->operationalScopes
                ->where('scope_type', 'attend')
                ->pluck('target_role_id')
                ->unique()
                ->values()
                ->all();
        }

        return $this->operationalScopes()
            ->where('scope_type', 'attend')
            ->pluck('target_role_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Retorna los IDs de roles a los que este rol puede asignar incidencias.
     *
     * @return array<int, int>
     */
    public function assignableRoleIds(): array
    {
        if ($this->slug === 'admin') {
            return static::pluck('id')->all();
        }

        if ($this->relationLoaded('operationalScopes')) {
            return $this->operationalScopes
                ->where('scope_type', 'assign')
                ->pluck('target_role_id')
                ->unique()
                ->values()
                ->all();
        }

        return $this->operationalScopes()
            ->where('scope_type', 'assign')
            ->pluck('target_role_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Paleta táctica institucional para distintivos de roles.
     *
     * @return array<string, array{label: string, bg: string, text: string, border: string}>
     */
    public static function getColorPresets(): array
    {
        return [
            'purple' => ['label' => 'Púrpura (Admin)', 'bg' => 'bg-purple-500/10', 'text' => 'text-purple-400', 'border' => 'border-purple-500/20'],
            'cyan' => ['label' => 'Cian (GTS Técnico)', 'bg' => 'bg-cyan-500/10', 'text' => 'text-cyan-400', 'border' => 'border-cyan-500/20'],
            'amber' => ['label' => 'Ámbar (APOC-CCO)', 'bg' => 'bg-amber-500/10', 'text' => 'text-amber-400', 'border' => 'border-amber-500/20'],
            'orange' => ['label' => 'Naranja (APOC-CSA)', 'bg' => 'bg-orange-500/10', 'text' => 'text-orange-400', 'border' => 'border-orange-500/20'],
            'emerald' => ['label' => 'Esmeralda (Operaciones)', 'bg' => 'bg-emerald-500/10', 'text' => 'text-emerald-400', 'border' => 'border-emerald-500/20'],
            'blue' => ['label' => 'Azul (Comunicaciones)', 'bg' => 'bg-blue-500/10', 'text' => 'text-blue-400', 'border' => 'border-blue-500/20'],
            'rose' => ['label' => 'Rosa (Seguridad Crítica)', 'bg' => 'bg-rose-500/10', 'text' => 'text-rose-400', 'border' => 'border-rose-500/20'],
            'slate' => ['label' => 'Pizarra (Estándar)', 'bg' => 'bg-slate-500/10', 'text' => 'text-slate-400', 'border' => 'border-slate-500/20'],
        ];
    }

    /**
     * Aplica los estilos de insignia según el color táctico seleccionado.
     */
    public function setColorPreset(string $color): void
    {
        $presets = static::getColorPresets();
        if (isset($presets[$color])) {
            $this->color = $color;
            $this->badge_bg = $presets[$color]['bg'];
            $this->badge_text = $presets[$color]['text'];
            $this->badge_border = $presets[$color]['border'];
        }
    }

    /**
     * Obtiene el arreglo de clases CSS Tailwind para el badge táctico.
     *
     * @return array{bg: string, text: string, border: string}
     */
    public function getBadgeClasses(): array
    {
        return [
            'bg' => $this->badge_bg ?? 'bg-slate-500/10',
            'text' => $this->badge_text ?? 'text-slate-400',
            'border' => $this->badge_border ?? 'border-slate-500/20',
        ];
    }
}
