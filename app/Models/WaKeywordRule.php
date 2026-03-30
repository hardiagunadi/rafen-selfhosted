<?php

namespace App\Models;

use Database\Factories\WaKeywordRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaKeywordRule extends Model
{
    /** @use HasFactory<WaKeywordRuleFactory> */
    use HasFactory;

    protected $fillable = [
        'keywords',
        'reply_text',
        'priority',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'priority' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
