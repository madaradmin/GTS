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
     *
     * @param string|array $roles
     * @return bool
     */
    public function hasRole(string|array $roles): bool
    {
        if (!$this->relationLoaded('role')) {
            $this->load('role');
        }

        if (!$this->role) {
            return false;
        }

        $roles = is_array($roles) ? $roles : func_get_args();

        return in_array($this->role->slug, $roles, true);
    }
}
