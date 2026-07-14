<?php

namespace App\Services\PriceHubble;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PriceHubbleClient
{
    private PendingRequest $http;

    public function __construct()
    {
        $this->http = Http::timeout(config('landingpages.pricehubble.timeout'))
            ->acceptJson()
            ->asJson()
            ->withToken((string) config('landingpages.pricehubble.api_key'));
    }

    public function valuate(array $payload): array
    {
        $baseUrl = config('landingpages.pricehubble.base_url');

        if (! $baseUrl) {
            return $this->fakeValuation($payload);
        }

        try {
            return $this->http
                ->post(rtrim($baseUrl, '/') . '/valuations', $payload)
                ->throw()
                ->json();
        } catch (Throwable $e) {
            Log::error('PriceHubble API call failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function fakeValuation(array $payload): array
    {
        $property = $payload['property'] ?? [];
        $livingArea = (float) ($property['living_area'] ?? 100);
        $zip = (string) ($property['zip'] ?? '');

        $zipFactor = match (true) {
            str_starts_with($zip, '8') => 7_500,
            str_starts_with($zip, '2') => 4_500,
            str_starts_with($zip, '1') => 4_000,
            str_starts_with($zip, '4') => 3_500,
            str_starts_with($zip, '6') => 3_800,
            default => 3_200,
        };

        $base = $livingArea * $zipFactor;

        if (! empty($property['plot_area']) && (float) $property['plot_area'] > 0) {
            $base += ((float) $property['plot_area'] * 150);
        }

        if (! empty($property['construction_year']) && (int) $property['construction_year'] > 2000) {
            $base *= 1.15;
        }

        if (! empty($property['rooms']) && (float) $property['rooms'] >= 4) {
            $base *= 1.05;
        }

        $estimated = round($base, -3);

        return [
            'estimated_value' => $estimated,
            'currency' => 'EUR',
            'confidence' => 'demo',
            'provider' => 'fake_pricehubble_until_credentials_arrive',
        ];
    }
}
