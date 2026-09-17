<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadMailDelivery extends Model
{
    protected $guarded = [];
    protected $casts = ['next_attempt_at' => 'datetime', 'sent_at' => 'datetime'];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
