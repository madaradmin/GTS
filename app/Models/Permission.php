<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'module',
        'description',
    ];

    /**
     * Roles que tienen asignado este permiso.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions')->withTimestamps();
    }

    /**
     * Scope para filtrar permisos por módulo.
     */
    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }
}
