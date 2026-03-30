<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProfileGroupRequest;
use App\Http\Requests\UpdateProfileGroupRequest;
use App\Models\MikrotikConnection;
use App\Models\ProfileGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfileGroupController extends Controller
{
    public function index(): View
    {
        return view('super-admin.settings.profile-groups', [
            'profileGroups' => ProfileGroup::query()
                ->with('mikrotikConnection')
                ->latest()
                ->get(),
            'mikrotikConnections' => MikrotikConnection::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreProfileGroupRequest $request): RedirectResponse
    {
        ProfileGroup::query()->create($this->prepareData($request->validated()));

        return redirect()
            ->route('super-admin.settings.profile-groups.index')
            ->with('success', 'Profile group berhasil ditambahkan.');
    }

    public function update(UpdateProfileGroupRequest $request, ProfileGroup $profileGroup): RedirectResponse
    {
        $profileGroup->update($this->prepareData($request->validated()));

        return redirect()
            ->route('super-admin.settings.profile-groups.index')
            ->with('success', 'Profile group berhasil diperbarui.');
    }

    public function destroy(ProfileGroup $profileGroup): RedirectResponse
    {
        $profileGroup->delete();

        return redirect()
            ->route('super-admin.settings.profile-groups.index')
            ->with('success', 'Profile group berhasil dihapus.');
    }

    private function prepareData(array $data): array
    {
        if (($data['ip_pool_mode'] ?? 'group_only') !== 'sql') {
            $data['ip_address'] = null;
            $data['netmask'] = null;
            $data['range_start'] = null;
            $data['range_end'] = null;
            $data['dns_servers'] = null;
            $data['host_min'] = null;
            $data['host_max'] = null;
        } else {
            $data['ip_pool_name'] = null;
        }

        return $data;
    }
}
