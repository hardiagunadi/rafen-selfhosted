<?php

namespace App\Services;

use App\Models\WaGatewaySetting;
use App\Models\WaMultiSessionDevice;
use Illuminate\Support\Facades\Http;

class WaGatewayService
{
    public function __construct(
        private string $url,
        private string $token,
        private string $key = '',
        private ?string $sessionId = null,
    ) {}

    public static function fromSettings(?WaGatewaySetting $settings = null): ?self
    {
        $settings ??= WaGatewaySetting::instance();

        if (! $settings->is_enabled) {
            return null;
        }

        $gatewayUrl = trim($settings->resolvedGatewayUrl());
        $token = trim($settings->resolvedAuthToken());
        $key = trim($settings->resolvedMasterKey());

        if ($gatewayUrl === '' || $token === '') {
            return null;
        }

        return new self(rtrim($gatewayUrl, '/'), $token, $key);
    }

    public function isConfigured(): bool
    {
        return trim($this->url) !== '' && trim($this->token) !== '';
    }

    public function setSessionId(?string $sessionId): self
    {
        $trimmedSessionId = trim((string) $sessionId);
        $this->sessionId = $trimmedSessionId !== '' ? $trimmedSessionId : null;

        return $this;
    }

    public function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone) ?? '';
        $phone = ltrim($phone, '+');

        if (str_starts_with($phone, '0')) {
            $phone = '62'.substr($phone, 1);
        }

        return $phone;
    }

    public function isValidPhone(string $normalized): bool
    {
        return (bool) preg_match('/^62\d{8,13}$/', $normalized);
    }

    /**
     * @return array<string, mixed>
     */
    public function testConnection(): array
    {
        $candidates = [
            '/api/device/info',
            '/api/v2/device/info',
            '/api/v2/sessions/status?session='.$this->resolveSessionId(),
            '/status',
        ];

        $lastError = '';

        foreach ($candidates as $path) {
            try {
                $response = Http::timeout(10)
                    ->withHeaders($this->buildHeaders())
                    ->get($this->url.$path);

                if ($response->successful()) {
                    return [
                        'status' => true,
                        'message' => 'Koneksi berhasil (endpoint: '.$path.')',
                        'http_status' => $response->status(),
                        'data' => $response->json(),
                    ];
                }

                if (in_array($response->status(), [401, 403], true)) {
                    return [
                        'status' => false,
                        'message' => 'Gateway dapat dijangkau tetapi token/key ditolak.',
                        'http_status' => $response->status(),
                        'data' => $response->body(),
                    ];
                }

                $lastError = 'HTTP '.$response->status().' pada '.$path;
            } catch (\Throwable $throwable) {
                $lastError = $throwable->getMessage();

                if (
                    str_contains($throwable->getMessage(), 'Could not resolve')
                    || str_contains($throwable->getMessage(), 'Connection refused')
                    || str_contains($throwable->getMessage(), 'timed out')
                ) {
                    return [
                        'status' => false,
                        'message' => 'Tidak dapat terhubung ke gateway: '.$throwable->getMessage(),
                        'http_status' => 0,
                        'network_error' => true,
                    ];
                }
            }
        }

        return [
            'status' => false,
            'message' => 'Gateway tidak merespons pada endpoint yang diketahui. '.$lastError,
            'http_status' => 0,
        ];
    }

    public function sendMessage(string $phone, string $message): bool
    {
        $normalizedPhone = $this->normalizePhone($phone);

        if (! $this->isValidPhone($normalizedPhone)) {
            return false;
        }

        $sessionId = $this->resolveSessionId();

        if ($sessionId === '') {
            return false;
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders($this->buildHeaders())
                ->post($this->url.'/api/v2/send-message', [
                    'data' => [[
                        'session' => $sessionId,
                        'phone' => $normalizedPhone,
                        'message' => $message,
                        'isGroup' => false,
                        'ref_id' => 'selfhosted-'.now()->format('YmdHis').'-'.bin2hex(random_bytes(4)),
                    ]],
                ]);

            if (! $response->successful()) {
                return false;
            }

            $body = $response->json();
            $status = strtolower((string) ($body['data']['messages'][0]['status'] ?? ''));

            return (bool) ($body['status'] ?? false)
                && ! in_array($status, ['failed', 'error', 'undelivered'], true);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function restartSession(?string $sessionId = null): array
    {
        return $this->callSessionEndpoint('/api/v2/sessions/restart', $sessionId);
    }

    /**
     * @return array<string, mixed>
     */
    public function sessionStatus(?string $sessionId = null): array
    {
        $targetSession = $sessionId ?: $this->resolveSessionId();

        try {
            $response = Http::timeout(10)
                ->withHeaders($this->buildHeaders())
                ->get($this->url.'/api/v2/sessions/status', [
                    'session' => $targetSession,
                ]);

            if ($response->successful()) {
                $body = $response->json();

                return [
                    'status' => true,
                    'message' => 'Status sesi berhasil diambil.',
                    'data' => $body['data'] ?? $body,
                    'http_status' => $response->status(),
                ];
            }

            return [
                'status' => false,
                'message' => 'Gagal membaca status sesi (HTTP '.$response->status().').',
                'data' => $response->json() ?? $response->body(),
                'http_status' => $response->status(),
            ];
        } catch (\Throwable $throwable) {
            return [
                'status' => false,
                'message' => 'Tidak dapat membaca status sesi: '.$throwable->getMessage(),
                'http_status' => 0,
                'network_error' => true,
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function callSessionEndpoint(string $path, ?string $sessionId = null): array
    {
        $targetSession = $sessionId ?: $this->resolveSessionId();

        try {
            $response = Http::timeout(15)
                ->withHeaders($this->buildHeaders())
                ->post($this->url.$path, [
                    'session' => $targetSession,
                ]);

            if ($response->successful()) {
                $body = $response->json();

                return [
                    'status' => true,
                    'message' => (string) ($body['message'] ?? 'Berhasil.'),
                    'data' => $body['data'] ?? $body,
                    'http_status' => $response->status(),
                ];
            }

            return [
                'status' => false,
                'message' => 'Permintaan gagal (HTTP '.$response->status().').',
                'data' => $response->json() ?? $response->body(),
                'http_status' => $response->status(),
            ];
        } catch (\Throwable $throwable) {
            return [
                'status' => false,
                'message' => 'Tidak dapat menghubungi gateway sesi: '.$throwable->getMessage(),
                'http_status' => 0,
                'network_error' => true,
            ];
        }
    }

    /**
     * @return array<string, string>
     */
    private function buildHeaders(): array
    {
        $headers = ['Content-Type' => 'application/json'];

        if (trim($this->token) !== '') {
            $headers['Authorization'] = $this->token;
        }

        if (trim($this->key) !== '') {
            $headers['key'] = $this->key;
        }

        if (trim((string) $this->sessionId) !== '') {
            $headers['X-Session-Id'] = (string) $this->sessionId;
        }

        return $headers;
    }

    private function resolveSessionId(): string
    {
        if (trim((string) $this->sessionId) !== '') {
            return trim((string) $this->sessionId);
        }

        $defaultDevice = WaMultiSessionDevice::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('device_name')
            ->first();

        return trim((string) $defaultDevice?->session_id);
    }
}
