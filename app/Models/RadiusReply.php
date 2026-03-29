<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiusReply extends Model
{
    protected $fillable = [
        'radius_account_id',
        'username',
        'attribute',
        'op',
        'value',
    ];

    public function radiusAccount(): BelongsTo
    {
        return $this->belongsTo(RadiusAccount::class);
    }
}
