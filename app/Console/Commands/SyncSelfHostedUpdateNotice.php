<?php

namespace App\Console\Commands;

use App\Services\SelfHostedUpdateNoticeSyncService;
use Illuminate\Console\Command;
use RuntimeException;

class SyncSelfHostedUpdateNotice extends Command
{
    protected $signature = 'self-hosted:sync-update-notice
        {path? : Path file _self_hosted_update_notice.json}
        {--deactivate-missing : Nonaktifkan notifikasi jika file tidak ditemukan}';

    protected $description = 'Sinkronkan metadata update self-hosted dari file ke database system settings.';

    public function handle(SelfHostedUpdateNoticeSyncService $syncService): int
    {
        try {
            $result = $syncService->sync(
                $this->argument('path') !== null ? (string) $this->argument('path') : null,
                (bool) $this->option('deactivate-missing'),
            );
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($result['status'] === 'deactivated') {
            $this->warn('Notifikasi update manual dinonaktifkan karena file metadata tidak ditemukan.');
            $this->line('Path: '.$result['path']);

            return self::SUCCESS;
        }

        if ($result['status'] === 'up_to_date') {
            $this->info('Metadata update dibaca, tetapi versi saat ini sudah sama dengan versi terpasang.');
        } else {
            $this->info('Notifikasi update manual berhasil disinkronkan ke database.');
        }

        $this->line('Path             : '.$result['path']);
        $this->line('Installed Version: '.$result['installed_version']);
        $this->line('Available Version: '.$result['available_version']);

        return self::SUCCESS;
    }
}
