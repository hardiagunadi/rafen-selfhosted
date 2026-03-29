<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;

class GenieAcsServiceManager
{
    /**
     * @return array<string, array{label: string, status_command: string, restart_command: string}>
     */
    public function services(): array
    {
        /** @var array<string, array{label?: string, status_command?: string, restart_command?: string}> $services */
        $services = (array) config('genieacs.services', []);

        return collect($services)
            ->map(fn (array $service): array => [
                'label' => (string) ($service['label'] ?? 'GenieACS'),
                'status_command' => trim((string) ($service['status_command'] ?? '')),
                'restart_command' => trim((string) ($service['restart_command'] ?? '')),
            ])
            ->all();
    }

    /**
     * @return array<string, array{label: string, success: bool, output: string}>
     */
    public function overview(): array
    {
        $statuses = [];

        foreach ($this->services() as $serviceKey => $service) {
            $statuses[$serviceKey] = [
                'label' => $service['label'],
                'success' => false,
                'output' => '',
            ];

            if ($service['status_command'] === '') {
                $statuses[$serviceKey]['success'] = true;
                $statuses[$serviceKey]['output'] = 'Command status belum dikonfigurasi.';

                continue;
            }

            $process = Process::run($service['status_command']);

            $statuses[$serviceKey]['success'] = $process->successful();
            $statuses[$serviceKey]['output'] = trim($process->output() ?: $process->errorOutput());
        }

        return $statuses;
    }

    /**
     * @return array{success: bool, message: string, output: string}
     */
    public function control(string $action): array
    {
        if ($action === 'status') {
            return [
                'success' => true,
                'message' => 'Status service GenieACS berhasil diambil.',
                'output' => json_encode($this->overview(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}',
            ];
        }

        if ($action === 'restart-all') {
            $outputs = [];
            $hasFailure = false;

            foreach ($this->services() as $service) {
                $result = $this->runCommand(
                    $service['restart_command'],
                    'Restart '.$service['label'].' berhasil.',
                    'Restart '.$service['label'].' gagal.',
                );

                $outputs[] = $service['label'].': '.$result['output'];
                $hasFailure = $hasFailure || ! $result['success'];
            }

            return [
                'success' => ! $hasFailure,
                'message' => ! $hasFailure
                    ? 'Restart seluruh service GenieACS berhasil.'
                    : 'Restart sebagian service GenieACS gagal.',
                'output' => trim(implode(PHP_EOL, array_filter($outputs))),
            ];
        }

        $serviceKey = str_replace('restart-', '', $action);
        $service = $this->services()[$serviceKey] ?? null;

        if (! $service) {
            return [
                'success' => false,
                'message' => 'Aksi service GenieACS tidak valid.',
                'output' => '',
            ];
        }

        return $this->runCommand(
            $service['restart_command'],
            'Restart '.$service['label'].' berhasil.',
            'Restart '.$service['label'].' gagal.',
        );
    }

    /**
     * @return array{success: bool, message: string, output: string}
     */
    private function runCommand(string $command, string $successMessage, string $failureMessage): array
    {
        if ($command === '') {
            return [
                'success' => true,
                'message' => 'Command service GenieACS belum dikonfigurasi.',
                'output' => '',
            ];
        }

        $process = Process::run($command);

        return [
            'success' => $process->successful(),
            'message' => $process->successful() ? $successMessage : $failureMessage,
            'output' => trim($process->output() ?: $process->errorOutput()),
        ];
    }
}
