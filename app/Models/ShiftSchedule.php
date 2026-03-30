<?php

namespace App\Models;

use Database\Factories\ShiftScheduleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShiftSchedule extends Model
{
    /** @use HasFactory<ShiftScheduleFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shift_definition_id',
        'schedule_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'schedule_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shiftDefinition(): BelongsTo
    {
        return $this->belongsTo(ShiftDefinition::class);
    }

    public function swapRequests(): HasMany
    {
        return $this->hasMany(ShiftSwapRequest::class, 'from_schedule_id');
    }
}
