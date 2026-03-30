<?php

namespace App\Models;

use Database\Factories\ShiftSwapRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftSwapRequest extends Model
{
    /** @use HasFactory<ShiftSwapRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'requester_id',
        'target_id',
        'from_schedule_id',
        'to_schedule_id',
        'reason',
        'status',
        'reviewed_by_id',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_id');
    }

    public function fromSchedule(): BelongsTo
    {
        return $this->belongsTo(ShiftSchedule::class, 'from_schedule_id');
    }

    public function toSchedule(): BelongsTo
    {
        return $this->belongsTo(ShiftSchedule::class, 'to_schedule_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }
}
