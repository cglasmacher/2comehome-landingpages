<?php

namespace App\Services\Valuation;

use Illuminate\Support\Str;
use RuntimeException;

class FormulaClient
{
    public function valuate(array $property): array
    {
        $propertyType = Str::lower(trim((string) ($property['property_type'] ?? '')));
        $typeConfig = config('landingpages.property_types.'.$propertyType);

        if ($propertyType === '' || ! is_array($typeConfig) || ($typeConfig['selectable'] ?? true) !== true) {
            throw new RuntimeException('unsupported_property');
        }

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

        // Room count is useful for individual homes/apartments but would systematically
        // overvalue multi-family houses, where a high total room count is expected.
        if ($propertyType !== 'mehrfamilienhaus'
            && ! empty($property['rooms'])
            && (float) $property['rooms'] >= 4) {
            $base *= 1.05;
        }

        $estimated = round($base, -3);

        return [
            'estimated_value' => $estimated,
            'currency' => 'EUR',
            'method' => 'formula',
            'provider' => 'formula',
        ];
    }
}
