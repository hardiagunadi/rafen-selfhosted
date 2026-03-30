<?php

namespace App\Models;

use Database\Factories\WaBlastLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaBlastLog extends Model
{
    /** @use HasFactory<WaBlastLogFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'sent_by_id',
        'sent_by_name',
        'event',
        'target_type',
        'target_id',
        'phone',
        'phone_normalized',
        'status',
        'reason',
        'customer_name',
        'ref_id',
        'message',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_id');
    }
}
