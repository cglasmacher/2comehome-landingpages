<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BrokerageContract extends Model
{
    protected $guarded = [];

    protected $hidden = ['snapshot', 'pdf_path', 'signed_pdf_path'];

    protected function casts(): array
    {
        return [
            'sellers' => 'array', 'snapshot' => 'array',
            'offer_price' => 'decimal:2', 'commission_percent' => 'decimal:2',
            'duration_months' => 'integer', 'notice_months' => 'integer', 'renewal_months' => 'integer',
            'starts_on' => 'immutable_date', 'ends_on' => 'immutable_date',
            'finalized_at' => 'immutable_datetime', 'signing_expires_at' => 'immutable_datetime',
            'links_revoked_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $contract) => $contract->uuid ??= (string) Str::uuid());
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function signers(): HasMany
    {
        return $this->hasMany(BrokerageContractSigner::class)->orderBy('position');
    }
}
