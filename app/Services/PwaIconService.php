<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Storage;

class PwaIconService
{
    private const CACHE_VERSION = 'selfhosted-v1';

    public function appName(?SystemSetting $settings, string $fallback, string $context): string
    {
        $name = trim((string) ($settings?->appName($fallback) ?? $fallback));
        $context = trim($context);

        if ($name === '' || $context === '') {
            return $name;
        }

        if (preg_match('/\b'.preg_quote($context, '/').'\b/i', $name) === 1) {
            return $name;
        }

        return trim($name.' '.$context);
    }

    public function appShortName(?SystemSetting $settings, string $fallback, string $context): string
    {
        return $this->appName($settings, $fallback, $context);
    }

    /**
     * @param  array<string, mixed>  $routeParameters
     */
    public function iconUrl(?SystemSetting $settings, int $size, string $routeName, array $routeParameters = []): string
    {
        if ($this->hasCustomLogo($settings)) {
            return route($routeName, array_merge($routeParameters, ['size' => $size]));
        }

        return asset("branding/favicon-{$size}.png");
    }

    public function iconPath(?SystemSetting $settings, int $size): string
    {
        if (! $this->hasCustomLogo($settings)) {
            return public_path("branding/favicon-{$size}.png");
        }

        $disk = Storage::disk('public');
        $relativePath = $this->cachedIconRelativePath($settings, $size);

        if (! $disk->exists($relativePath)) {
            $disk->makeDirectory(dirname($relativePath));
            $this->generateIcon($settings, $size, $disk->path($relativePath));
        }

        return $disk->path($relativePath);
    }

    private function hasCustomLogo(?SystemSetting $settings): bool
    {
        return $settings !== null
            && filled($settings->business_logo)
            && Storage::disk('public')->exists((string) $settings->business_logo);
    }

    private function cachedIconRelativePath(SystemSetting $settings, int $size): string
    {
        $signature = sha1(implode('|', [
            self::CACHE_VERSION,
            (string) $settings->id,
            (string) $settings->business_logo,
            (string) optional($settings->updated_at)?->timestamp,
            (string) $size,
        ]));

        return "pwa-icons/{$signature}-{$size}.png";
    }

    private function generateIcon(SystemSetting $settings, int $size, string $targetPath): void
    {
        $disk = Storage::disk('public');
        $sourceBinary = $disk->get((string) $settings->business_logo);
        $sourceImage = @imagecreatefromstring($sourceBinary);

        if ($sourceImage === false) {
            copy(public_path("branding/favicon-{$size}.png"), $targetPath);

            return;
        }

        $width = imagesx($sourceImage);
        $height = imagesy($sourceImage);
        $canvas = imagecreatetruecolor($size, $size);

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);

        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $transparent);

        $safeZone = (int) round($size * 0.62);
        $scale = min($safeZone / max($width, 1), $safeZone / max($height, 1));

        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));
        $targetX = (int) floor(($size - $targetWidth) / 2);
        $targetY = (int) floor(($size - $targetHeight) / 2);

        imagecopyresampled(
            $canvas,
            $sourceImage,
            $targetX,
            $targetY,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $width,
            $height,
        );

        imagepng($canvas, $targetPath, 6);

        imagedestroy($sourceImage);
        imagedestroy($canvas);
    }
}
