<?php

namespace App\DTO;

readonly class ValuationResult
{
    public function __construct(
        public ?float $estimatedValue,
        public float $rangePercent,
        public ?float $rangeLow,
        public ?float $rangeHigh,
        public array $rawResponse = [],
        public string $status = 'completed',
        public ?string $errorMessage = null,
    ) {}
}
