<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BrokerageContractSigner extends Model
{
    protected $guarded = [];

    protected $hidden = ['signature_data'];

    protected function casts(): array
    {
        return [
            'signed_at' => 'immutable_datetime', 'terms_accepted_at' => 'immutable_datetime',
            'early_start_requested' => 'boolean', 'withdrawal_loss_acknowledged' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $signer) => $signer->uuid ??= (string) Str::uuid());
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(BrokerageContract::class, 'brokerage_contract_id');
    }
}
