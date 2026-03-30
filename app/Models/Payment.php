<?php

namespace App\Models;

use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'payment_number',
        'payment_type',
        'invoice_id',
        'payment_channel',
        'payment_method',
        'amount',
        'fee',
        'total_amount',
        'status',
        'reference',
        'merchant_ref',
        'expired_at',
        'paid_at',
        'callback_data',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'expired_at' => 'datetime',
            'paid_at' => 'datetime',
            'callback_data' => 'array',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public static function generatePaymentNumber(): string
    {
        $date = now()->format('Ymd');
        $random = strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 6));

        return "PAY-{$date}-{$random}";
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp '.number_format((float) $this->amount, 0, ',', '.');
    }
}
