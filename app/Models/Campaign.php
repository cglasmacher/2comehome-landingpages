<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    protected $guarded = [];

    protected $casts = [
        'tracking_settings' => 'array',
        'is_active' => 'boolean',
    ];

    public function landingPages(): HasMany
    {
        return $this->hasMany(LandingPage::class);
    }
}
