<?php

namespace App\Services\Maps;

use App\Models\LeadProperty;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleStaticMapService
{
    public function forProperty(?LeadProperty $property): ?string
    {
        if (! $property || ! config('services.google_maps.static_enabled', true)) {
            return null;
        }

        $apiKey = trim((string) config('services.google_maps.key'));
        if ($apiKey === '') {
            Log::warning('Google Static Maps skipped: API key is missing.');

            return null;
        }

        $address = $this->address($property);
        if ($address === null) {
            Log::warning('Google Static Maps skipped: property address is incomplete.', [
                'property_id' => $property->id,
            ]);

            return null;
        }

        $parameters = [
            'center' => $address,
            'zoom' => (int) config('services.google_maps.static_zoom', 17),
            'size' => (string) config('services.google_maps.static_size', '640x320'),
            'scale' => (int) config('services.google_maps.static_scale', 2),
            'maptype' => 'roadmap',
            'markers' => 'color:0x0B2D48|'.$address,
            'key' => $apiKey,
        ];

        // Quiet cadastral-style presentation for valuation reports:
        // remove POIs/transit and most contextual labels, keep roads/buildings readable.
        $styles = [
            'feature:all|element:geometry|saturation:-100|lightness:18',
            'feature:all|element:labels|visibility:off',
            'feature:poi|visibility:off',
            'feature:transit|visibility:off',
            'feature:administrative|element:labels|visibility:off',
            'feature:landscape.natural|element:labels|visibility:off',
            'feature:landscape.man_made|element:geometry|color:0xeeeeea|visibility:on',
            'feature:road|element:geometry|color:0xffffff|visibility:simplified',
            'feature:road|element:labels.text|color:0x4f555b|visibility:on',
            'feature:road|element:labels.icon|visibility:off',
            'feature:administrative.land_parcel|element:labels.text|color:0x666666|visibility:on',
            'feature:water|element:geometry|color:0xe5ebef',
            'feature:water|element:labels|visibility:off',
        ];

        $endpoint = 'https://maps.googleapis.com/maps/api/staticmap?'
            .implode('&', array_map(
                static fn (string $style): string => 'style='.rawurlencode($style),
                $styles,
            ));

        try {
            $response = Http::timeout((int) config('services.google_maps.timeout', 10))
                ->accept('image/*')
                ->get($endpoint, $parameters);

            if (! $response->successful()) {
                Log::error('Google Static Maps API request failed.', [
                    'property_id' => $property->id,
                    'address' => $address,
                    'http_status' => $response->status(),
                    'response_body' => mb_substr($response->body(), 0, 2000),
                ]);

                return null;
            }

            $contentType = strtolower((string) $response->header('Content-Type'));
            if (! str_starts_with($contentType, 'image/')) {
                Log::error('Google Static Maps returned a non-image response.', [
                    'property_id' => $property->id,
                    'address' => $address,
                    'content_type' => $contentType,
                    'response_body' => mb_substr($response->body(), 0, 2000),
                ]);

                return null;
            }

            $mimeType = trim(explode(';', $contentType)[0]);

            Log::info('Google Static Maps image loaded.', [
                'property_id' => $property->id,
                'address' => $address,
                'http_status' => $response->status(),
                'bytes' => strlen($response->body()),
            ]);

            return 'data:'.$mimeType.';base64,'.base64_encode($response->body());
        } catch (Throwable $e) {
            Log::error('Google Static Maps request exception.', [
                'property_id' => $property->id,
                'address' => $address,
                'exception_type' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function address(LeadProperty $property): ?string
    {
        $street = trim((string) $property->street);
        $houseNumber = trim((string) $property->house_number);
        $zip = trim((string) $property->zip);
        $city = trim((string) $property->city);

        if ($street === '' || $houseNumber === '' || $zip === '' || $city === '') {
            return null;
        }

        return sprintf('%s %s, %s %s, Germany', $street, $houseNumber, $zip, $city);
    }
}
