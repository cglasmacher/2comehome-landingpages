<?php

namespace App\Services\PriceHubble;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
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
        $baseUrl = trim((string) config('valuation.providers.pricehubble.base_url'));

        if ($baseUrl === '') {
            throw new RuntimeException('not_configured');
        }

        $property = $payload['property'] ?? $payload;
        $requestPayload = $this->buildRequestPayload($property);
        $endpoint = str_ends_with($baseUrl, '/valuation/property_value')
            ? $baseUrl
            : rtrim($baseUrl, '/') . '/valuation/property_value';

        try {
            $response = $this->http
                ->withOptions(['allow_redirects' => false])
                ->post($endpoint, $requestPayload)
                ->throw()
                ->json();
        } catch (Throwable $e) {
            // The orchestrator logs a safe reason, never HTTP bodies or credentials.
            throw $e;
        }

        if (! is_array($response)) {
            throw new RuntimeException('invalid_json');
        }

        $valuation = data_get($response, 'valuations.0.0');
        if (is_array($valuation)) {
            $response['estimated_value'] ??= $this->numeric(
                $valuation['salePrice'] ?? $valuation['value'] ?? null
            );
            $response['range_low'] ??= $this->numeric(
                data_get($valuation, 'salePriceRange.lower', data_get($valuation, 'valueRange.lower'))
            );
            $response['range_high'] ??= $this->numeric(
                data_get($valuation, 'salePriceRange.upper', data_get($valuation, 'valueRange.upper'))
            );
            $response['currency'] ??= $valuation['currency'] ?? null;
        }

        $response['estimated_value'] ??= $this->extractEstimatedValue($response);

        return $response;
    }

    /** @param array<string, mixed> $property */
    private function buildRequestPayload(array $property): array
    {
        $type = Str::lower(trim((string) ($property['property_type'] ?? '')));
        $typeConfig = config('landingpages.property_types.' . $type);

        if (! is_array($typeConfig) || ($typeConfig['selectable'] ?? true) !== true) {
            throw new RuntimeException('unsupported_property');
        }

        $priceHubbleType = $typeConfig['pricehubble'] ?? null;
        if (! is_array($priceHubbleType) || ! filled($priceHubbleType['code'] ?? null)) {
            throw new RuntimeException('unsupported_property');
        }

        $countryCode = strtoupper(trim((string) ($property['country'] ?? 'DE')));
        $street = trim((string) ($property['street'] ?? ''));
        $postCode = trim((string) ($property['zip'] ?? ''));
        $city = trim((string) ($property['city'] ?? ''));
        $livingArea = $this->numeric($property['living_area'] ?? null);
        $buildingYear = filter_var($property['construction_year'] ?? null, FILTER_VALIDATE_INT);

        if ($street === '' || $postCode === '' || $city === '' || $livingArea === null || $buildingYear === false) {
            throw new RuntimeException('missing_property_data');
        }

        $propertyPayload = [
            'location' => [
                'address' => array_filter([
                    'postCode' => $postCode,
                    'city' => $city,
                    'street' => $street,
                    'houseNumber' => trim((string) ($property['house_number'] ?? '')) ?: null,
                ], static fn ($value) => $value !== null && $value !== ''),
            ],
            'propertyType' => array_filter([
                'code' => (string) $priceHubbleType['code'],
                'subcode' => filled($priceHubbleType['subcode'] ?? null) ? (string) $priceHubbleType['subcode'] : null,
            ]),
            'buildingYear' => (int) $buildingYear,
            'livingArea' => $livingArea,
        ];

        $propertyCode = (string) $priceHubbleType['code'];
        $landArea = $this->numeric($property['plot_area'] ?? null);

        if ($propertyCode === 'house') {
            if ($landArea === null) {
                throw new RuntimeException('missing_property_data');
            }
            $propertyPayload['landArea'] = $landArea;
        } elseif ($propertyCode === 'multi_family_house' && $landArea !== null) {
            $propertyPayload['landArea'] = $landArea;
        }

        if ($propertyCode !== 'multi_family_house') {
            $rooms = $this->numeric($property['rooms'] ?? null);
            if ($rooms !== null) {
                $propertyPayload['numberOfRooms'] = $rooms;
            }
        }

        return [
            'dealType' => 'sale',
            'valuationInputs' => [
                ['property' => $propertyPayload],
            ],
            'returnScores' => false,
            'countryCode' => $countryCode,
        ];
    }

    private function numeric(mixed $value): ?float
    {
        return is_numeric($value) && is_finite((float) $value) ? (float) $value : null;
    }

    /**
     * Best-effort extraction for compatibility with older/custom PriceHubble response wrappers.
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
            'valuations.0.0.salePrice',
            'valuations.0.0.value',
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
