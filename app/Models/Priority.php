<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Priority extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'level',
        'color',
        'sla_minutes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'sla_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Incidencias ponderadas con esta prioridad.
     */
    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }
}
