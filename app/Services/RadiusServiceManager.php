<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;

class RadiusServiceManager
{
    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        $statusCommand = trim((string) config('radius.status_command', ''));

        if ($statusCommand === '') {
            return [
                'success' => true,
                'message' => 'Status service FreeRADIUS belum dikonfigurasi.',
                'output' => '',
            ];
        }

        $process = Process::run($statusCommand);

        return [
            'success' => $process->successful(),
            'message' => $process->successful() ? 'Status service FreeRADIUS berhasil diambil.' : 'Gagal membaca status service FreeRADIUS.',
            'output' => trim($process->output() ?: $process->errorOutput()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function reload(): array
    {
        return $this->runCommand(
            trim((string) config('radius.reload_command', '')),
            'Reload FreeRADIUS berhasil.',
            'Reload FreeRADIUS gagal.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function restart(): array
    {
        return $this->runCommand(
            trim((string) config('radius.restart_command', '')),
            'Restart FreeRADIUS berhasil.',
            'Restart FreeRADIUS gagal.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function runCommand(string $command, string $successMessage, string $failureMessage): array
    {
        if ($command === '') {
            return [
                'success' => true,
                'message' => 'Command service FreeRADIUS belum dikonfigurasi.',
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
