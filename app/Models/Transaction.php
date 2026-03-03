<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'symbol',
        'company_name',
        'type',
        'quantity',
        'price_per_share',
        'total_amount',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
