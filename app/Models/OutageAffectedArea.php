<?php

namespace App\Models;

use Database\Factories\OutageAffectedAreaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutageAffectedArea extends Model
{
    /** @use HasFactory<OutageAffectedAreaFactory> */
    use HasFactory;

    protected $fillable = [
        'outage_id',
        'area_type',
        'label',
    ];

    public function outage(): BelongsTo
    {
        return $this->belongsTo(Outage::class, 'outage_id');
    }

    public function getDisplayLabelAttribute(): string
    {
        return $this->label ?? 'Area Tidak Diketahui';
    }
}
