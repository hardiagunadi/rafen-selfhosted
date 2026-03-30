<?php

namespace App\Models;

use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'type',
        'username',
        'plan_name',
        'amount',
        'tax_amount',
        'total',
        'status',
        'payment_method',
        'paid_at',
        'period_start',
        'period_end',
        'mixradius_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_at' => 'datetime',
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }
}
