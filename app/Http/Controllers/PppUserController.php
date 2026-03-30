<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePppUserRequest;
use App\Http\Requests\UpdatePppUserRequest;
use App\Models\Odp;
use App\Models\PppProfile;
use App\Models\PppUser;
use App\Models\ProfileGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PppUserController extends Controller
{
    public function index(): View
    {
        return view('super-admin.settings.ppp-users', [
            'pppUsers' => PppUser::query()
                ->with(['profile.bandwidthProfile', 'profileGroup', 'odp'])
                ->latest()
                ->get(),
            'pppProfiles' => PppProfile::query()->orderBy('name')->get(),
            'profileGroups' => ProfileGroup::query()->orderBy('name')->get(),
            'odps' => Odp::query()->orderBy('code')->get(),
        ]);
    }

    public function generateCustomerId(): JsonResponse
    {
        return response()->json([
            'customer_id' => PppUser::generateCustomerId(),
        ]);
    }

    public function store(StorePppUserRequest $request): RedirectResponse
    {
        PppUser::query()->create($this->prepareData($request->validated()));

        return redirect()
            ->route('super-admin.settings.ppp-users.index')
            ->with('success', 'Pelanggan PPP berhasil ditambahkan.');
    }

    public function update(UpdatePppUserRequest $request, PppUser $pppUser): RedirectResponse
    {
        $pppUser->update($this->prepareData($request->validated(), $pppUser));

        return redirect()
            ->route('super-admin.settings.ppp-users.index')
            ->with('success', 'Pelanggan PPP berhasil diperbarui.');
    }

    public function destroy(PppUser $pppUser): RedirectResponse
    {
        $pppUser->delete();

        return redirect()
            ->route('super-admin.settings.ppp-users.index')
            ->with('success', 'Pelanggan PPP berhasil dihapus.');
    }

    private function prepareData(array $data, ?PppUser $pppUser = null): array
    {
        $data['tagihkan_ppn'] = (bool) ($data['tagihkan_ppn'] ?? false);
        $data['prorata_otomatis'] = (bool) ($data['prorata_otomatis'] ?? false);
        $data['promo_aktif'] = (bool) ($data['promo_aktif'] ?? false);

        if (($data['tipe_ip'] ?? $pppUser?->tipe_ip ?? 'dhcp') !== 'static') {
            $data['ip_static'] = null;
        }

        if (($data['customer_id'] ?? null) === null || trim((string) $data['customer_id']) === '') {
            $data['customer_id'] = $pppUser?->customer_id ?: PppUser::generateCustomerId();
        }

        $profileId = $data['ppp_profile_id'] ?? $pppUser?->ppp_profile_id;

        if (($data['profile_group_id'] ?? null) === null && $profileId !== null) {
            $profile = PppProfile::query()->find($profileId);

            if ($profile?->profile_group_id !== null) {
                $data['profile_group_id'] = $profile->profile_group_id;
            }
        }

        $odpId = $data['odp_id'] ?? $pppUser?->odp_id;

        if ($odpId !== null) {
            $odp = Odp::query()->find($odpId);

            if ($odp) {
                $data['odp_pop'] = $odp->code;
            }
        }

        $metodeLogin = $data['metode_login'] ?? $pppUser?->metode_login ?? 'username_password';
        $username = $data['username'] ?? $pppUser?->username;

        if ($metodeLogin === 'username_equals_password' && is_string($username) && $username !== '') {
            $data['ppp_password'] = $username;
        } elseif (($data['ppp_password'] ?? null) === null && $pppUser instanceof PppUser) {
            $data['ppp_password'] = $pppUser->ppp_password;
        }

        if (($data['password_clientarea'] ?? null) === null || $data['password_clientarea'] === '') {
            $data['password_clientarea'] = $data['ppp_password'] ?? $pppUser?->password_clientarea;
        }

        return $data;
    }
}
