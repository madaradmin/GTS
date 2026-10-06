<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'code',
        'description',
        'location',
        'technical_specs',
        'icon',
        'color',
        'status',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            if ($category->status === 'ACTIVO') {
                $category->is_active = true;
            } elseif (in_array($category->status, ['INACTIVO', 'FUERA DE SERVICIO'], true)) {
                $category->is_active = false;
            } elseif ($category->is_active === false && empty($category->status)) {
                $category->status = 'INACTIVO';
            } elseif (empty($category->status)) {
                $category->status = 'ACTIVO';
            }
        });
    }

    /**
     * Incidencias clasificadas bajo este subsistema tecnológico.
     */
    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    /**
     * Incidencias abiertas/activas actualmente afectando este subsistema.
     */
    public function activeIncidents(): HasMany
    {
        return $this->hasMany(Incident::class)->whereHas('status', function ($q) {
            $q->whereIn('code', ['NUEVA', 'ASIGNADA', 'EN_ATENCION']);
        });
    }

    /**
     * Scope para filtrar subsistemas en estado activo.
     */
    public function scopeActiveStatus($query)
    {
        return $query->where('status', 'ACTIVO');
    }

    /**
     * Scope para filtrar subsistemas fuera de servicio.
     */
    public function scopeOutOfService($query)
    {
        return $query->where('status', 'FUERA DE SERVICIO');
    }

    /**
     * Scope para filtrar subsistemas inactivos.
     */
    public function scopeInactiveStatus($query)
    {
        return $query->where('status', 'INACTIVO');
    }

    /**
     * Disponibilidad operacional estimada (100% si no hay fallas críticas o activas).
     */
    public function getAvailabilityPercentageAttribute(): float
    {
        if ($this->status === 'FUERA DE SERVICIO') {
            return 0.0;
        }

        if ($this->status === 'INACTIVO') {
            return 0.0;
        }

        $activeCount = $this->activeIncidents()->count();
        if ($activeCount === 0) {
            return 100.0;
        }

        // Degradación progresiva según número de incidencias abiertas concurrentes
        $percentage = max(70.0, 100.0 - ($activeCount * 5.0));

        return round($percentage, 1);
    }
}
