<?php

namespace App\Models;

use Database\Factories\WaTicketFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WaTicket extends Model
{
    /** @use HasFactory<WaTicketFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_name',
        'customer_phone',
        'customer_type',
        'customer_id',
        'title',
        'description',
        'image_path',
        'type',
        'status',
        'priority',
        'assigned_to_id',
        'assigned_by_id',
        'resolved_at',
        'public_token',
    ];

    protected static function booted(): void
    {
        static::creating(function (WaTicket $ticket): void {
            if (blank($ticket->public_token)) {
                $ticket->public_token = bin2hex(random_bytes(16));
            }
        });
    }

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function publicUrl(): string
    {
        if (blank($this->public_token)) {
            $this->public_token = bin2hex(random_bytes(16));
            $this->saveQuietly();
        }

        return route('ticket.public-progress', $this->public_token);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(WaTicketNote::class, 'ticket_id')->orderBy('created_at');
    }

    protected function customerDisplayName(): Attribute
    {
        return Attribute::get(function (): string {
            return $this->customer_name ?: 'Kontak Manual';
        });
    }
}
