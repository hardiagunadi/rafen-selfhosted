<?php

namespace App\Models;

use Database\Factories\WaWebhookLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaWebhookLog extends Model
{
    /** @use HasFactory<WaWebhookLogFactory> */
    use HasFactory;

    protected $fillable = [
        'event_type',
        'session_id',
        'sender',
        'message',
        'status',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
