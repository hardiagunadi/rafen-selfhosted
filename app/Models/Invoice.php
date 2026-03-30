<?php

namespace App\Models;

use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'ppp_user_id',
        'ppp_profile_id',
        'customer_id',
        'customer_name',
        'tipe_service',
        'paket_langganan',
        'harga_dasar',
        'harga_asli',
        'ppn_percent',
        'ppn_amount',
        'total',
        'promo_applied',
        'prorata_applied',
        'due_date',
        'status',
        'renewed_without_payment',
        'payment_method',
        'payment_channel',
        'payment_reference',
        'paid_at',
        'payment_id',
        'paid_by',
        'cash_received',
        'transfer_amount',
        'payment_note',
        'payment_token',
    ];

    protected function casts(): array
    {
        return [
            'harga_dasar' => 'decimal:2',
            'harga_asli' => 'decimal:2',
            'ppn_percent' => 'decimal:2',
            'ppn_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'promo_applied' => 'boolean',
            'prorata_applied' => 'boolean',
            'renewed_without_payment' => 'boolean',
            'cash_received' => 'decimal:2',
            'transfer_amount' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    public function pppUser(): BelongsTo
    {
        return $this->belongsTo(PppUser::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(PppProfile::class, 'ppp_profile_id');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isUnpaid(): bool
    {
        return $this->status === 'unpaid';
    }

    public function isOverdue(): bool
    {
        return $this->isUnpaid() && $this->due_date !== null && $this->due_date->isPast();
    }

    public function getFormattedTotalAttribute(): string
    {
        return 'Rp '.number_format((float) $this->total, 0, ',', '.');
    }

    public static function generatePaymentToken(): string
    {
        return bin2hex(random_bytes(24));
    }

    public static function generateNumber(string $prefix = 'INV'): string
    {
        return DB::transaction(function () use ($prefix): string {
            $yearMonth = now()->format('Ym');
            $pattern = $prefix.'-'.$yearMonth.'%';

            $last = static::query()
                ->where('invoice_number', 'like', $pattern)
                ->lockForUpdate()
                ->max('invoice_number');

            $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

            return $prefix.'-'.$yearMonth.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
        });
    }
}
