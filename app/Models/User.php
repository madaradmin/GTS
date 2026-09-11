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
        'shift_status',
        'shift_status_updated_at',
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
            'shift_status_updated_at' => 'datetime',
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

    /**
     * Comprueba si el usuario tiene rol APOC.
     */
    public function isApoc(): bool
    {
        return $this->hasRole('apoc');
    }

    /**
     * Historial de cambios de disponibilidad operacional en turnos.
     */
    public function availabilityLogs(): HasMany
    {
        return $this->hasMany(AgentAvailabilityLog::class)->orderBy('created_at', 'desc');
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
     * Comprueba si el agente está en estado ACTIVO / DISPONIBLE para recibir asignaciones.
     */
    public function isShiftActive(): bool
    {
        return strtoupper($this->shift_status ?? 'ACTIVO') === 'ACTIVO';
    }

    /**
     * Comprueba si el agente está en estado NO DISPONIBLE (receso, descanso o fuera de turno).
     */
    public function isShiftUnavailable(): bool
    {
        return strtoupper($this->shift_status ?? '') === 'NO DISPONIBLE';
    }

    /**
     * Actualiza el estado de disponibilidad del agente y genera el registro en la bitácora histórica.
     */
    public function setShiftStatus(string $newStatus, ?string $reason = null, ?string $ipAddress = null): void
    {
        $oldStatus = $this->shift_status ?? 'ACTIVO';

        $this->shift_status = $newStatus;
        $this->shift_status_updated_at = now();
        $this->save();

        AgentAvailabilityLog::create([
            'user_id' => $this->id,
            'status' => $newStatus,
            'previous_status' => $oldStatus,
            'reason' => $reason,
            'ip_address' => $ipAddress,
            'created_at' => now(),
        ]);
    }

    /**
     * Scope para filtrar usuarios con estado de turno ACTIVO.
     */
    public function scopeShiftActive($query)
    {
        return $query->where('shift_status', 'ACTIVO');
    }

    /**
     * Scope para filtrar usuarios con estado NO DISPONIBLE.
     */
    public function scopeShiftUnavailable($query)
    {
        return $query->where('shift_status', 'NO DISPONIBLE');
    }

    /**
     * Scope para obtener especialistas técnicos GTS.
     */
    public function scopeGtsAgents($query)
    {
        return $query->whereHas('role', fn($q) => $q->where('slug', 'gts'));
    }
}

