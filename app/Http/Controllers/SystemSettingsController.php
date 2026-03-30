<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSystemBusinessSettingsRequest;
use App\Http\Requests\UpdateSystemIsolirSettingsRequest;
use App\Http\Requests\UpdateSystemUpdateNoticeRequest;
use App\Http\Requests\UploadSystemLogoRequest;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SystemSettingsController extends Controller
{
    public function index(): View
    {
        return view('super-admin.settings.system', [
            'settings' => SystemSetting::instance(),
        ]);
    }

    public function updateBusiness(UpdateSystemBusinessSettingsRequest $request): RedirectResponse
    {
        SystemSetting::instance()->update($request->validated());

        return redirect()
            ->route('super-admin.settings.system.index')
            ->with('success', 'Pengaturan bisnis berhasil diperbarui.');
    }

    public function updateIsolir(UpdateSystemIsolirSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        foreach (['isolir_page_bg_color', 'isolir_page_accent_color'] as $colorField) {
            if (filled($validated[$colorField] ?? null) && ! str_starts_with((string) $validated[$colorField], '#')) {
                $validated[$colorField] = '#'.$validated[$colorField];
            }
        }

        SystemSetting::instance()->update($validated);

        return redirect()
            ->route('super-admin.settings.system.index')
            ->with('success', 'Halaman isolir berhasil diperbarui.');
    }

    public function uploadLogo(UploadSystemLogoRequest $request): RedirectResponse
    {
        $settings = SystemSetting::instance();

        if (filled($settings->business_logo)) {
            Storage::disk('public')->delete((string) $settings->business_logo);
        }

        $path = $request->file('business_logo')->store('branding/system', 'public');

        $settings->update([
            'business_logo' => $path,
        ]);

        return redirect()
            ->route('super-admin.settings.system.index')
            ->with('success', 'Logo bisnis berhasil diunggah.');
    }

    public function updateNotice(UpdateSystemUpdateNoticeRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['update_is_active'] = $request->boolean('update_is_active');
        $validated['update_manual_only'] = true;

        SystemSetting::instance()->update($validated);

        return redirect()
            ->route('super-admin.settings.system.index')
            ->with('success', 'Notifikasi update manual berhasil diperbarui.');
    }
}
