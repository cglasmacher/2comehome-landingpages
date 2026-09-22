<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadProperty extends Model
{
    protected $guarded = [];

    protected $casts = [
        'features' => 'array',
    ];

    public function getPropertyTypeLabelAttribute(): string
    {
        $type = trim((string) $this->property_type);

        return (string) config(
            'landingpages.property_types.'.$type.'.label',
            $type !== '' ? ucfirst($type) : ''
        );
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
