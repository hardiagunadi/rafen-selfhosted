<?php

namespace App\Models;

use Database\Factories\BandwidthProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BandwidthProfile extends Model
{
    /** @use HasFactory<BandwidthProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'upload_min_mbps',
        'upload_max_mbps',
        'download_min_mbps',
        'download_max_mbps',
    ];

    protected function casts(): array
    {
        return [
            'upload_min_mbps' => 'integer',
            'upload_max_mbps' => 'integer',
            'download_min_mbps' => 'integer',
            'download_max_mbps' => 'integer',
        ];
    }
}
