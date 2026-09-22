<?php

namespace App\Services\Valuation;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class SomanticClient
{
    public function valuate(array $property): array
    {
        $type = Str::lower(trim((string) ($property['property_type'] ?? '')));
        $somanticType = config('landingpages.property_types.'.$type.'.somantic');
        if (! is_string($somanticType) || $somanticType === ''
            || ! in_array(strtoupper($property['country'] ?? 'DE'), ['DE', 'DEU'], true)) {
            throw new RuntimeException('unsupported_property');
        }
        $payload = [
            'typ' => $somanticType,
            'street' => trim(($property['street'] ?? '').' '.($property['house_number'] ?? '')),
            'postcode' => (string) ($property['zip'] ?? ''),
            'city' => (string) ($property['city'] ?? ''),
            'square_meters' => (float) ($property['living_area'] ?? 0),
        ];
        if (empty($property['street']) || ! preg_match('/^\d{5}$/', $payload['postcode'])
            || trim($payload['city']) === '' || $payload['square_meters'] <= 0) {
            throw new RuntimeException('missing_property_data');
        }
        foreach (['rooms' => 'rooms', 'construction_year' => 'year_of_construction'] as $source => $target) {
            if (is_numeric($property[$source] ?? null) && $property[$source] > 0) {
                $payload[$target] = (float) $property[$source];
            }
        }
        $response = Http::connectTimeout(3)->timeout(max(1, (int) config('valuation.providers.somantic.timeout', 10)))
            ->acceptJson()->asJson()->withHeaders(['X-API-Key' => config('valuation.providers.somantic.api_key')])
            ->withOptions(['allow_redirects' => false])
            ->post(rtrim(config('valuation.providers.somantic.base_url'), '/').'/estimate', $payload);
        if (! $response->successful()) {
            throw new RuntimeException('http_'.$response->status());
        }
        $body = $response->json();
        if (! is_array($body)) {
            throw new RuntimeException('invalid_json');
        }

        // Only normalized valuation data; no contact details, API keys or full response in logs/DB.
        return [
            'estimated_value' => data_get($body, 'estimates.price'),
            'range_low' => data_get($body, 'estimates.price_range.low'),
            'range_high' => data_get($body, 'estimates.price_range.high'),
        ];
    }
}
