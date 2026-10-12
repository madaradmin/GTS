<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentAssignedArea extends Model
{
    use HasFactory;

    protected $fillable = [
        'incident_id',
        'area_code',
        'area_name',
        'role_id',
    ];

    /**
     * Incidencia a la cual pertenece la asignación.
     */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    /**
     * Rol asociado al área si corresponde a una entidad interna del sistema.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}

