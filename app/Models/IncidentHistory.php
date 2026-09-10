<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentHistory extends Model
{
    use HasFactory;

    // La auditoría es estrictamente Append-Only (sin updated_at)
    public const UPDATED_AT = null;

    protected $fillable = [
        'incident_id',
        'user_id',
        'action',
        'field_name',
        'old_value',
        'new_value',
        'comment',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * Incidencia sobre la que se ejecutó la acción.
     */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    /**
     * Usuario responsable que ejecutó la acción.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
