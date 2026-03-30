<?php

namespace App\Models;

use Database\Factories\WaChatMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaChatMessage extends Model
{
    /** @use HasFactory<WaChatMessageFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'conversation_id',
        'direction',
        'message',
        'media_type',
        'media_path',
        'media_mime',
        'media_filename',
        'sender_name',
        'sender_id',
        'wa_message_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WaConversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
