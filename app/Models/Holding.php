<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holding extends Model
{
    protected $fillable = [
        'user_id',
        'symbol',
        'company_name',
        'quantity',
        'average_cost',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
