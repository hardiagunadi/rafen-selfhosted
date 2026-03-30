<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePppProfileRequest;
use App\Http\Requests\UpdatePppProfileRequest;
use App\Models\BandwidthProfile;
use App\Models\PppProfile;
use App\Models\ProfileGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PppProfileController extends Controller
{
    public function index(): View
    {
        return view('super-admin.settings.ppp-profiles', [
            'pppProfiles' => PppProfile::query()
                ->with(['profileGroup', 'bandwidthProfile'])
                ->latest()
                ->get(),
            'profileGroups' => ProfileGroup::query()->orderBy('name')->get(),
            'bandwidthProfiles' => BandwidthProfile::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StorePppProfileRequest $request): RedirectResponse
    {
        PppProfile::query()->create($request->validated());

        return redirect()
            ->route('super-admin.settings.ppp-profiles.index')
            ->with('success', 'Paket PPP berhasil ditambahkan.');
    }

    public function update(UpdatePppProfileRequest $request, PppProfile $pppProfile): RedirectResponse
    {
        $pppProfile->update($request->validated());

        return redirect()
            ->route('super-admin.settings.ppp-profiles.index')
            ->with('success', 'Paket PPP berhasil diperbarui.');
    }

    public function destroy(PppProfile $pppProfile): RedirectResponse
    {
        $pppProfile->delete();

        return redirect()
            ->route('super-admin.settings.ppp-profiles.index')
            ->with('success', 'Paket PPP berhasil dihapus.');
    }
}
