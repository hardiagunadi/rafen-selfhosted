<?php

namespace App\Models;

use Database\Factories\TeknisiSetoranFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class TeknisiSetoran extends Model
{
    /** @use HasFactory<TeknisiSetoranFactory> */
    use HasFactory;

    protected $fillable = [
        'teknisi_id',
        'verified_by',
        'period_date',
        'total_invoices',
        'total_tagihan',
        'total_cash',
        'status',
        'submitted_at',
        'verified_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_date' => 'date',
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
            'total_tagihan' => 'decimal:2',
            'total_cash' => 'decimal:2',
        ];
    }

    public function teknisi(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teknisi_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function getInvoices(): Collection
    {
        return Invoice::query()
            ->where('paid_by', $this->teknisi_id)
            ->whereDate('paid_at', $this->period_date)
            ->with('pppUser')
            ->orderBy('paid_at')
            ->get();
    }

    public function recalculate(): void
    {
        $invoices = $this->getInvoices();

        $this->update([
            'total_invoices' => $invoices->count(),
            'total_tagihan' => $invoices->sum('total'),
            'total_cash' => $invoices->sum('cash_received'),
        ]);
    }
}
