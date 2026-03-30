<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBandwidthProfileRequest;
use App\Http\Requests\UpdateBandwidthProfileRequest;
use App\Models\BandwidthProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BandwidthProfileController extends Controller
{
    public function index(): View
    {
        return view('super-admin.settings.bandwidth-profiles', [
            'bandwidthProfiles' => BandwidthProfile::query()->latest()->get(),
        ]);
    }

    public function store(StoreBandwidthProfileRequest $request): RedirectResponse
    {
        BandwidthProfile::query()->create($request->validated());

        return redirect()
            ->route('super-admin.settings.bandwidth-profiles.index')
            ->with('success', 'Profil bandwidth berhasil ditambahkan.');
    }

    public function update(UpdateBandwidthProfileRequest $request, BandwidthProfile $bandwidthProfile): RedirectResponse
    {
        $bandwidthProfile->update($request->validated());

        return redirect()
            ->route('super-admin.settings.bandwidth-profiles.index')
            ->with('success', 'Profil bandwidth berhasil diperbarui.');
    }

    public function destroy(BandwidthProfile $bandwidthProfile): RedirectResponse
    {
        $bandwidthProfile->delete();

        return redirect()
            ->route('super-admin.settings.bandwidth-profiles.index')
            ->with('success', 'Profil bandwidth berhasil dihapus.');
    }
}
