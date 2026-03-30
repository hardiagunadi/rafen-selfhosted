<?php

namespace App\Models;

use Database\Factories\OutageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Outage extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_RESOLVED = 'resolved';

    /** @use HasFactory<OutageFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'status',
        'severity',
        'started_at',
        'estimated_resolved_at',
        'resolved_at',
        'public_token',
        'wa_blast_sent_at',
        'wa_blast_count',
        'resolution_wa_sent_at',
        'created_by_id',
        'include_status_link',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'estimated_resolved_at' => 'datetime',
            'resolved_at' => 'datetime',
            'wa_blast_sent_at' => 'datetime',
            'resolution_wa_sent_at' => 'datetime',
            'wa_blast_count' => 'integer',
            'include_status_link' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Outage $outage): void {
            if (! is_string($outage->public_token) || $outage->public_token === '') {
                $outage->public_token = bin2hex(random_bytes(16));
            }
        });
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function affectedAreas(): HasMany
    {
        return $this->hasMany(OutageAffectedArea::class, 'outage_id');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(OutageUpdate::class, 'outage_id')->orderBy('created_at');
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }
}
