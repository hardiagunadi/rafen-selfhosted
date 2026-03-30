<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHotspotUserRequest;
use App\Http\Requests\UpdateHotspotUserRequest;
use App\Models\HotspotProfile;
use App\Models\HotspotUser;
use App\Models\ProfileGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HotspotUserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $perPage = max(10, (int) $request->input('per_page', 10));

        $query = HotspotUser::query()
            ->with(['hotspotProfile.bandwidthProfile', 'profileGroup'])
            ->latest();

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_id', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $hotspotUsers = $query->paginate($perPage)->withQueryString();

        return view('hotspot_users.index', [
            'hotspotUsers' => $hotspotUsers,
            'stats' => $this->stats(),
            'search' => $search,
            'perPage' => $perPage,
        ]);
    }

    public function create(): View
    {
        return view('hotspot_users.create', $this->formData());
    }

    public function show(HotspotUser $hotspotUser): RedirectResponse
    {
        return redirect()->route('super-admin.settings.hotspot-users.edit', $hotspotUser);
    }

    public function edit(HotspotUser $hotspotUser): View
    {
        $hotspotUser->load('hotspotProfile');

        return view('hotspot_users.edit', $this->formData([
            'hotspotUser' => $hotspotUser,
        ]));
    }

    public function generateCustomerId(): JsonResponse
    {
        return response()->json([
            'customer_id' => HotspotUser::generateCustomerId(),
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $search = trim((string) $request->input('search.value', ''));

        $query = HotspotUser::query()
            ->with(['hotspotProfile'])
            ->latest();

        $total = (clone $query)->count();

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_id', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $filtered = (clone $query)->count();

        $users = $query->skip($start)->take($length > 0 ? $length : 10)->get();

        $data = $users->map(function (HotspotUser $hotspotUser): array {
            $renewButton = '<button type="button" class="btn btn-success btn-sm" data-ajax-post="'.route('super-admin.settings.hotspot-users.renew', $hotspotUser).'" data-confirm="Perpanjang layanan hotspot ini?"><i class="fas fa-redo-alt mr-1"></i>Perpanjang</button>';

            return [
                'checkbox' => '<input type="checkbox" name="ids[]" value="'.$hotspotUser->id.'">',
                'customer_id' => '<a href="#" class="toggle-status-btn badge badge-'.($hotspotUser->status_akun === 'enable' ? 'success' : 'danger').'" data-toggle-url="'.route('super-admin.settings.hotspot-users.toggle-status', $hotspotUser).'">'.e($hotspotUser->customer_id ?? '-').'</a>',
                'nama' => '<a href="'.route('super-admin.settings.hotspot-users.edit', $hotspotUser).'" class="font-weight-bold text-dark">'.e($hotspotUser->customer_name).'</a>',
                'username' => e($hotspotUser->username ?? '-'),
                'profil' => e($hotspotUser->hotspotProfile?->name ?? '-'),
                'jatuh_tempo' => e($hotspotUser->jatuh_tempo?->format('Y-m-d') ?? '-'),
                'status' => '<span class="badge badge-'.($hotspotUser->status_akun === 'enable' ? 'success' : ($hotspotUser->status_akun === 'isolir' ? 'warning' : 'secondary')).'">'.e(strtoupper((string) $hotspotUser->status_akun)).'</span>',
                'perpanjang' => '<div class="btn-group btn-group-sm">'.$renewButton.'</div>',
                'aksi' => '<div class="btn-group btn-group-sm">'
                    .'<a href="'.route('super-admin.settings.hotspot-users.edit', $hotspotUser).'" class="btn btn-warning text-white" title="Edit"><i class="fas fa-pen"></i></a>'
                    .'<button type="button" class="btn btn-danger" data-ajax-delete="'.route('super-admin.settings.hotspot-users.destroy', $hotspotUser).'" title="Hapus"><i class="fas fa-trash"></i></button>'
                    .'</div>',
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ]);
    }

    public function autocomplete(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->input('q', ''));

        if (mb_strlen($keyword) < 2) {
            return response()->json(['data' => []]);
        }

        $data = HotspotUser::query()
            ->where(function ($builder) use ($keyword): void {
                $builder->where('customer_name', 'like', "%{$keyword}%")
                    ->orWhere('customer_id', 'like', "%{$keyword}%")
                    ->orWhere('username', 'like', "%{$keyword}%");
            })
            ->latest()
            ->limit(8)
            ->get(['customer_name', 'customer_id', 'username'])
            ->map(function (HotspotUser $hotspotUser): array {
                $displayName = trim((string) ($hotspotUser->customer_name ?: $hotspotUser->username ?: $hotspotUser->customer_id));

                return [
                    'value' => $displayName,
                    'label' => sprintf(
                        '%s | %s | %s',
                        $hotspotUser->customer_id ?? '-',
                        $hotspotUser->username ?? '-',
                        $hotspotUser->customer_name ?? '-',
                    ),
                ];
            })
            ->values()
            ->all();

        return response()->json(['data' => $data]);
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
            ->route('super-admin.settings.hotspot-users.edit', $hotspotUser)
            ->with('success', 'Pelanggan Hotspot berhasil diperbarui.');
    }

    public function destroy(HotspotUser $hotspotUser): JsonResponse|RedirectResponse
    {
        $hotspotUser->delete();

        if (request()->wantsJson()) {
            return response()->json(['status' => 'Pelanggan Hotspot berhasil dihapus.']);
        }

        return redirect()
            ->route('super-admin.settings.hotspot-users.index')
            ->with('success', 'Pelanggan Hotspot berhasil dihapus.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $ids = collect($request->input('ids', []))
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values();

        if ($ids->isEmpty()) {
            return redirect()
                ->route('super-admin.settings.hotspot-users.index')
                ->with('error', 'Pilih pelanggan Hotspot terlebih dahulu.');
        }

        HotspotUser::query()->whereIn('id', $ids)->delete();

        return redirect()
            ->route('super-admin.settings.hotspot-users.index')
            ->with('success', $ids->count().' pelanggan Hotspot berhasil dihapus.');
    }

    public function renew(HotspotUser $hotspotUser): JsonResponse|RedirectResponse
    {
        $baseDate = $hotspotUser->jatuh_tempo && $hotspotUser->jatuh_tempo->isFuture()
            ? $hotspotUser->jatuh_tempo->copy()
            : now();

        $newDueDate = $hotspotUser->hotspotProfile?->computeExpiredAt($baseDate->copy())
            ?? $baseDate->copy()->addMonth();

        $hotspotUser->update([
            'jatuh_tempo' => $newDueDate->toDateString(),
            'status_registrasi' => 'aktif',
            'status_bayar' => 'belum_bayar',
            'status_akun' => 'enable',
        ]);

        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'Layanan hotspot berhasil diperpanjang.',
            ]);
        }

        return redirect()
            ->route('super-admin.settings.hotspot-users.edit', $hotspotUser)
            ->with('success', 'Layanan hotspot berhasil diperpanjang.');
    }

    public function toggleStatus(HotspotUser $hotspotUser): JsonResponse
    {
        $nextStatus = $hotspotUser->status_akun === 'enable' ? 'disable' : 'enable';
        $hotspotUser->update(['status_akun' => $nextStatus]);

        return response()->json([
            'status' => $nextStatus,
        ]);
    }

    private function stats(): array
    {
        $now = now();

        return [
            'registrasi_bulan_ini' => HotspotUser::query()
                ->whereMonth('created_at', $now->month)
                ->whereYear('created_at', $now->year)
                ->count(),
            'total' => HotspotUser::query()->count(),
            'pelanggan_isolir' => HotspotUser::query()->where('status_akun', 'isolir')->count(),
            'akun_disable' => HotspotUser::query()->where('status_akun', 'disable')->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function formData(array $overrides = []): array
    {
        return array_merge([
            'hotspotProfiles' => HotspotProfile::query()->orderBy('name')->get(),
            'profileGroups' => ProfileGroup::query()->orderBy('name')->get(),
        ], $overrides);
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
