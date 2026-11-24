<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValuationLetter extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'issued_on' => 'date',
            'valuation_date' => 'date',
            'value_low' => 'decimal:2',
            'value_high' => 'decimal:2',
            'orientation_value' => 'decimal:2',
            'photo_paths' => 'array',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
