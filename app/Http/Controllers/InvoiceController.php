<?php

namespace App\Http\Controllers;

use App\Http\Requests\PayInvoiceRequest;
use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PppProfile;
use App\Models\PppUser;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(): View
    {
        return $this->renderIndex(
            title: 'Data Tagihan',
            description: 'Kelola tagihan pelanggan PPP beserta rekap invoice belum lunas.',
            unpaidOnly: false
        );
    }

    public function unpaidIndex(): View
    {
        return $this->renderIndex(
            title: 'Invoice Belum Lunas',
            description: 'Fokus ke invoice aktif yang masih menunggu pembayaran.',
            unpaidOnly: true
        );
    }

    public function show(Invoice $invoice): View
    {
        return view('super-admin.invoice-show', [
            'invoice' => $invoice->load(['pppUser.profile', 'payment', 'paidBy']),
        ]);
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $pppUser = PppUser::query()->with('profile')->findOrFail($request->integer('ppp_user_id'));
        $profile = $pppUser->profile;
        $hargaDasar = $request->filled('harga_dasar')
            ? (float) $request->input('harga_dasar')
            : (float) ($profile?->harga_promo ?? $profile?->harga_modal ?? 0);
        $ppnPercent = $request->filled('ppn_percent')
            ? (float) $request->input('ppn_percent')
            : (($pppUser->tagihkan_ppn && $profile !== null) ? (float) $profile->ppn : 0);
        $ppnAmount = round($hargaDasar * ($ppnPercent / 100), 2);
        $total = $hargaDasar + $ppnAmount;

        Invoice::query()->create([
            'invoice_number' => Invoice::generateNumber(),
            'ppp_user_id' => $pppUser->id,
            'ppp_profile_id' => $profile?->id,
            'customer_id' => $pppUser->customer_id,
            'customer_name' => $pppUser->customer_name,
            'tipe_service' => $pppUser->tipe_service,
            'paket_langganan' => $request->input('paket_langganan', $profile?->name),
            'harga_dasar' => $hargaDasar,
            'harga_asli' => $hargaDasar,
            'ppn_percent' => $ppnPercent,
            'ppn_amount' => $ppnAmount,
            'total' => $total,
            'promo_applied' => false,
            'prorata_applied' => false,
            'due_date' => $request->date('due_date'),
            'status' => 'unpaid',
            'renewed_without_payment' => false,
            'payment_token' => Invoice::generatePaymentToken(),
        ]);

        return redirect()
            ->route('super-admin.invoices.index')
            ->with('success', 'Invoice berhasil dibuat.');
    }

    public function pay(PayInvoiceRequest $request, Invoice $invoice): JsonResponse|RedirectResponse
    {
        if ($invoice->isPaid()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'Invoice sudah dibayar.',
                    'redirect_url' => route('super-admin.invoices.show', $invoice),
                ], 422);
            }

            return redirect()
                ->route('super-admin.invoices.show', $invoice)
                ->with('success', 'Invoice sudah dibayar.');
        }

        $validated = $request->validated();
        $paymentMethod = $validated['payment_method'];
        $cashReceived = $validated['cash_received'] ?? null;
        $transferAmount = $validated['transfer_amount'] ?? null;
        $paidAmount = $paymentMethod === 'cash'
            ? (float) ($cashReceived ?? 0)
            : (float) ($transferAmount ?? $invoice->total);

        if ($paymentMethod === 'cash' && $paidAmount < (float) $invoice->total) {
            return back()->withErrors([
                'cash_received' => 'Nominal tunai tidak boleh kurang dari total invoice.',
            ]);
        }

        $payment = DB::transaction(function () use ($invoice, $paymentMethod, $cashReceived, $transferAmount, $paidAmount, $validated): Payment {
            $channel = match ($paymentMethod) {
                'cash' => 'manual_cash',
                'transfer' => 'manual_transfer',
                default => 'manual_other',
            };

            $payment = Payment::query()->create([
                'payment_number' => Payment::generatePaymentNumber(),
                'payment_type' => 'invoice',
                'invoice_id' => $invoice->id,
                'payment_channel' => $channel,
                'payment_method' => $paymentMethod,
                'amount' => $invoice->total,
                'fee' => 0,
                'total_amount' => $paidAmount > 0 ? $paidAmount : $invoice->total,
                'status' => 'paid',
                'reference' => 'INV-'.$invoice->invoice_number,
                'paid_at' => now(),
                'notes' => $validated['payment_note'] ?? null,
            ]);

            $invoice->update([
                'status' => 'paid',
                'payment_method' => $paymentMethod,
                'payment_channel' => $channel,
                'payment_reference' => $payment->payment_number,
                'paid_at' => now(),
                'payment_id' => $payment->id,
                'paid_by' => auth()->id(),
                'cash_received' => $cashReceived,
                'transfer_amount' => $transferAmount,
                'payment_note' => $validated['payment_note'] ?? null,
            ]);

            if ($invoice->pppUser instanceof PppUser) {
                $invoice->pppUser->update([
                    'status_registrasi' => 'aktif',
                    'status_bayar' => 'sudah_bayar',
                    'status_akun' => 'enable',
                ]);
            }

            return $payment;
        });

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'Invoice dibayar.',
                'redirect_url' => route('super-admin.payments.show', $payment),
            ]);
        }

        return redirect()
            ->route('super-admin.invoices.show', $invoice)
            ->with('success', 'Invoice dibayar.');
    }

    public function renew(Invoice $invoice): JsonResponse|RedirectResponse
    {
        if ($invoice->isPaid()) {
            if (request()->wantsJson()) {
                return response()->json([
                    'status' => 'Invoice yang sudah lunas tidak perlu diperpanjang.',
                ], 422);
            }

            return redirect()
                ->route('super-admin.invoices.show', $invoice)
                ->with('error', 'Invoice yang sudah lunas tidak perlu diperpanjang.');
        }

        $pppUser = $invoice->pppUser()->with('profile')->firstOrFail();
        $profile = $pppUser->profile;
        $base = $invoice->due_date && $invoice->due_date->isFuture()
            ? $invoice->due_date->copy()
            : now();
        $newDueDate = $this->resolveRenewedDueDate($base, $profile);

        DB::transaction(function () use ($invoice, $pppUser, $newDueDate): void {
            $invoice->update([
                'due_date' => $newDueDate->toDateString(),
                'renewed_without_payment' => true,
            ]);

            $pppUser->update([
                'status_registrasi' => 'aktif',
                'status_bayar' => 'belum_bayar',
                'status_akun' => 'enable',
                'jatuh_tempo' => $newDueDate->toDateString(),
            ]);
        });

        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'Layanan berhasil diperpanjang. Status pelanggan aktif dan belum bayar.',
            ]);
        }

        return redirect()
            ->route('super-admin.invoices.show', $invoice)
            ->with('success', 'Layanan diperpanjang. Status: Aktif - Belum Bayar.');
    }

    public function destroy(Invoice $invoice): JsonResponse|RedirectResponse
    {
        $invoice->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'Invoice berhasil dihapus.',
            ]);
        }

        return redirect()
            ->route('super-admin.invoices.index')
            ->with('success', 'Invoice berhasil dihapus.');
    }

    private function renderIndex(string $title, string $description, bool $unpaidOnly): View
    {
        $invoiceQuery = Invoice::query()
            ->with(['pppUser.profile', 'payment', 'paidBy'])
            ->latest();

        if ($unpaidOnly) {
            $invoiceQuery->where('status', 'unpaid');
        }

        $invoices = $invoiceQuery->get();
        $unpaidInvoices = Invoice::query()
            ->where('status', 'unpaid')
            ->whereNotNull('due_date')
            ->orderBy('due_date')
            ->get(['id', 'due_date', 'total']);
        $monthlyDebt = $this->buildMonthlyDebtRecap($unpaidInvoices);
        $unpaidSummary = [
            'invoice_count' => $unpaidInvoices->count(),
            'month_count' => $monthlyDebt->count(),
            'total_amount' => (float) $unpaidInvoices->sum('total'),
            'oldest_month_label' => $monthlyDebt->first()['month_label'] ?? '-',
        ];
        $invoiceStats = [
            'total_invoice' => Invoice::query()->count(),
            'invoice_paid' => Invoice::query()->where('status', 'paid')->count(),
            'invoice_unpaid' => Invoice::query()->where('status', 'unpaid')->count(),
            'invoice_overdue' => Invoice::query()
                ->where('status', 'unpaid')
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', now()->toDateString())
                ->count(),
            'nominal_paid' => (float) Invoice::query()->where('status', 'paid')->sum('total'),
            'nominal_unpaid' => (float) $unpaidInvoices->sum('total'),
        ];

        return view('super-admin.invoices', [
            'pageTitle' => $title,
            'pageDescription' => $description,
            'showMonthlyDebtRecap' => ! $unpaidOnly,
            'invoices' => $invoices,
            'monthlyDebt' => $monthlyDebt,
            'unpaidSummary' => $unpaidSummary,
            'invoiceStats' => $invoiceStats,
            'pppUsers' => PppUser::query()->with('profile')->orderBy('customer_name')->get(),
        ]);
    }

    /**
     * @param  Collection<int, Invoice>  $unpaidInvoices
     * @return Collection<int, array{month_key:string,month_label:string,invoice_count:int,total_amount:float}>
     */
    private function buildMonthlyDebtRecap(Collection $unpaidInvoices): Collection
    {
        return $unpaidInvoices
            ->groupBy(fn (Invoice $invoice): string => $invoice->due_date?->format('Y-m') ?? 'unknown')
            ->reject(fn (Collection $items, string $monthKey): bool => $monthKey === 'unknown')
            ->map(function (Collection $items, string $monthKey): array {
                $date = Carbon::createFromFormat('Y-m', $monthKey)->startOfMonth();

                return [
                    'month_key' => $monthKey,
                    'month_label' => $date->translatedFormat('F Y'),
                    'invoice_count' => $items->count(),
                    'total_amount' => (float) $items->sum('total'),
                ];
            })
            ->sortBy('month_key')
            ->values();
    }

    private function resolveRenewedDueDate(Carbon $base, ?PppProfile $profile): Carbon
    {
        if ($profile === null) {
            return $base->copy()->addMonth();
        }

        return match ($profile->satuan) {
            'menit' => $base->copy()->addMinutes($profile->masa_aktif),
            'jam' => $base->copy()->addHours($profile->masa_aktif),
            'hari' => $base->copy()->addDays($profile->masa_aktif),
            'minggu' => $base->copy()->addWeeks($profile->masa_aktif),
            default => $base->copy()->addMonths($profile->masa_aktif),
        };
    }
}
