<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Services\PwaIconService;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManifestController extends Controller
{
    public function admin(PwaIconService $pwaIconService): Response
    {
        $settings = SystemSetting::instance();
        $icons = [
            ['src' => $pwaIconService->iconUrl($settings, 192, 'manifest.admin.icon'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ['src' => $pwaIconService->iconUrl($settings, 512, 'manifest.admin.icon'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
        ];

        return $this->manifestResponse([
            'id' => '/',
            'name' => $pwaIconService->appName($settings, 'Rafen Self-Hosted', 'Admin'),
            'short_name' => $pwaIconService->appShortName($settings, 'Rafen', 'Admin'),
            'description' => 'Panel admin self-hosted untuk operasional ISP.',
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'portrait-primary',
            'background_color' => '#f4f7fb',
            'theme_color' => '#1367a4',
            'lang' => 'id',
            'icons' => $icons,
        ]);
    }

    public function portal(PwaIconService $pwaIconService): Response
    {
        $settings = SystemSetting::instance();
        $icons = [
            ['src' => $pwaIconService->iconUrl($settings, 192, 'portal.icon'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ['src' => $pwaIconService->iconUrl($settings, 512, 'portal.icon'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
        ];

        return $this->manifestResponse([
            'id' => '/portal',
            'name' => $settings->portalName(),
            'short_name' => $settings->portalName(),
            'description' => $settings->portalDescription(),
            'start_url' => '/portal',
            'scope' => '/portal/',
            'display' => 'standalone',
            'orientation' => 'portrait-primary',
            'background_color' => '#0a3e68',
            'theme_color' => '#0f6b95',
            'lang' => 'id',
            'icons' => $icons,
        ]);
    }

    public function icon(int $size, PwaIconService $pwaIconService): BinaryFileResponse
    {
        abort_unless(in_array($size, [32, 180, 192, 512], true), 404);

        return response()->file($pwaIconService->iconPath(SystemSetting::instance(), $size), [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function portalIcon(int $size, PwaIconService $pwaIconService): BinaryFileResponse
    {
        return $this->icon($size, $pwaIconService);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function manifestResponse(array $payload): Response
    {
        $data = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return response($data, 200, [
            'Content-Type' => 'application/manifest+json',
            'Cache-Control' => 'no-store',
        ])->withoutCookie('XSRF-TOKEN')->withoutCookie('laravel-session');
    }
}
