<?php

namespace Tests\Unit;

use App\Models\Lead;
use App\Models\LeadProperty;
use App\Services\Valuation\ValuationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ValuationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config()->set('valuation.providers.somantic.api_key', 'private-test-key');
        config()->set('valuation.providers.somantic.base_url', 'https://somantic.test/api');
        config()->set('valuation.providers.pricehubble.api_key', null);
        config()->set('valuation.providers.pricehubble.base_url', null);
    }

    private function lead(string $propertyType = 'einfamilienhaus'): Lead
    {
        $lead = new Lead(['first_name' => 'Private', 'email' => 'private@example.test']);
        $lead->setRelation('property', new LeadProperty([
            'property_type' => $propertyType, 'street' => 'Eschenweg', 'house_number' => '39',
            'zip' => '40723', 'city' => 'Hilden', 'country' => 'DE',
            'living_area' => 100, 'plot_area' => 500, 'construction_year' => 1964, 'rooms' => 4,
            'number_of_units' => $propertyType === 'mehrfamilienhaus' ? 8 : null,
        ]));

        return $lead;
    }

    public function test_somantic_wins_and_preserves_asymmetric_provider_range(): void
    {
        Http::fake(['https://somantic.test/api/estimate' => Http::response([
            'estimates' => ['price' => 450000, 'price_range' => ['low' => 410000, 'high' => 480000]],
        ])]);
        $result = app(ValuationService::class)->createForLead($this->lead(), 7.5);
        $this->assertSame('somantic', $result->provider);
        $this->assertSame(450000.0, $result->estimatedValue);
        $this->assertSame(410000.0, $result->rangeLow);
        $this->assertSame(480000.0, $result->rangeHigh);
        $this->assertSame('provider', $result->rawResponse['range_source']);
        Http::assertSentCount(1);
        Http::assertSent(fn ($r) => $r->hasHeader('X-API-Key', 'private-test-key')
            && $r['street'] === 'Eschenweg 39' && $r['postcode'] === '40723'
            && $r['typ'] === 'haus' && (float) $r['square_meters'] === 100.0
            && ! isset($r['contact']) && ! isset($r['email']));
    }

    public function test_quota_error_falls_back_to_existing_formula_with_correct_provenance(): void
    {
        Http::fake(['*' => Http::response(['error' => 'secret body'], 429)]);
        Log::spy();
        $result = app(ValuationService::class)->createForLead($this->lead(), 7.5);
        $this->assertSame('formula', $result->provider);
        $this->assertSame(446000.0, $result->estimatedValue);
        $this->assertSame(412550.0, $result->rangeLow);
        $this->assertSame(479450.0, $result->rangeHigh);
        $this->assertSame('http_429', $result->rawResponse['attempts'][0]['reason']);
        $this->assertSame('not_configured', $result->rawResponse['attempts'][1]['reason']);
        Log::shouldHaveReceived('warning')->with('valuation provider failed', ['lead_id' => null, 'provider' => 'somantic', 'reason' => 'http_429'])->once();
        $this->assertStringNotContainsString('secret', json_encode($result->rawResponse));
        Http::assertSentCount(1);
    }

    public function test_order_can_select_formula_first_without_external_requests(): void
    {
        config()->set('valuation.order', ['formula', 'somantic']);
        $this->assertSame('formula', app(ValuationService::class)->createForLead($this->lead(), 10)->provider);
        Http::assertNothingSent();
    }

    public function test_disabled_and_unconfigured_services_are_skipped(): void
    {
        config()->set('valuation.providers.somantic.enabled', false);
        $result = app(ValuationService::class)->createForLead($this->lead(), 10);
        $this->assertSame('formula', $result->provider);
        $this->assertSame('disabled', $result->rawResponse['attempts'][0]['reason']);
        Http::assertNothingSent();
    }

    public function test_pricehubble_uses_official_multifamily_payload_and_provider_range(): void
    {
        config()->set('valuation.order', ['pricehubble', 'formula']);
        config()->set('valuation.providers.pricehubble.base_url', 'https://pricehubble.test');
        config()->set('valuation.providers.pricehubble.api_key', 'ph-test-key');
        Http::fake(['https://pricehubble.test/valuation/property_value' => Http::response([
            'valuations' => [[[
                'salePrice' => 600000,
                'salePriceRange' => ['lower' => 550000, 'upper' => 650000],
                'currency' => 'EUR',
            ]]],
        ])]);

        $result = app(ValuationService::class)->createForLead($this->lead('mehrfamilienhaus'), 10);

        $this->assertSame('pricehubble', $result->provider);
        $this->assertSame(600000.0, $result->estimatedValue);
        $this->assertSame(550000.0, $result->rangeLow);
        $this->assertSame(650000.0, $result->rangeHigh);
        $this->assertSame('provider', $result->rawResponse['range_source']);
        Http::assertSentCount(1);
        Http::assertSent(function ($request): bool {
            $data = $request->data();
            $property = data_get($data, 'valuationInputs.0.property', []);

            return $request->url() === 'https://pricehubble.test/valuation/property_value'
                && $request->hasHeader('Authorization', 'Bearer ph-test-key')
                && data_get($data, 'dealType') === 'sale'
                && data_get($property, 'propertyType.code') === 'multi_family_house'
                && data_get($property, 'numberOfUnits') === 8
                && (float) data_get($property, 'livingArea') === 100.0
                && ! array_key_exists('numberOfRooms', $property)
                && data_get($data, 'countryCode') === 'DE';
        });
    }

    public function test_formula_does_not_apply_single_home_room_bonus_to_multifamily_house(): void
    {
        config()->set('valuation.order', ['formula']);
        $lead = $this->lead('mehrfamilienhaus');
        $lead->property->rooms = 12;

        $result = app(ValuationService::class)->createForLead($lead, 10);

        $this->assertSame('formula', $result->provider);
        $this->assertSame(425000.0, $result->estimatedValue);
    }

    public function test_legacy_maisonette_is_not_valuated_as_a_new_property_type(): void
    {
        $result = app(ValuationService::class)->createForLead($this->lead('maisonette'), 10);

        $this->assertSame('failed', $result->status);
        $this->assertSame('unsupported_property', $result->rawResponse['attempts'][0]['reason']);
        $this->assertSame('not_configured', $result->rawResponse['attempts'][1]['reason']);
        $this->assertSame('unsupported_property', $result->rawResponse['attempts'][2]['reason']);
        Http::assertNothingSent();
    }

    public function test_single_provider_mode_fails_without_an_implicit_formula(): void
    {
        config()->set('valuation.order', ['somantic']);
        Http::fake(['*' => Http::response([], 401)]);
        $result = app(ValuationService::class)->createForLead($this->lead(), 10);
        $this->assertSame('failed', $result->status);
        $this->assertSame('none', $result->provider);
        $this->assertNull($result->estimatedValue);
    }

    public function test_invalid_values_and_ranges_do_not_become_completed_valuations(): void
    {
        config()->set('valuation.order', ['somantic']);
        foreach ([['price' => 0], ['price' => -1], ['price' => 'invalid'], ['price' => 1e20],
            ['price' => 100, 'price_range' => ['low' => 150, 'high' => 200]],
            ['price' => 100, 'price_range' => ['low' => 50]],
        ] as $estimates) {
            Http::fake(['*' => Http::response(['estimates' => $estimates])]);
            $this->assertSame('failed', app(ValuationService::class)->createForLead($this->lead(), 10)->status);
        }
    }

    public function test_missing_range_uses_configured_percentage(): void
    {
        Http::fake(['*' => Http::response(['estimates' => ['price' => 500000]])]);
        $result = app(ValuationService::class)->createForLead($this->lead(), 10);
        $this->assertSame(450000.0, $result->rangeLow);
        $this->assertSame(550000.0, $result->rangeHigh);
        $this->assertSame('configured_percentage', $result->rawResponse['range_source']);
    }

    public function test_timeout_falls_back_without_exposing_exception_content(): void
    {
        Http::fake(['*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('secret-key')]);
        $result = app(ValuationService::class)->createForLead($this->lead(), 10);
        $this->assertSame('formula', $result->provider);
        $this->assertSame('connection_error', $result->rawResponse['attempts'][0]['reason']);
        $this->assertStringNotContainsString('secret-key', json_encode($result->rawResponse));
    }

    public function test_unsupported_property_skips_somantic_request(): void
    {
        $lead = $this->lead();
        $lead->property->property_type = 'grundstück';
        $result = app(ValuationService::class)->createForLead($lead, 10);
        $this->assertSame('formula', $result->provider);
        $this->assertSame('unsupported_property', $result->rawResponse['attempts'][0]['reason']);
        Http::assertNothingSent();
    }
}
