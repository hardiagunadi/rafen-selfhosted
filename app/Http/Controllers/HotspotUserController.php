<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHotspotUserRequest;
use App\Http\Requests\UpdateHotspotUserRequest;
use App\Models\HotspotProfile;
use App\Models\HotspotUser;
use App\Models\ProfileGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HotspotUserController extends Controller
{
    public function index(): View
    {
        return view('super-admin.settings.hotspot-users', [
            'hotspotUsers' => HotspotUser::query()
                ->with(['hotspotProfile.bandwidthProfile', 'profileGroup'])
                ->latest()
                ->get(),
            'hotspotProfiles' => HotspotProfile::query()->orderBy('name')->get(),
            'profileGroups' => ProfileGroup::query()->orderBy('name')->get(),
        ]);
    }

    public function generateCustomerId(): JsonResponse
    {
        return response()->json([
            'customer_id' => HotspotUser::generateCustomerId(),
        ]);
    }

    public function store(StoreHotspotUserRequest $request): RedirectResponse
    {
        HotspotUser::query()->create($this->prepareData($request->validated()));

        return redirect()
            ->route('super-admin.settings.hotspot-users.index')
            ->with('success', 'Pelanggan Hotspot berhasil ditambahkan.');
    }

    public function update(UpdateHotspotUserRequest $request, HotspotUser $hotspotUser): RedirectResponse
    {
        $hotspotUser->update($this->prepareData($request->validated(), $hotspotUser));

        return redirect()
            ->route('super-admin.settings.hotspot-users.index')
            ->with('success', 'Pelanggan Hotspot berhasil diperbarui.');
    }

    public function destroy(HotspotUser $hotspotUser): RedirectResponse
    {
        $hotspotUser->delete();

        return redirect()
            ->route('super-admin.settings.hotspot-users.index')
            ->with('success', 'Pelanggan Hotspot berhasil dihapus.');
    }

    private function prepareData(array $data, ?HotspotUser $hotspotUser = null): array
    {
        $data['tagihkan_ppn'] = (bool) ($data['tagihkan_ppn'] ?? false);

        if (($data['customer_id'] ?? null) === null || trim((string) $data['customer_id']) === '') {
            $data['customer_id'] = $hotspotUser?->customer_id ?: HotspotUser::generateCustomerId();
        }

        $profileId = $data['hotspot_profile_id'] ?? $hotspotUser?->hotspot_profile_id;

        if (($data['profile_group_id'] ?? null) === null && $profileId !== null) {
            $profile = HotspotProfile::query()->find($profileId);

            if ($profile?->profile_group_id !== null) {
                $data['profile_group_id'] = $profile->profile_group_id;
            }
        }

        $metodeLogin = $data['metode_login'] ?? $hotspotUser?->metode_login ?? 'username_password';
        $username = $data['username'] ?? $hotspotUser?->username;

        if ($metodeLogin === 'username_equals_password' && is_string($username) && $username !== '') {
            $data['hotspot_password'] = $username;
        } elseif (($data['hotspot_password'] ?? null) === null && $hotspotUser instanceof HotspotUser) {
            $data['hotspot_password'] = $hotspotUser->hotspot_password;
        }

        return $data;
    }
}
