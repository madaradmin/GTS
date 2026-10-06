<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FolioSequence extends Model
{
    protected $primaryKey = 'year';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'year',
        'last_number',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'last_number' => 'integer',
            'updated_at' => 'datetime',
        ];
    }
}
