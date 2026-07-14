<?php

namespace App\Services\PriceHubble;

use App\DTO\ValuationResult;
use App\Models\Lead;
use Throwable;

class ValuationService
{
    public function __construct(private readonly PriceHubbleClient $client) {}

    public function createForLead(Lead $lead, float $rangePercent): ValuationResult
    {
        $lead->loadMissing('property');

        $payload = [
            'lead_uuid' => $lead->uuid,
            'property' => $lead->property?->toArray() ?? [],
            'contact' => [
                'first_name' => $lead->first_name,
                'last_name' => $lead->last_name,
                'email' => $lead->email,
                'phone' => $lead->phone,
            ],
        ];

        try {
            $response = $this->client->valuate($payload);
            $estimatedValue = (float) data_get($response, 'estimated_value');
            $rangeLow = $estimatedValue ? $estimatedValue * (1 - $rangePercent / 100) : null;
            $rangeHigh = $estimatedValue ? $estimatedValue * (1 + $rangePercent / 100) : null;

            return new ValuationResult(
                estimatedValue: $estimatedValue ?: null,
                rangePercent: $rangePercent,
                rangeLow: $rangeLow,
                rangeHigh: $rangeHigh,
                rawResponse: $response,
            );
        } catch (Throwable $e) {
            report($e);

            return new ValuationResult(
                estimatedValue: null,
                rangePercent: $rangePercent,
                rangeLow: null,
                rangeHigh: null,
                rawResponse: [],
                status: 'failed',
                errorMessage: $e->getMessage(),
            );
        }
    }
}
