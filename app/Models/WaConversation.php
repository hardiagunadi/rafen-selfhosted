<?php

namespace App\Models;

use Database\Factories\WaConversationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WaConversation extends Model
{
    /** @use HasFactory<WaConversationFactory> */
    use HasFactory;

    protected $fillable = [
        'session_id',
        'contact_phone',
        'contact_name',
        'assigned_to_id',
        'status',
        'bot_paused_until',
        'last_message',
        'last_message_at',
        'unread_count',
    ];

    protected function casts(): array
    {
        return [
            'bot_paused_until' => 'datetime',
            'last_message_at' => 'datetime',
            'unread_count' => 'integer',
        ];
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WaChatMessage::class, 'conversation_id');
    }

    public function updateFromIncoming(string $message): void
    {
        $this->update([
            'last_message' => mb_substr($message, 0, 500),
            'last_message_at' => now(),
            'status' => 'open',
            'unread_count' => $this->unread_count + 1,
        ]);
    }
}
