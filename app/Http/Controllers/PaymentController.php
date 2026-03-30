<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(): View
    {
        $payments = Payment::query()
            ->with('invoice')
            ->latest()
            ->get();

        return view('super-admin.payments', [
            'payments' => $payments,
            'paymentStats' => [
                'total_payment' => $payments->count(),
                'paid_payment' => $payments->where('status', 'paid')->count(),
                'cash_payment' => $payments->where('payment_method', 'cash')->count(),
                'transfer_payment' => $payments->where('payment_method', 'transfer')->count(),
                'total_amount' => (float) $payments->sum('total_amount'),
            ],
        ]);
    }

    public function show(Payment $payment): View
    {
        return view('super-admin.payment-show', [
            'payment' => $payment->load('invoice'),
        ]);
    }
}
