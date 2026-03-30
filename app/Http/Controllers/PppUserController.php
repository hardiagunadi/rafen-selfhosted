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
use Illuminate\Http\Request;
use Illuminate\View\View;

class PppUserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $perPage = max(10, (int) $request->input('per_page', 10));

        $query = PppUser::query()
            ->with(['profile.bandwidthProfile', 'profileGroup', 'odp'])
            ->latest();

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_id', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $pppUsers = $query->paginate($perPage)->withQueryString();

        return view('ppp_users.index', [
            'pppUsers' => $pppUsers,
            'stats' => $this->stats(),
            'search' => $search,
            'perPage' => $perPage,
        ]);
    }

    public function create(): View
    {
        return view('ppp_users.create', $this->formData());
    }

    public function show(PppUser $pppUser): RedirectResponse
    {
        return redirect()->route('super-admin.settings.ppp-users.edit', $pppUser);
    }

    public function edit(PppUser $pppUser): View
    {
        return view('ppp_users.edit', $this->formData([
            'pppUser' => $pppUser,
        ]));
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
            ->route('super-admin.settings.ppp-users.edit', $pppUser)
            ->with('success', 'Pelanggan PPP berhasil diperbarui.');
    }

    public function destroy(PppUser $pppUser): RedirectResponse
    {
        $pppUser->delete();

        return redirect()
            ->route('super-admin.settings.ppp-users.index')
            ->with('success', 'Pelanggan PPP berhasil dihapus.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $ids = collect($request->input('ids', []))
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values();

        if ($ids->isEmpty()) {
            return redirect()
                ->route('super-admin.settings.ppp-users.index')
                ->with('error', 'Pilih pelanggan PPP terlebih dahulu.');
        }

        PppUser::query()->whereIn('id', $ids)->delete();

        return redirect()
            ->route('super-admin.settings.ppp-users.index')
            ->with('success', $ids->count().' pelanggan PPP berhasil dihapus.');
    }

    public function toggleStatus(PppUser $pppUser): JsonResponse
    {
        $nextStatus = $pppUser->status_akun === 'enable' ? 'disable' : 'enable';
        $pppUser->update(['status_akun' => $nextStatus]);

        return response()->json([
            'status' => $nextStatus,
        ]);
    }

    private function stats(): array
    {
        $now = now();

        return [
            'registrasi_bulan_ini' => PppUser::query()
                ->whereMonth('created_at', $now->month)
                ->whereYear('created_at', $now->year)
                ->count(),
            'renewal_bulan_ini' => PppUser::query()
                ->whereMonth('updated_at', $now->month)
                ->whereYear('updated_at', $now->year)
                ->count(),
            'pelanggan_isolir' => PppUser::query()->where('status_akun', 'isolir')->count(),
            'akun_disable' => PppUser::query()->where('status_akun', 'disable')->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function formData(array $overrides = []): array
    {
        return array_merge([
            'pppProfiles' => PppProfile::query()->orderBy('name')->get(),
            'profileGroups' => ProfileGroup::query()->orderBy('name')->get(),
            'odps' => Odp::query()->orderBy('code')->get(),
        ], $overrides);
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
