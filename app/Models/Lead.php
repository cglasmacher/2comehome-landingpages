<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Lead extends Model
{
    protected $guarded = [];

    protected $casts = [
        'utm' => 'array',
        'tracking' => 'array',
        'consultation_requested_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Lead $lead): void {
            $lead->uuid ??= (string) Str::uuid();
        });
    }

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class);
    }

    public function property(): HasOne
    {
        return $this->hasOne(LeadProperty::class);
    }

    public function valuation(): HasOne
    {
        return $this->hasOne(Valuation::class);
    }
}
