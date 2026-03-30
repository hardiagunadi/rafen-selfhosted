<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHotspotProfileRequest;
use App\Http\Requests\UpdateHotspotProfileRequest;
use App\Models\BandwidthProfile;
use App\Models\HotspotProfile;
use App\Models\ProfileGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HotspotProfileController extends Controller
{
    public function index(): View
    {
        return view('super-admin.settings.hotspot-profiles', [
            'hotspotProfiles' => HotspotProfile::query()
                ->with(['profileGroup', 'bandwidthProfile'])
                ->latest()
                ->get(),
            'profileGroups' => ProfileGroup::query()->orderBy('name')->get(),
            'bandwidthProfiles' => BandwidthProfile::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreHotspotProfileRequest $request): RedirectResponse
    {
        HotspotProfile::query()->create($this->sanitizeData($request->validated()));

        return redirect()
            ->route('super-admin.settings.hotspot-profiles.index')
            ->with('success', 'Profil Hotspot berhasil ditambahkan.');
    }

    public function update(UpdateHotspotProfileRequest $request, HotspotProfile $hotspotProfile): RedirectResponse
    {
        $hotspotProfile->update($this->sanitizeData($request->validated()));

        return redirect()
            ->route('super-admin.settings.hotspot-profiles.index')
            ->with('success', 'Profil Hotspot berhasil diperbarui.');
    }

    public function destroy(HotspotProfile $hotspotProfile): RedirectResponse
    {
        $hotspotProfile->delete();

        return redirect()
            ->route('super-admin.settings.hotspot-profiles.index')
            ->with('success', 'Profil Hotspot berhasil dihapus.');
    }

    private function sanitizeData(array $data): array
    {
        $profileType = $data['profile_type'] ?? null;
        $limitType = $data['limit_type'] ?? null;

        if ($profileType === 'unlimited') {
            $data['limit_type'] = null;
            $data['time_limit_value'] = null;
            $data['time_limit_unit'] = null;
            $data['quota_limit_value'] = null;
            $data['quota_limit_unit'] = null;
        }

        if ($profileType === 'limited') {
            $data['masa_aktif_value'] = null;
            $data['masa_aktif_unit'] = null;

            if ($limitType === 'time') {
                $data['quota_limit_value'] = null;
                $data['quota_limit_unit'] = null;
            }

            if ($limitType === 'quota') {
                $data['time_limit_value'] = null;
                $data['time_limit_unit'] = null;
            }
        }

        return $data;
    }
}
