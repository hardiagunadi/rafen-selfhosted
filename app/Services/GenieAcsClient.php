<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GenieAcsClient
{
    public function __construct(
        private readonly string $baseUrl = '',
        private readonly string $username = '',
        private readonly string $password = '',
        private readonly int $timeout = 10,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            rtrim((string) config('genieacs.nbi_url', ''), '/'),
            (string) config('genieacs.username', ''),
            (string) config('genieacs.password', ''),
            (int) config('genieacs.timeout', 10),
        );
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '';
    }

    /**
     * @return array{online: bool, nbi_url: string, message: string}
     */
    public function getStatus(): array
    {
        if (! $this->isConfigured()) {
            return [
                'online' => false,
                'nbi_url' => $this->baseUrl,
                'message' => 'URL NBI GenieACS belum dikonfigurasi.',
            ];
        }

        try {
            $response = $this->get('/devices/', ['limit' => 1]);

            return [
                'online' => $response->successful(),
                'nbi_url' => $this->baseUrl,
                'message' => $response->successful()
                    ? 'NBI GenieACS dapat dijangkau.'
                    : 'NBI GenieACS merespons dengan HTTP '.$response->status().'.',
            ];
        } catch (\Throwable $throwable) {
            return [
                'online' => false,
                'nbi_url' => $this->baseUrl,
                'message' => 'NBI GenieACS tidak dapat dihubungi: '.$throwable->getMessage(),
            ];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listDevices(int $limit = 500): array
    {
        $response = $this->get('/devices/', ['limit' => $limit]);

        return $response->successful() ? ($response->json() ?? []) : [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getTasks(int $limit = 500): array
    {
        $response = $this->get('/tasks', ['limit' => $limit]);

        return $response->successful() ? ($response->json() ?? []) : [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getFaults(int $limit = 500): array
    {
        $response = $this->get('/faults', ['limit' => $limit]);

        return $response->successful() ? ($response->json() ?? []) : [];
    }

    /**
     * @return array{total_devices: int, online_devices: int, pending_tasks: int, faults: int}
     */
    public function summary(): array
    {
        $threshold = (int) config('genieacs.online_threshold_minutes', 70);
        $devices = $this->listDevices();
        $onlineDevices = 0;

        foreach ($devices as $device) {
            $lastInform = data_get($device, '_lastInform');

            if (! is_string($lastInform) || trim($lastInform) === '') {
                continue;
            }

            try {
                if (Carbon::parse($lastInform)->diffInMinutes(now()) <= $threshold) {
                    $onlineDevices++;
                }
            } catch (\Throwable) {
            }
        }

        return [
            'total_devices' => count($devices),
            'online_devices' => $onlineDevices,
            'pending_tasks' => count($this->getTasks()),
            'faults' => count($this->getFaults()),
        ];
    }

    /**
     * @return array{success: bool, queued: bool, status: int, message: string}
     */
    public function sendConnectionRequest(string $deviceId, string $profile = 'igd'): array
    {
        $paramName = $profile === 'device'
            ? 'Device.DeviceInfo.UpTime'
            : 'InternetGatewayDevice.DeviceInfo.UpTime';

        $response = $this->post(
            '/devices/'.rawurlencode($deviceId).'/tasks?connection_request&timeout=3000',
            [
                'name' => 'getParameterValues',
                'parameterNames' => [$paramName],
            ],
        );

        if ($response->status() === 202) {
            $taskId = data_get($response->json() ?? [], '_id');

            if (is_string($taskId) && $taskId !== '') {
                $this->deleteTask($taskId);
            }

            return [
                'success' => false,
                'queued' => true,
                'status' => 202,
                'message' => 'Connection request dikirim, tetapi device belum merespons.',
            ];
        }

        if ($response->successful()) {
            return [
                'success' => true,
                'queued' => false,
                'status' => $response->status(),
                'message' => 'Connection request berhasil dikirim ke device.',
            ];
        }

        return [
            'success' => false,
            'queued' => false,
            'status' => $response->status(),
            'message' => 'Connection request gagal (HTTP '.$response->status().').',
        ];
    }

    public function deleteTask(string $taskId): bool
    {
        $response = $this->request()->delete('/tasks/'.rawurlencode($taskId));

        return $response->successful() || $response->status() === 404;
    }

    public function deleteDeviceTasks(string $deviceId): int
    {
        $response = $this->get('/tasks/', [
            'query' => json_encode(['device' => $deviceId]),
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Gagal membaca task GenieACS untuk device.');
        }

        $deletedCount = 0;

        foreach ($response->json() ?? [] as $task) {
            $taskId = data_get($task, '_id');

            if (is_string($taskId) && $taskId !== '' && $this->deleteTask($taskId)) {
                $deletedCount++;
            }
        }

        return $deletedCount;
    }

    private function request(): PendingRequest
    {
        $request = Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->timeout($this->timeout);

        if ($this->username !== '' || $this->password !== '') {
            $request = $request->withBasicAuth($this->username, $this->password);
        }

        return $request;
    }

    private function get(string $path, array $query = []): Response
    {
        return $this->request()->get($path, $query);
    }

    private function post(string $path, array $payload = []): Response
    {
        return $this->request()->post($path, $payload);
    }
}
