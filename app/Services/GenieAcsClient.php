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

    /**
     * @return array<string, mixed>
     */
    public function getDeviceInfo(string $deviceId): array
    {
        $response = $this->get('/devices/', [
            'query' => json_encode(['_id' => $deviceId]),
        ]);

        if (! $response->successful()) {
            return [];
        }

        $payload = $response->json();

        return is_array($payload) && isset($payload[0]) && is_array($payload[0])
            ? $payload[0]
            : [];
    }

    /**
     * @return array{success: bool, queued: bool, status: int, message: string}
     */
    public function rebootDevice(string $deviceId): array
    {
        return $this->createTask($deviceId, [
            'name' => 'reboot',
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findDeviceByUsername(string $username): ?array
    {
        foreach (['igd', 'device'] as $profile) {
            $path = config("genieacs.params.{$profile}.pppoe_username");

            if (! is_string($path) || $path === '') {
                continue;
            }

            $response = $this->get('/devices/', [
                'query' => json_encode([$path.'._value' => $username]),
            ]);

            if (! $response->successful()) {
                continue;
            }

            $devices = $response->json();

            if (is_array($devices) && isset($devices[0]) && is_array($devices[0])) {
                return $devices[0];
            }
        }

        return null;
    }

    public function detectParamProfile(array $device): string
    {
        return isset($device['InternetGatewayDevice']) ? 'igd' : 'device';
    }

    public function getParamValue(array $device, string $paramKey): mixed
    {
        $profile = $this->detectParamProfile($device);
        $path = config("genieacs.params.{$profile}.{$paramKey}");

        if (is_string($path) && $path !== '') {
            $value = $this->extractValue($device, $path);

            if ($value !== null) {
                return $value;
            }
        }

        $fallbackMap = [
            'manufacturer' => '_Manufacturer',
            'model' => '_ProductClass',
            'serial_number' => '_SerialNumber',
        ];

        if (isset($fallbackMap[$paramKey])) {
            return data_get($device, '_deviceId.'.$fallbackMap[$paramKey]);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $device
     * @return list<array{index: int|string, ssid: mixed, enabled: mixed}>
     */
    public function extractWifiNetworks(array $device): array
    {
        $profile = $this->detectParamProfile($device);

        if ($profile === 'igd') {
            $wlanConfigurations = data_get($device, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration', []);
            $wifiNetworks = [];

            if (! is_array($wlanConfigurations)) {
                return [];
            }

            foreach ($wlanConfigurations as $index => $configuration) {
                if (! is_numeric((string) $index) || ! is_array($configuration)) {
                    continue;
                }

                $wifiNetworks[] = [
                    'index' => $index,
                    'ssid' => data_get($configuration, 'SSID._value'),
                    'enabled' => data_get($configuration, 'Enable._value'),
                ];
            }

            return $wifiNetworks;
        }

        $ssids = data_get($device, 'Device.WiFi.SSID', []);
        $wifiNetworks = [];

        if (! is_array($ssids)) {
            return [];
        }

        foreach ($ssids as $index => $ssid) {
            if (! is_numeric((string) $index) || ! is_array($ssid)) {
                continue;
            }

            $wifiNetworks[] = [
                'index' => $index,
                'ssid' => data_get($ssid, 'SSID._value'),
                'enabled' => data_get($ssid, 'Enable._value'),
            ];
        }

        return $wifiNetworks;
    }

    /**
     * @param  array<string, mixed>  $device
     * @return list<array<string, mixed>>
     */
    public function extractWanConnections(array $device): array
    {
        $profile = $this->detectParamProfile($device);
        $connections = [];

        if ($profile === 'igd') {
            $wanDevices = data_get($device, 'InternetGatewayDevice.WANDevice', []);

            if (! is_array($wanDevices)) {
                return [];
            }

            foreach ($wanDevices as $wanDevice) {
                if (! is_array($wanDevice)) {
                    continue;
                }

                $connectionDevices = data_get($wanDevice, 'WANConnectionDevice', []);

                if (! is_array($connectionDevices)) {
                    continue;
                }

                foreach ($connectionDevices as $connectionDevice) {
                    if (! is_array($connectionDevice)) {
                        continue;
                    }

                    foreach (['WANPPPConnection', 'WANIPConnection'] as $group) {
                        $groupConnections = data_get($connectionDevice, $group, []);

                        if (! is_array($groupConnections)) {
                            continue;
                        }

                        foreach ($groupConnections as $connection) {
                            if (! is_array($connection)) {
                                continue;
                            }

                            $connections[] = [
                                'type' => $group,
                                'name' => data_get($connection, 'Name._value'),
                                'username' => data_get($connection, 'Username._value'),
                                'status' => data_get($connection, 'ConnectionStatus._value'),
                                'mac_address' => $this->normalizeMacAddress(data_get($connection, 'MACAddress._value')),
                            ];
                        }
                    }
                }
            }

            return $connections;
        }

        $pppInterfaces = data_get($device, 'Device.PPP.Interface', []);

        if (! is_array($pppInterfaces)) {
            return [];
        }

        foreach ($pppInterfaces as $interface) {
            if (! is_array($interface)) {
                continue;
            }

            $connections[] = [
                'type' => 'PPP',
                'name' => data_get($interface, 'Name._value'),
                'username' => data_get($interface, 'Username._value'),
                'status' => data_get($interface, 'ConnectionStatus._value'),
                'mac_address' => $this->normalizeMacAddress(data_get($interface, 'MACAddress._value')),
            ];
        }

        return $connections;
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

    public function createTask(string $deviceId, array $payload, bool $connectionRequest = true): array
    {
        $path = '/devices/'.rawurlencode($deviceId).'/tasks';

        if ($connectionRequest) {
            $path .= '?connection_request&timeout=3000';
        }

        $response = $this->post($path, $payload);

        if ($response->status() === 202) {
            $taskId = data_get($response->json() ?? [], '_id');

            if (is_string($taskId) && $taskId !== '') {
                $this->deleteTask($taskId);
            }

            return [
                'success' => true,
                'queued' => true,
                'status' => 202,
                'message' => 'Task GenieACS dibuat, tetapi device belum merespons.',
            ];
        }

        return [
            'success' => $response->successful(),
            'queued' => false,
            'status' => $response->status(),
            'message' => $response->successful()
                ? 'Task GenieACS berhasil dibuat.'
                : 'Task GenieACS gagal dibuat (HTTP '.$response->status().').',
        ];
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

    private function extractValue(array $payload, string $path): mixed
    {
        $segments = explode('.', $path);
        $current = $payload;

        foreach ($segments as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return null;
            }

            $current = $current[$segment];
        }

        if (is_array($current) && array_key_exists('_value', $current)) {
            return $current['_value'];
        }

        return $current;
    }

    private function normalizeMacAddress(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $normalized = preg_replace('/[^0-9A-Fa-f]/', '', $value);

        if (! is_string($normalized) || strlen($normalized) !== 12) {
            return null;
        }

        return strtolower(implode(':', str_split($normalized, 2)));
    }
}
