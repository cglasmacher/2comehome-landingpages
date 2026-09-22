<?php

namespace App\Services\Valuation;

use App\DTO\ValuationResult;
use App\Models\Lead;
use App\Services\PriceHubble\PriceHubbleClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ValuationService
{
    public function __construct(
        private readonly SomanticClient $somantic,
        private readonly PriceHubbleClient $pricehubble,
        private readonly FormulaClient $formula,
    ) {}

    public function createForLead(Lead $lead, float $rangePercent): ValuationResult
    {
        $lead->loadMissing('property');
        $property = $lead->property?->toArray() ?? [];
        $attempts = [];
        $order = config('valuation.order', []);
        $order = is_array($order) ? array_unique(array_filter($order, 'is_string')) : [];
        $rangePercent = is_finite($rangePercent) && $rangePercent > 0 && $rangePercent < 100 ? $rangePercent : 7.5;

        foreach ($order as $provider) {
            $reason = null;
            if (! in_array($provider, ['somantic', 'pricehubble', 'formula'], true)) {
                $reason = 'unknown_provider';
            } elseif (! config('valuation.providers.'.$provider.'.enabled', false)) {
                $reason = 'disabled';
            } elseif ($provider !== 'formula' && (! filled(config('valuation.providers.'.$provider.'.api_key'))
                || ! filled(config('valuation.providers.'.$provider.'.base_url')))) {
                $reason = 'not_configured';
            }
            if ($reason !== null) {
                $attempts[] = ['provider' => $provider, 'status' => 'skipped', 'reason' => $reason];
                Log::info('valuation provider skipped', ['lead_id' => $lead->id, 'provider' => $provider, 'reason' => $reason]);
                continue;
            }
            try {
                $response = match ($provider) {
                    'somantic' => $this->somantic->valuate($property),
                    'pricehubble' => $this->pricehubble->valuate(['property' => $property]),
                    'formula' => $this->formula->valuate($property),
                };
                $value = $this->price($response['estimated_value'] ?? null);
                if ($value === null) {
                    throw new RuntimeException('invalid_estimate');
                }
                $low = $response['range_low'] ?? null;
                $high = $response['range_high'] ?? null;
                $rangeSource = 'configured_percentage';
                if ($low !== null || $high !== null) {
                    $low = $this->price($low);
                    $high = $this->price($high);
                    if ($low === null || $high === null || $low > $value || $high < $value) {
                        throw new RuntimeException('invalid_range');
                    }
                    $rangeSource = 'provider';
                } else {
                    $low = round($value * (1 - $rangePercent / 100), 2);
                    $high = round($value * (1 + $rangePercent / 100), 2);
                    if ($this->price($low) === null || $this->price($high) === null) {
                        throw new RuntimeException('invalid_range');
                    }
                }
                $attempts[] = ['provider' => $provider, 'status' => 'success'];
                Log::info('valuation provider selected', ['lead_id' => $lead->id, 'provider' => $provider, 'range_source' => $rangeSource]);

                return new ValuationResult(
                    estimatedValue: $value, rangePercent: $rangePercent, rangeLow: $low, rangeHigh: $high,
                    rawResponse: ['provider' => $provider, 'range_source' => $rangeSource, 'attempts' => $attempts],
                    provider: $provider,
                );
            } catch (Throwable $e) {
                $reason = $e instanceof RequestException ? 'http_'.$e->response->status()
                    : ($e instanceof ConnectionException ? 'connection_error' : 'invalid_response');
                if ($e instanceof RuntimeException && preg_match('/^(http_\d{3}|unsupported_property|missing_property_data|invalid_json|invalid_estimate|invalid_range)$/', $e->getMessage())) {
                    $reason = $e->getMessage();
                }
                $attempts[] = ['provider' => $provider, 'status' => 'failed', 'reason' => $reason];
                Log::warning('valuation provider failed', ['lead_id' => $lead->id, 'provider' => $provider, 'reason' => $reason]);
            }
        }

        return new ValuationResult(null, $rangePercent, null, null,
            rawResponse: ['attempts' => $attempts], status: 'failed',
            errorMessage: 'Kein konfigurierter Bewertungsanbieter lieferte ein gültiges Ergebnis.');
    }

    private function price(mixed $value): ?float
    {
        // valuations columns use DECIMAL(12,2).
        return is_numeric($value) && is_finite((float) $value) && (float) $value >= 0.01 && (float) $value <= 9999999999.99
            ? round((float) $value, 2) : null;
    }
}
