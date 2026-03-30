<?php

namespace App\Http\Controllers;

use App\Models\WaConversation;
use App\Models\WaKeywordRule;
use App\Models\WaMultiSessionDevice;
use App\Models\WaWebhookLog;
use App\Services\WaGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WaWebhookController extends Controller
{
    public function ingest(Request $request): JsonResponse
    {
        $payload = $request->all();

        if ($payload === []) {
            return response()->json(['status' => true]);
        }

        $eventType = $this->resolveIncomingEventType($payload);

        return match ($eventType) {
            'session' => $this->session($request),
            'status' => $this->status($request),
            'auto_reply' => $this->autoReply($request),
            default => $this->message($request),
        };
    }

    public function session(Request $request): JsonResponse
    {
        $payload = $request->all();
        $sessionId = $this->extractSessionId($payload);
        $status = $this->extractStatus($payload);

        WaWebhookLog::query()->create([
            'event_type' => 'session',
            'session_id' => $sessionId,
            'sender' => $this->extractSender($payload),
            'message' => null,
            'status' => $status,
            'payload' => $payload,
        ]);

        if ($sessionId !== null && $status !== null) {
            WaMultiSessionDevice::query()
                ->where('session_id', $sessionId)
                ->update([
                    'last_status' => $status,
                    'last_seen_at' => now(),
                ]);
        }

        return response()->json(['status' => true]);
    }

    public function message(Request $request): JsonResponse
    {
        return $this->handleMessageLikeEvent($request, 'message');
    }

    public function autoReply(Request $request): JsonResponse
    {
        return $this->handleMessageLikeEvent($request, 'auto_reply');
    }

    public function status(Request $request): JsonResponse
    {
        $payload = $request->all();
        $trackingContext = $payload['message_id'] ?? $payload['tracking_url'] ?? null;

        if (is_array($trackingContext)) {
            $trackingContext = json_encode($trackingContext);
        }

        WaWebhookLog::query()->create([
            'event_type' => 'status',
            'session_id' => $this->extractSessionId($payload),
            'sender' => $this->extractSender($payload),
            'message' => is_scalar($trackingContext) ? mb_substr((string) $trackingContext, 0, 1000) : null,
            'status' => $this->extractStatus($payload),
            'payload' => $payload,
        ]);

        return response()->json(['status' => true]);
    }

    private function handleMessageLikeEvent(Request $request, string $eventType): JsonResponse
    {
        $payload = $this->extractMessagePayload($request->all());
        $sender = $this->extractSender($payload);
        $message = $this->extractMessage($payload);

        WaWebhookLog::query()->create([
            'event_type' => $eventType,
            'session_id' => $this->extractSessionId($payload),
            'sender' => $sender,
            'message' => $message,
            'status' => $this->extractStatus($payload),
            'payload' => $payload,
        ]);

        if ($eventType !== 'message') {
            return response()->json([
                'status' => true,
            ]);
        }

        if ($sender === null || $message === null || $this->isTruthy($payload['fromMe'] ?? null) || $this->isGroupMessage($payload)) {
            return response()->json([
                'status' => true,
                'ignored' => true,
            ]);
        }

        $conversation = WaConversation::query()->firstOrCreate(
            ['contact_phone' => $sender],
            [
                'session_id' => $this->extractSessionId($payload),
                'contact_name' => $this->extractContactName($payload),
                'status' => 'open',
                'last_message_at' => now(),
            ],
        );

        $conversation->fill([
            'session_id' => $this->extractSessionId($payload) ?: $conversation->session_id,
            'contact_name' => $this->extractContactName($payload) ?: $conversation->contact_name,
        ])->save();

        $conversation->messages()->create([
            'direction' => 'inbound',
            'message' => $message,
            'sender_name' => $conversation->contact_name,
            'wa_message_id' => $this->extractMessageId($payload),
            'created_at' => now(),
        ]);

        $conversation->updateFromIncoming($message);

        $replyText = $this->checkKeywordRules($message);

        if ($replyText !== null) {
            $service = WaGatewayService::fromSettings();

            if ($service?->isConfigured()) {
                if ($conversation->session_id) {
                    $service->setSessionId($conversation->session_id);
                }

                if ($service->sendMessage($conversation->contact_phone, $replyText)) {
                    $conversation->messages()->create([
                        'direction' => 'outbound',
                        'message' => $replyText,
                        'sender_name' => 'Bot WhatsApp',
                        'created_at' => now(),
                    ]);

                    $conversation->update([
                        'last_message' => mb_substr($replyText, 0, 500),
                        'last_message_at' => now(),
                    ]);
                }
            }
        }

        return response()->json([
            'status' => true,
            'conversation_id' => $conversation->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveIncomingEventType(array $payload): string
    {
        $normalizedPayload = $this->extractMessagePayload($payload);
        $explicitEvent = strtolower(trim((string) ($payload['event'] ?? $payload['type'] ?? '')));
        $status = strtolower(trim((string) ($payload['message_status'] ?? $payload['status'] ?? '')));

        if ($explicitEvent === 'auto_reply' || $explicitEvent === 'autoreply') {
            return 'auto_reply';
        }

        if ($explicitEvent === 'session') {
            return 'session';
        }

        if ($explicitEvent === 'status') {
            return 'status';
        }

        if ($status !== '' && isset($normalizedPayload['message']) === false && isset($normalizedPayload['text']) === false && isset($normalizedPayload['body']) === false) {
            return 'status';
        }

        if ($status !== '' && in_array($status, ['connected', 'connecting', 'disconnected', 'open', 'close', 'closed', 'online', 'offline'], true)) {
            return 'session';
        }

        return 'message';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function extractMessagePayload(array $payload): array
    {
        $data = $payload['data'] ?? null;

        if (is_array($data) && is_array($data[0] ?? null)) {
            return $data[0];
        }

        if (is_array($data)) {
            return $data;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractSender(array $payload): ?string
    {
        $sender = $payload['sender'] ?? $payload['from'] ?? $payload['phone'] ?? null;

        if (! is_scalar($sender)) {
            return null;
        }

        $service = WaGatewayService::fromSettings();
        $normalizedSender = $service?->normalizePhone((string) $sender) ?? trim((string) $sender);

        return $normalizedSender !== '' ? $normalizedSender : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractMessage(array $payload): ?string
    {
        $rawMessage = $payload['message'] ?? $payload['text'] ?? $payload['body'] ?? $payload['caption'] ?? null;

        if (is_array($rawMessage)) {
            $rawMessage = $rawMessage['text'] ?? $rawMessage['conversation'] ?? $rawMessage['body'] ?? null;
        }

        if (! is_scalar($rawMessage)) {
            return null;
        }

        $message = trim((string) $rawMessage);

        return $message !== '' ? $message : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractStatus(array $payload): ?string
    {
        $status = $payload['message_status'] ?? $payload['status'] ?? $payload['msg_status'] ?? $payload['state'] ?? null;

        if (! is_scalar($status)) {
            return null;
        }

        $value = trim((string) $status);

        return $value !== '' ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractSessionId(array $payload): ?string
    {
        $sessionId = $payload['session'] ?? $payload['session_id'] ?? $payload['device'] ?? $payload['device_id'] ?? null;

        if (! is_scalar($sessionId)) {
            return null;
        }

        $value = trim((string) $sessionId);

        return $value !== '' ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractContactName(array $payload): ?string
    {
        $name = $payload['pushName'] ?? $payload['contact_name'] ?? $payload['sender_name'] ?? $payload['name'] ?? null;

        if (! is_scalar($name)) {
            return null;
        }

        $value = trim((string) $name);

        return $value !== '' ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractMessageId(array $payload): ?string
    {
        $messageId = $payload['id'] ?? $payload['message_id'] ?? $payload['wa_message_id'] ?? null;

        if (! is_scalar($messageId)) {
            return null;
        }

        $value = trim((string) $messageId);

        return $value !== '' ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function isGroupMessage(array $payload): bool
    {
        if ($this->isTruthy($payload['isGroup'] ?? null)) {
            return true;
        }

        $sender = strtolower((string) ($payload['sender'] ?? $payload['from'] ?? ''));

        return str_contains($sender, '@g.us');
    }

    private function isTruthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }

    private function checkKeywordRules(string $message): ?string
    {
        $normalizedMessage = mb_strtolower($message);

        $rules = WaKeywordRule::query()
            ->where('is_active', true)
            ->orderBy('priority')
            ->orderBy('id')
            ->get();

        foreach ($rules as $rule) {
            foreach ((array) $rule->keywords as $keyword) {
                $normalizedKeyword = mb_strtolower(trim((string) $keyword));

                if ($normalizedKeyword !== '' && str_contains($normalizedMessage, $normalizedKeyword)) {
                    return $rule->reply_text;
                }
            }
        }

        return null;
    }
}
