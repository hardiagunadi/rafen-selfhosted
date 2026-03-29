<?php

namespace App\Services;

use App\Models\OltConnection;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class HsgqSnmpCollector
{
    private const OID_SYS_DESCR = '1.3.6.1.2.1.1.1.0';

    private const OID_SYS_OBJECT_ID = '1.3.6.1.2.1.1.2.0';

    /**
     * @return array<int, string>
     */
    public static function availableModels(): array
    {
        return array_keys((array) config('olt.hsgq_models', []));
    }

    /**
     * @param  array<string, mixed>  $connectionConfig
     * @return array{matched_model: ?string, sys_descr: ?string, sys_object_id: ?string, candidate_models: array<int, string>}
     */
    public function detectModelFromSnmp(array $connectionConfig): array
    {
        $temporaryConnection = $this->buildConnectionFromConfig($connectionConfig);
        $sysDescr = $this->readScalarValue($temporaryConnection, self::OID_SYS_DESCR);
        $sysObjectId = $this->readScalarValue($temporaryConnection, self::OID_SYS_OBJECT_ID);

        if ($sysDescr === null && $sysObjectId === null) {
            throw new RuntimeException('Gagal membaca identitas perangkat lewat SNMP. Pastikan host, port, dan community benar.');
        }

        return [
            'matched_model' => $this->matchModelFromDeviceMetadata($sysDescr, $sysObjectId),
            'sys_descr' => $sysDescr,
            'sys_object_id' => $sysObjectId,
            'candidate_models' => self::availableModels(),
        ];
    }

    /**
     * @param  array<string, mixed>  $connectionConfig
     * @return array{
     *   model: string,
     *   oids: array<string, string>,
     *   probe: array<string, array{oid: string, sample_count: int, detected: bool}>,
     *   detected_fields: int
     * }
     */
    public function detectMappingFromModel(array $connectionConfig): array
    {
        $requestedModel = trim((string) ($connectionConfig['olt_model'] ?? ''));
        $profile = data_get(config('olt.hsgq_models'), $requestedModel.'.oids');

        if (! is_array($profile) || $profile === []) {
            throw new RuntimeException('Profil OID untuk model OLT ini belum tersedia.');
        }

        $temporaryConnection = $this->buildConnectionFromConfig($connectionConfig);
        $probe = [];
        $detectedFields = 0;

        foreach ($profile as $field => $oid) {
            $oidValue = trim((string) $oid);

            if ($oidValue === '') {
                continue;
            }

            $sampleCount = count($this->walkByIndex($temporaryConnection, $oidValue));
            $detected = $sampleCount > 0;

            if ($detected) {
                $detectedFields++;
            }

            $probe[(string) $field] = [
                'oid' => $oidValue,
                'sample_count' => $sampleCount,
                'detected' => $detected,
            ];
        }

        if ($detectedFields === 0) {
            throw new RuntimeException('SNMP terhubung, tetapi tidak ada OID model yang berhasil dipetakan. Pastikan model OLT benar.');
        }

        return [
            'model' => $requestedModel,
            'oids' => $profile,
            'probe' => $probe,
            'detected_fields' => $detectedFields,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function collectEssential(OltConnection $oltConnection): array
    {
        $oidMap = array_filter([
            'serial_number' => $oltConnection->oid_serial,
            'onu_name' => $oltConnection->oid_onu_name,
            'distance_raw' => $oltConnection->oid_distance,
            'rx_onu_raw' => $oltConnection->oid_rx_onu,
            'status' => $oltConnection->oid_status,
        ], fn (?string $value): bool => filled($value));

        if ($oidMap === []) {
            throw new RuntimeException('OID SNMP belum dikonfigurasi. Isi minimal satu OID pada koneksi OLT.');
        }

        $walkResults = [];

        foreach ($oidMap as $field => $oid) {
            $walkResults[$field] = $this->walkByIndex($oltConnection, (string) $oid);
        }

        $allIndexes = collect($walkResults)
            ->flatMap(fn (array $valuesByIndex): array => array_keys($valuesByIndex))
            ->unique()
            ->values()
            ->all();

        $rows = [];

        foreach ($allIndexes as $onuIndex) {
            $ponAndOnu = $this->inferPonAndOnu($onuIndex);
            $distanceRaw = $walkResults['distance_raw'][$onuIndex] ?? null;
            $rxOnuRaw = $walkResults['rx_onu_raw'][$onuIndex] ?? null;
            $statusRaw = $walkResults['status'][$onuIndex] ?? null;

            $rows[] = [
                'onu_index' => $onuIndex,
                'pon_interface' => $ponAndOnu['pon_interface'],
                'onu_number' => $ponAndOnu['onu_number'],
                'serial_number' => $walkResults['serial_number'][$onuIndex] ?? null,
                'onu_name' => $walkResults['onu_name'][$onuIndex] ?? null,
                'distance_m' => $this->parseDistanceValue($distanceRaw),
                'rx_onu_dbm' => $this->parseOpticalValue($rxOnuRaw),
                'status' => $this->normalizeOnuStatus($statusRaw),
                'raw_payload' => [
                    'distance' => $distanceRaw,
                    'rx_onu' => $rxOnuRaw,
                    'status' => $statusRaw,
                ],
            ];
        }

        return $rows;
    }

    private function buildSnmpGetCommand(OltConnection $oltConnection, string $oid): string
    {
        return sprintf(
            'snmpget -On -v2c -c %s -t %d -r %d %s %s',
            escapeshellarg((string) $oltConnection->snmp_community),
            (int) $oltConnection->snmp_timeout,
            (int) $oltConnection->snmp_retries,
            escapeshellarg((string) $oltConnection->host.':'.(int) $oltConnection->snmp_port),
            escapeshellarg('.'.ltrim(trim($oid), '.')),
        );
    }

    private function buildSnmpWalkCommand(OltConnection $oltConnection, string $oid): string
    {
        return sprintf(
            'snmpwalk -On -v2c -c %s -t %d -r %d %s %s',
            escapeshellarg((string) $oltConnection->snmp_community),
            (int) $oltConnection->snmp_timeout,
            (int) $oltConnection->snmp_retries,
            escapeshellarg((string) $oltConnection->host.':'.(int) $oltConnection->snmp_port),
            escapeshellarg('.'.ltrim(trim($oid), '.')),
        );
    }

    private function readScalarValue(OltConnection $oltConnection, string $oid): ?string
    {
        $result = Process::run($this->buildSnmpGetCommand($oltConnection, $oid));

        if ($result->failed()) {
            return null;
        }

        return $this->extractSnmpLineValue(trim($result->output()));
    }

    /**
     * @return array<string, string>
     */
    private function walkByIndex(OltConnection $oltConnection, string $oid): array
    {
        $result = Process::run($this->buildSnmpWalkCommand($oltConnection, $oid));

        if ($result->failed()) {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($result->output())) ?: [];
        $valuesByIndex = [];

        foreach ($lines as $line) {
            if (preg_match('/^\.?(?<oid>[0-9\.]+)\s*=\s*(?<type>[^:]+):\s*(?<value>.*)$/', trim($line), $matches) !== 1) {
                continue;
            }

            $normalizedOid = ltrim(trim((string) $matches['oid']), '.');
            $baseOid = ltrim(trim($oid), '.');

            if (! str_starts_with($normalizedOid, $baseOid.'.')) {
                continue;
            }

            $index = substr($normalizedOid, strlen($baseOid) + 1);

            if ($index === false || $index === '') {
                continue;
            }

            $value = $this->normalizeSnmpValue((string) $matches['value']);

            if ($value === null) {
                continue;
            }

            $valuesByIndex[$index] = $value;
        }

        return $valuesByIndex;
    }

    private function normalizeSnmpValue(string $value): ?string
    {
        $trimmed = trim($value, "\" \t\n\r\0\x0B");

        if ($trimmed === '') {
            return null;
        }

        if (str_starts_with($trimmed, 'Hex-STRING:')) {
            return trim((string) preg_replace('/^Hex-STRING:\s*/', '', $trimmed));
        }

        return $trimmed;
    }

    private function extractSnmpLineValue(string $line): ?string
    {
        if ($line === '') {
            return null;
        }

        if (preg_match('/^\.?(?<oid>[0-9\.]+)\s*=\s*(?<type>[^:]+):\s*(?<value>.*)$/', $line, $matches) !== 1) {
            return null;
        }

        return $this->normalizeSnmpValue((string) $matches['value']);
    }

    private function matchModelFromDeviceMetadata(?string $sysDescr, ?string $sysObjectId): ?string
    {
        $haystack = strtolower(trim(($sysDescr ?? '').' '.($sysObjectId ?? '')));

        if ($haystack === '') {
            return null;
        }

        if (str_contains($haystack, 'e04i') || str_contains($haystack, '50224')) {
            return 'HSGQ-E04I (EPON)';
        }

        if (str_contains($haystack, '16 pon')) {
            return 'HSGQ GPON 16 PON';
        }

        if (str_contains($haystack, '8 pon')) {
            return 'HSGQ GPON 8 PON';
        }

        if (str_contains($haystack, '4 pon')) {
            return 'HSGQ GPON 4 PON';
        }

        if (str_contains($haystack, 'epon')) {
            return 'HSGQ EPON';
        }

        if (str_contains($haystack, 'gpon')) {
            return 'HSGQ GPON';
        }

        return null;
    }

    /**
     * @return array{pon_interface: ?string, onu_number: ?string}
     */
    private function inferPonAndOnu(string $onuIndex): array
    {
        if (ctype_digit($onuIndex)) {
            $relativeIndex = (int) $onuIndex - 16777216;

            if ($relativeIndex > 0) {
                $pon = intdiv($relativeIndex, 256);
                $onu = $relativeIndex % 256;

                if ($pon >= 0 && $onu >= 1) {
                    return [
                        'pon_interface' => 'PON'.$pon,
                        'onu_number' => (string) $onu,
                    ];
                }
            }
        }

        $segments = array_values(array_filter(explode('.', $onuIndex), fn (string $segment): bool => $segment !== ''));

        if (count($segments) >= 2) {
            $ponRaw = $segments[count($segments) - 2];
            $onuRaw = $segments[count($segments) - 1];

            return [
                'pon_interface' => ctype_digit($ponRaw) ? 'PON'.$ponRaw : $ponRaw,
                'onu_number' => $onuRaw,
            ];
        }

        return [
            'pon_interface' => null,
            'onu_number' => $segments[0] ?? null,
        ];
    }

    private function parseDistanceValue(?string $value): ?int
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $normalized = preg_replace('/[^0-9\-]/', '', $value);

        return $normalized === null || $normalized === '' ? null : (int) $normalized;
    }

    private function parseOpticalValue(?string $value): ?float
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $normalized = preg_replace('/[^0-9\.\-]/', '', $value);

        if ($normalized === null || $normalized === '' || ! is_numeric($normalized)) {
            return null;
        }

        $numericValue = (float) $normalized;

        if (abs($numericValue) >= 100) {
            return round($numericValue / 100, 2);
        }

        return round($numericValue, 2);
    }

    private function normalizeOnuStatus(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtolower(trim($value));

        return match ($normalized) {
            '1', 'online', 'up', 'true' => 'online',
            '2', 'offline', 'down', 'false' => 'offline',
            default => $normalized !== '' ? $normalized : null,
        };
    }

    /**
     * @param  array<string, mixed>  $connectionConfig
     */
    private function buildConnectionFromConfig(array $connectionConfig): OltConnection
    {
        $oltConnection = new OltConnection;
        $oltConnection->fill([
            'vendor' => (string) ($connectionConfig['vendor'] ?? 'hsgq'),
            'name' => (string) ($connectionConfig['name'] ?? 'Detected OLT'),
            'olt_model' => $connectionConfig['olt_model'] ?? null,
            'host' => (string) ($connectionConfig['host'] ?? ''),
            'snmp_port' => (int) ($connectionConfig['snmp_port'] ?? 161),
            'snmp_version' => (string) ($connectionConfig['snmp_version'] ?? '2c'),
            'snmp_community' => (string) ($connectionConfig['snmp_community'] ?? ''),
            'snmp_write_community' => $connectionConfig['snmp_write_community'] ?? null,
            'snmp_timeout' => (int) ($connectionConfig['snmp_timeout'] ?? 5),
            'snmp_retries' => (int) ($connectionConfig['snmp_retries'] ?? 1),
            'is_active' => (bool) ($connectionConfig['is_active'] ?? true),
        ]);

        return $oltConnection;
    }
}
