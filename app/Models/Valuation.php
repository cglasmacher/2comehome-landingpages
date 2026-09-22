<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Valuation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'estimated_value' => 'decimal:2',
        'range_percent' => 'decimal:2',
        'range_low' => 'decimal:2',
        'range_high' => 'decimal:2',
        'provider_payload' => 'array',
        'provider_response' => 'array',
    ];

    public function getSourceLabelAttribute(): string
    {
        if (data_get($this->provider_response, 'confidence') === 'demo'
            || data_get($this->provider_response, 'provider') === 'fake_pricehubble_until_credentials_arrive') {
            return 'Formelbasierte Orientierung (Altbestand)';
        }

        return match ($this->provider) {
            'somantic' => 'Somantic',
            'pricehubble' => 'PriceHubble',
            'formula' => 'Formelbasierte Orientierung',
            'manual' => 'Manuell bearbeitet',
            default => 'Keine automatische Bewertung',
        };
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
