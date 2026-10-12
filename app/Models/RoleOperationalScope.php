<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleOperationalScope extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_role_id',
        'target_role_id',
        'scope_type',
    ];

    /**
     * Rol origen (quien posee el alcance).
     */
    public function sourceRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'source_role_id');
    }

    /**
     * Rol objetivo (hacia quien se proyecta el alcance).
     */
    public function targetRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'target_role_id');
    }

    /**
     * Scope para filtrar por tipo de alcance ('view', 'attend', 'assign').
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('scope_type', $type);
    }
}
