<?php

namespace App\Services\PriceHubble;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

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

        if (!$baseUrl) {
            return $this->fakeValuation($payload);
        }

        return $this->http
            ->post(rtrim($baseUrl, '/') . '/valuations', $payload)
            ->throw()
            ->json();
    }

    private function fakeValuation(array $payload): array
    {
        $livingArea = (int) data_get($payload, 'property.living_area', 100);
        $base = max(250000, $livingArea * 4300);

        return [
            'estimated_value' => $base,
            'confidence' => 'demo',
            'provider' => 'fake_pricehubble_until_credentials_arrive',
        ];
    }
}
