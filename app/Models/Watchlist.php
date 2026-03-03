<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Watchlist extends Model
{
    protected $fillable = [
        'user_id',
        'symbol',
        'company_name',
        'alert_price',
        'alert_condition',
        'alert_triggered',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
