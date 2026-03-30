<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePppUserRequest;
use App\Http\Requests\UpdatePppUserRequest;
use App\Models\Invoice;
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

    public function datatable(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $search = trim((string) $request->input('search.value', ''));

        $query = PppUser::query()
            ->with(['profile', 'odp'])
            ->addSelect([
                'latest_unpaid_invoice_id' => Invoice::query()
                    ->select('id')
                    ->whereColumn('ppp_user_id', 'ppp_users.id')
                    ->where('status', 'unpaid')
                    ->latest('due_date')
                    ->latest('id')
                    ->limit(1),
                'latest_unpaid_invoice_number' => Invoice::query()
                    ->select('invoice_number')
                    ->whereColumn('ppp_user_id', 'ppp_users.id')
                    ->where('status', 'unpaid')
                    ->latest('due_date')
                    ->latest('id')
                    ->limit(1),
                'latest_unpaid_invoice_due_date' => Invoice::query()
                    ->select('due_date')
                    ->whereColumn('ppp_user_id', 'ppp_users.id')
                    ->where('status', 'unpaid')
                    ->latest('due_date')
                    ->latest('id')
                    ->limit(1),
                'latest_unpaid_invoice_renewed' => Invoice::query()
                    ->select('renewed_without_payment')
                    ->whereColumn('ppp_user_id', 'ppp_users.id')
                    ->where('status', 'unpaid')
                    ->latest('due_date')
                    ->latest('id')
                    ->limit(1),
            ])
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

        $data = $users->map(function (PppUser $pppUser): array {
            $invoiceId = $pppUser->getAttribute('latest_unpaid_invoice_id');
            $invoiceNumber = (string) ($pppUser->getAttribute('latest_unpaid_invoice_number') ?? '');
            $invoiceDueDate = $pppUser->getAttribute('latest_unpaid_invoice_due_date');
            $invoiceRenewed = (bool) $pppUser->getAttribute('latest_unpaid_invoice_renewed');
            $invoiceDueLabel = $invoiceDueDate ? date('Y-m-d', strtotime((string) $invoiceDueDate)) : null;

            if ($invoiceId !== null) {
                $renewButton = $invoiceRenewed
                    ? '<button type="button" class="btn btn-light btn-sm" disabled title="Invoice sudah pernah di-renew tanpa pembayaran."><i class="fas fa-bolt"></i> Renew</button>'
                    : '<button type="button" class="btn btn-primary btn-sm" data-ajax-post="'.route('super-admin.invoices.renew', $invoiceId).'" data-confirm="Perpanjang layanan PPP tanpa pembayaran?" title="Perpanjang Layanan"><i class="fas fa-bolt"></i> Renew</button>';

                $billingActions = '<div class="btn-group btn-group-sm">'
                    .$renewButton
                    .'<a href="'.route('super-admin.invoices.show', $invoiceId).'" class="btn btn-outline-primary" title="Lihat invoice '.e($invoiceNumber ?: (string) $invoiceId).'"><i class="fas fa-file-invoice"></i></a>'
                    .'</div>';

                if ($invoiceDueLabel !== null) {
                    $billingActions .= '<div class="small text-muted mt-1">Jatuh tempo invoice: '.e($invoiceDueLabel).'</div>';
                }
            } else {
                $billingActions = '<div class="btn-group btn-group-sm">'
                    .'<button type="button" class="btn btn-outline-primary btn-sm" data-ajax-post="'.route('super-admin.settings.ppp-users.add-invoice', $pppUser).'" data-redirect-on-success="1" data-confirm="Buat invoice baru untuk pelanggan PPP ini?"><i class="fas fa-file-invoice mr-1"></i>Buat Invoice</button>'
                    .'</div>';
            }

            return [
                'checkbox' => '<input type="checkbox" name="ids[]" value="'.$pppUser->id.'">',
                'customer_id' => '<a href="#" class="toggle-status-btn badge badge-'.($pppUser->status_akun === 'enable' ? 'success' : 'danger').'" data-toggle-url="'.route('super-admin.settings.ppp-users.toggle-status', $pppUser).'">'.e($pppUser->customer_id ?? '-').'</a>',
                'nama' => '<a href="'.route('super-admin.settings.ppp-users.edit', $pppUser).'" class="font-weight-bold text-dark">'.e($pppUser->customer_name).'</a>',
                'username' => e($pppUser->username ?? '-'),
                'paket' => e($pppUser->profile?->name ?? '-'),
                'odp' => e($pppUser->odp?->code ?? ($pppUser->odp_pop ?: '-')),
                'jatuh_tempo' => e($pppUser->jatuh_tempo?->format('Y-m-d') ?? '-'),
                'status' => '<span class="badge badge-'.($pppUser->status_akun === 'enable' ? 'success' : ($pppUser->status_akun === 'isolir' ? 'warning' : 'secondary')).'">'.e(strtoupper((string) $pppUser->status_akun)).'</span>',
                'billing' => $billingActions,
                'aksi' => '<div class="btn-group btn-group-sm">'
                    .'<a href="'.route('super-admin.settings.ppp-users.edit', $pppUser).'" class="btn btn-warning text-white" title="Edit"><i class="fas fa-pen"></i></a>'
                    .'<button type="button" class="btn btn-danger" data-ajax-delete="'.route('super-admin.settings.ppp-users.destroy', $pppUser).'" title="Hapus"><i class="fas fa-trash"></i></button>'
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

        $data = PppUser::query()
            ->where(function ($builder) use ($keyword): void {
                $builder->where('customer_name', 'like', "%{$keyword}%")
                    ->orWhere('customer_id', 'like', "%{$keyword}%")
                    ->orWhere('username', 'like', "%{$keyword}%");
            })
            ->latest()
            ->limit(8)
            ->get(['customer_name', 'customer_id', 'username'])
            ->map(function (PppUser $pppUser): array {
                $displayName = trim((string) ($pppUser->customer_name ?: $pppUser->username ?: $pppUser->customer_id));

                return [
                    'value' => $displayName,
                    'label' => sprintf(
                        '%s | %s | %s',
                        $pppUser->customer_id ?? '-',
                        $pppUser->username ?? '-',
                        $pppUser->customer_name ?? '-',
                    ),
                ];
            })
            ->values()
            ->all();

        return response()->json(['data' => $data]);
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

    public function destroy(PppUser $pppUser): JsonResponse|RedirectResponse
    {
        $pppUser->delete();

        if (request()->wantsJson()) {
            return response()->json(['status' => 'Pelanggan PPP berhasil dihapus.']);
        }

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

    public function addInvoice(PppUser $pppUser): JsonResponse|RedirectResponse
    {
        $existingInvoice = $pppUser->invoices()
            ->where('status', 'unpaid')
            ->latest('due_date')
            ->latest('id')
            ->first();

        if ($existingInvoice instanceof Invoice) {
            $message = 'Pelanggan ini masih memiliki invoice belum lunas.';

            if (request()->wantsJson()) {
                return response()->json([
                    'status' => $message,
                    'redirect_url' => route('super-admin.invoices.show', $existingInvoice),
                ]);
            }

            return redirect()
                ->route('super-admin.invoices.show', $existingInvoice)
                ->with('success', $message);
        }

        $profile = $pppUser->profile;
        $hargaDasar = (float) ($profile?->harga_promo ?? $profile?->harga_modal ?? 0);
        $ppnPercent = ($pppUser->tagihkan_ppn && $profile !== null) ? (float) $profile->ppn : 0;
        $ppnAmount = round($hargaDasar * ($ppnPercent / 100), 2);
        $total = $hargaDasar + $ppnAmount;

        $invoice = Invoice::query()->create([
            'invoice_number' => Invoice::generateNumber(),
            'ppp_user_id' => $pppUser->id,
            'ppp_profile_id' => $profile?->id,
            'customer_id' => $pppUser->customer_id,
            'customer_name' => $pppUser->customer_name,
            'tipe_service' => $pppUser->tipe_service,
            'paket_langganan' => $profile?->name,
            'harga_dasar' => $hargaDasar,
            'harga_asli' => $hargaDasar,
            'ppn_percent' => $ppnPercent,
            'ppn_amount' => $ppnAmount,
            'total' => $total,
            'promo_applied' => false,
            'prorata_applied' => false,
            'due_date' => $pppUser->jatuh_tempo?->toDateString() ?? now()->toDateString(),
            'status' => 'unpaid',
            'renewed_without_payment' => false,
            'payment_token' => Invoice::generatePaymentToken(),
        ]);

        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'Invoice pelanggan PPP berhasil dibuat.',
                'redirect_url' => route('super-admin.invoices.show', $invoice),
            ]);
        }

        return redirect()
            ->route('super-admin.invoices.show', $invoice)
            ->with('success', 'Invoice pelanggan PPP berhasil dibuat.');
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
