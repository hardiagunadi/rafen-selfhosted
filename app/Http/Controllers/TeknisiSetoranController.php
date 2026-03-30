<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeknisiSetoranRequest;
use App\Http\Requests\VerifyTeknisiSetoranRequest;
use App\Models\Invoice;
use App\Models\TeknisiSetoran;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TeknisiSetoranController extends Controller
{
    public function index(): View
    {
        $user = request()->user();
        $this->authorizeAccess($user);

        $status = request()->string('status')->toString();
        $query = TeknisiSetoran::query()
            ->with(['teknisi', 'verifiedBy'])
            ->when($user?->role === User::ROLE_TEKNISI && ! $user?->isSuperAdmin(), fn ($builder) => $builder->where('teknisi_id', $user->id))
            ->when($status !== '', fn ($builder) => $builder->where('status', $status))
            ->latest('period_date');

        return view('super-admin.teknisi-setoran.index', [
            'setorans' => $query->get(),
            'selectedStatus' => $status,
            'teknisis' => User::query()
                ->where('role', User::ROLE_TEKNISI)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function show(TeknisiSetoran $teknisiSetoran): View
    {
        $user = request()->user();
        $this->authorizeAccess($user, $teknisiSetoran);

        $teknisiSetoran->load(['teknisi', 'verifiedBy']);

        return view('super-admin.teknisi-setoran.show', [
            'teknisiSetoran' => $teknisiSetoran,
            'invoices' => $teknisiSetoran->getInvoices(),
        ]);
    }

    public function store(StoreTeknisiSetoranRequest $request): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeAccess($user);

        $validated = $request->validated();
        $teknisiId = $user?->role === User::ROLE_TEKNISI && ! $user?->isSuperAdmin()
            ? $user->id
            : (int) ($validated['teknisi_id'] ?? 0);

        $teknisi = User::query()
            ->whereKey($teknisiId)
            ->where('role', User::ROLE_TEKNISI)
            ->first();

        if (! $teknisi) {
            return redirect()
                ->route('teknisi-setoran.index')
                ->with('error', 'Pilih teknisi yang valid terlebih dahulu.');
        }

        $existing = TeknisiSetoran::query()
            ->where('teknisi_id', $teknisi->id)
            ->whereDate('period_date', $validated['period_date'])
            ->first();

        if ($existing) {
            return redirect()
                ->route('teknisi-setoran.show', $existing)
                ->with('success', 'Setoran untuk periode ini sudah ada.');
        }

        $invoiceQuery = Invoice::query()
            ->where('paid_by', $teknisi->id)
            ->whereDate('paid_at', $validated['period_date']);

        if (! $invoiceQuery->exists()) {
            return redirect()
                ->route('teknisi-setoran.index')
                ->with('error', 'Tidak ada invoice yang dibayar pada periode tersebut.');
        }

        $setoran = TeknisiSetoran::query()->create([
            'teknisi_id' => $teknisi->id,
            'period_date' => $validated['period_date'],
            'status' => 'draft',
        ]);

        $setoran->recalculate();

        return redirect()
            ->route('teknisi-setoran.show', $setoran)
            ->with('success', 'Setoran teknisi berhasil dibuat.');
    }

    public function submit(TeknisiSetoran $teknisiSetoran): RedirectResponse
    {
        $user = request()->user();
        $this->authorizeAccess($user, $teknisiSetoran);

        if ($teknisiSetoran->status !== 'draft') {
            return redirect()
                ->route('teknisi-setoran.show', $teknisiSetoran)
                ->with('error', 'Setoran sudah pernah diproses.');
        }

        $teknisiSetoran->recalculate();
        $teknisiSetoran->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return redirect()
            ->route('teknisi-setoran.show', $teknisiSetoran)
            ->with('success', 'Setoran berhasil disubmit ke keuangan.');
    }

    public function verify(VerifyTeknisiSetoranRequest $request, TeknisiSetoran $teknisiSetoran): RedirectResponse
    {
        $user = $request->user();

        if (! $user || (! $user->isSuperAdmin() && ! in_array($user->role, [User::ROLE_ADMINISTRATOR, User::ROLE_KEUANGAN], true))) {
            abort(403);
        }

        if ($teknisiSetoran->status !== 'submitted') {
            return redirect()
                ->route('teknisi-setoran.show', $teknisiSetoran)
                ->with('error', 'Setoran belum siap diverifikasi.');
        }

        $teknisiSetoran->update([
            'status' => 'verified',
            'verified_by' => $user->id,
            'verified_at' => now(),
            'notes' => $request->validated('notes'),
        ]);

        return redirect()
            ->route('teknisi-setoran.show', $teknisiSetoran)
            ->with('success', 'Setoran berhasil diverifikasi.');
    }

    private function authorizeAccess(?User $user, ?TeknisiSetoran $teknisiSetoran = null): void
    {
        if (! $user || (! $user->isSuperAdmin() && ! in_array($user->role, [User::ROLE_ADMINISTRATOR, User::ROLE_KEUANGAN, User::ROLE_TEKNISI], true))) {
            abort(403);
        }

        if ($teknisiSetoran && $user->role === User::ROLE_TEKNISI && $teknisiSetoran->teknisi_id !== $user->id) {
            abort(403);
        }
    }
}
