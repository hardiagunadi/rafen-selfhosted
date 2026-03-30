<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkDestroyVoucherRequest;
use App\Http\Requests\StoreVoucherBatchRequest;
use App\Models\HotspotProfile;
use App\Models\Voucher;
use App\Services\VoucherGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VoucherController extends Controller
{
    public function __construct(private readonly VoucherGeneratorService $generator) {}

    public function index(): View
    {
        $status = request()->string('status')->toString();
        $batch = request()->string('batch')->toString();

        $voucherQuery = Voucher::query()
            ->with(['hotspotProfile', 'profileGroup'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($batch !== '', fn ($query) => $query->where('batch_name', $batch));

        return view('super-admin.vouchers', [
            'vouchers' => $voucherQuery->latest()->get(),
            'hotspotProfiles' => HotspotProfile::query()->orderBy('name')->get(),
            'stats' => [
                'unused' => Voucher::query()->where('status', 'unused')->count(),
                'used' => Voucher::query()->where('status', 'used')->count(),
                'expired' => Voucher::query()->where('status', 'expired')->count(),
            ],
            'batches' => Voucher::query()
                ->select('batch_name')
                ->whereNotNull('batch_name')
                ->distinct()
                ->orderByDesc('batch_name')
                ->pluck('batch_name'),
            'selectedStatus' => $status,
            'selectedBatch' => $batch,
        ]);
    }

    public function store(StoreVoucherBatchRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $profile = HotspotProfile::query()->findOrFail($validated['hotspot_profile_id']);

        $vouchers = $this->generator->generateBatch(
            profile: $profile,
            count: (int) $validated['jumlah'],
            batchName: $validated['batch_name'],
        );

        return redirect()
            ->route('super-admin.vouchers.index')
            ->with('success', "Batch voucher '{$validated['batch_name']}' berhasil dibuat ({$vouchers->count()} kode).");
    }

    public function printBatch(string $batch): View
    {
        return view('super-admin.vouchers-print', [
            'batch' => $batch,
            'vouchers' => Voucher::query()
                ->with('hotspotProfile')
                ->where('batch_name', $batch)
                ->orderBy('code')
                ->get(),
        ]);
    }

    public function destroy(Voucher $voucher): RedirectResponse
    {
        if (! $voucher->isUnused()) {
            return redirect()
                ->route('super-admin.vouchers.index')
                ->with('error', 'Hanya voucher unused yang dapat dihapus.');
        }

        $voucher->delete();

        return redirect()
            ->route('super-admin.vouchers.index')
            ->with('success', 'Voucher berhasil dihapus.');
    }

    public function bulkDestroy(BulkDestroyVoucherRequest $request): RedirectResponse
    {
        Voucher::query()
            ->whereIn('id', $request->validated('ids'))
            ->where('status', 'unused')
            ->delete();

        return redirect()
            ->route('super-admin.vouchers.index')
            ->with('success', 'Voucher terpilih berhasil dihapus.');
    }
}
