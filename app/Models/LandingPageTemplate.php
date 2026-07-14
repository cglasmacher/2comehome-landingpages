<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingPageTemplate extends Model
{
    protected $guarded = [];

    protected $casts = [
        'schema' => 'array',
        'default_content' => 'array',
        'is_active' => 'boolean',
    ];
}
