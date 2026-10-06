<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Status extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'color',
        'step_order',
        'is_terminal',
    ];

    protected function casts(): array
    {
        return [
            'step_order' => 'integer',
            'is_terminal' => 'boolean',
        ];
    }

    /**
     * Incidencias que se encuentran actualmente en este estado.
     */
    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }
}
