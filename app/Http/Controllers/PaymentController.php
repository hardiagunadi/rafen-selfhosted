<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(): View
    {
        return view('super-admin.payments', [
            'payments' => Payment::query()
                ->with('invoice')
                ->latest()
                ->get(),
        ]);
    }

    public function show(Payment $payment): View
    {
        return view('super-admin.payment-show', [
            'payment' => $payment->load('invoice'),
        ]);
    }
}
