<?php

namespace App\Models;

use Database\Factories\FinanceExpenseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceExpense extends Model
{
    /** @use HasFactory<FinanceExpenseFactory> */
    use HasFactory;

    protected $fillable = [
        'created_by',
        'expense_date',
        'category',
        'service_type',
        'amount',
        'payment_method',
        'reference',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
