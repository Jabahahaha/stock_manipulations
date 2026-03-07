<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CopyTradingSetting extends Model
{
    protected $fillable = [
        'user_id',
        'trader_id',
        'amount_per_trade',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount_per_trade' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trader_id');
    }
}
