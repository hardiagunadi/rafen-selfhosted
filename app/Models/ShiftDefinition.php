<?php

namespace App\Models;

use Database\Factories\ShiftDefinitionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShiftDefinition extends Model
{
    /** @use HasFactory<ShiftDefinitionFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'role',
        'color',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ShiftSchedule::class);
    }
}
