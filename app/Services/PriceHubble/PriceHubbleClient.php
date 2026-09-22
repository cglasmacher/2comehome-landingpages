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
        $this->http = Http::timeout(config('valuation.providers.pricehubble.timeout'))
            ->acceptJson()
            ->asJson()
            ->withToken((string) config('valuation.providers.pricehubble.api_key'));
    }

    public function valuate(array $payload): array
    {
        $baseUrl = config('valuation.providers.pricehubble.base_url');

        if (! $baseUrl) {
            throw new \RuntimeException('not_configured');
        }

        try {
            $response = $this->http
                ->withOptions(['allow_redirects' => false])
                ->post(rtrim($baseUrl, '/') . '/valuations', $payload)
                ->throw()
                ->json();
        } catch (Throwable $e) {
            // The orchestrator logs a safe reason, never HTTP bodies or credentials.
            throw $e;
        }

        // Normalize the response so downstream code can always rely on `estimated_value`,
        // regardless of the exact field name PriceHubble returns.
        // TODO: Once the real PriceHubble API docs/credentials are available, verify and
        // simplify this mapping against the actual response schema.
        $response['estimated_value'] ??= $this->extractEstimatedValue($response);

        return $response;
    }

    /**
     * Best-effort extraction of the estimated value from common PriceHubble-style response shapes.
     */
    private function extractEstimatedValue(array $response): ?float
    {
        $candidates = [
            'estimated_value',
            'estimatedValue',
            'value',
            'price',
            'marketValue.value',
            'valuation.value',
            'valuation.estimatedValue',
            'result.value',
        ];

        foreach ($candidates as $path) {
            $value = data_get($response, $path);

            if (is_numeric($value)) {
                return (float) $value;
            }
        }

        return null;
    }

}
