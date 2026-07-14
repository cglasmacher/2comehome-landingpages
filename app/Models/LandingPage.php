<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LandingPage extends Model
{
    protected $guarded = [];

    protected $casts = [
        'content' => 'array',
        'seo' => 'array',
        'tracking_overrides' => 'array',
        'published_at' => 'datetime',
        'valuation_range_percent' => 'decimal:2',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(LandingPageTemplate::class, 'landing_page_template_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }
}
