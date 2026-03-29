<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class WgKeyService
{
    /**
     * @return array{private_key: string, public_key: string}
     */
    public function resolveServerKeypair(bool $autoGenerate = true): array
    {
        $privateKey = (string) config('wg.server_private_key', '');
        $publicKey = (string) config('wg.server_public_key', '');
        $privateKeyPath = (string) config('wg.server_private_key_path', '');
        $publicKeyPath = (string) config('wg.server_public_key_path', '');

        if ($privateKey === '' && $privateKeyPath !== '' && File::exists($privateKeyPath)) {
            $privateKey = trim((string) File::get($privateKeyPath));
        }

        if ($publicKey === '' && $publicKeyPath !== '' && File::exists($publicKeyPath)) {
            $publicKey = trim((string) File::get($publicKeyPath));
        }

        if ($privateKey !== '' && $publicKey === '') {
            $publicKey = $this->derivePublicKey($privateKey);
        }

        if ($privateKey === '' && $autoGenerate) {
            $keys = $this->generateKeypair();
            $privateKey = $keys['private_key'];
            $publicKey = $keys['public_key'];

            if ($privateKeyPath !== '') {
                File::ensureDirectoryExists(dirname($privateKeyPath));
                File::put($privateKeyPath, $privateKey);
            }

            if ($publicKeyPath !== '') {
                File::ensureDirectoryExists(dirname($publicKeyPath));
                File::put($publicKeyPath, $publicKey);
            }
        }

        return [
            'private_key' => $privateKey,
            'public_key' => $publicKey,
        ];
    }

    /**
     * @return array{private_key: string, public_key: string}
     */
    public function generateKeypair(): array
    {
        $privateKey = $this->runProcess(['wg', 'genkey']);

        if ($privateKey === '') {
            $privateKey = base64_encode(random_bytes(32));
        }

        $publicKey = $this->derivePublicKey($privateKey);

        if ($publicKey === '') {
            $publicKey = base64_encode(random_bytes(32));
        }

        return [
            'private_key' => $privateKey,
            'public_key' => $publicKey,
        ];
    }

    public function resolveHost(): string
    {
        $configuredHost = (string) config('wg.host', '');

        if ($configuredHost !== '') {
            return $configuredHost;
        }

        return (string) (parse_url((string) config('app.url'), PHP_URL_HOST) ?: '');
    }

    private function derivePublicKey(string $privateKey): string
    {
        return $this->runProcess(['wg', 'pubkey'], $privateKey."\n");
    }

    private function runProcess(array $command, ?string $input = null): string
    {
        $process = new Process($command);

        if ($input !== null) {
            $process->setInput($input);
        }

        $process->run();

        if (! $process->isSuccessful()) {
            return '';
        }

        return trim($process->getOutput());
    }
}
