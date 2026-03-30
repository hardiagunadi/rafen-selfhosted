<?php

namespace App\Http\Controllers;

use App\Models\PppUser;
use App\Models\SystemSetting;
use Illuminate\View\View;

class IsolirPageController extends Controller
{
    public function show(?PppUser $pppUser = null): View
    {
        $settings = SystemSetting::instance();

        return view('isolir.show', [
            'settings' => $settings,
            'pppUser' => $pppUser,
            'title' => $settings->getIsolirPageTitle(),
            'body' => $settings->getIsolirPageBody(),
            'contact' => $settings->getIsolirPageContact(),
            'bgColor' => $settings->isolir_page_bg_color ?: '#1a1a2e',
            'accentColor' => $settings->isolir_page_accent_color ?: '#e94560',
        ]);
    }

    public function preview(): View
    {
        $settings = SystemSetting::instance();

        return view('isolir.show', [
            'settings' => $settings,
            'pppUser' => null,
            'title' => $settings->getIsolirPageTitle(),
            'body' => $settings->getIsolirPageBody(),
            'contact' => $settings->getIsolirPageContact(),
            'bgColor' => $settings->isolir_page_bg_color ?: '#1a1a2e',
            'accentColor' => $settings->isolir_page_accent_color ?: '#e94560',
            'isPreview' => true,
        ]);
    }
}
