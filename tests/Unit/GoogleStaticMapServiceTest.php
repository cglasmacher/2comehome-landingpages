<?php

namespace Tests\Unit;

use App\Models\LeadProperty;
use App\Services\Maps\GoogleStaticMapService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleStaticMapServiceTest extends TestCase
{
    public function test_it_returns_a_data_uri_for_a_successful_map_response(): void
    {
        config()->set('services.google_maps.key', 'test-key');
        config()->set('services.google_maps.static_enabled', true);

        Http::fake([
            'maps.googleapis.com/*' => Http::response('fake-image', 200, ['Content-Type' => 'image/png']),
        ]);

        $property = new LeadProperty([
            'street' => 'Hauptstraße',
            'house_number' => '12',
            'zip' => '50667',
            'city' => 'Köln',
        ]);

        $image = app(GoogleStaticMapService::class)->forProperty($property);

        $this->assertSame('data:image/png;base64,'.base64_encode('fake-image'), $image);
    }

    public function test_it_returns_null_when_google_maps_fails(): void
    {
        config()->set('services.google_maps.key', 'test-key');
        config()->set('services.google_maps.static_enabled', true);

        Http::fake([
            'maps.googleapis.com/*' => Http::response('request denied', 403, ['Content-Type' => 'text/plain']),
        ]);

        $property = new LeadProperty([
            'street' => 'Hauptstraße',
            'house_number' => '12',
            'zip' => '50667',
            'city' => 'Köln',
        ]);

        $this->assertNull(app(GoogleStaticMapService::class)->forProperty($property));
    }
}
