<?php

namespace Tests\Unit;

use App\Services\OnOffice\OnOfficeClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OnOfficeClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('landingpages.onoffice.base_url', 'https://api.onoffice.test');
        config()->set('landingpages.onoffice.token', 'token');
        config()->set('landingpages.onoffice.secret', 'secret');
        config()->set('landingpages.onoffice.debug', false);
    }

    public function test_it_creates_an_estate_with_the_landing_page_note(): void
    {
        Http::fake([
            'https://api.onoffice.test' => Http::response($this->successResponse('estate-42'), 200),
        ]);

        $response = app(OnOfficeClient::class)->createEstate([
            'property_type' => 'Einfamilienhaus',
            'street' => 'Hauptstraße',
            'house_number' => '12',
            'zip' => '50667',
            'city' => 'Köln',
            'country' => 'DE',
            'living_area' => 140,
            'rooms' => 5,
        ], 'Landing Page Lead aus bestandsimmobilie');

        $this->assertSame('success', $response['status']);
        $this->assertSame('estate-42', $response['external_estate_id']);

        Http::assertSent(function ($request): bool {
            $action = $request->data()['request']['actions'][0];

            return $action['resourcetype'] === 'estate'
                && $action['parameters']['interne_Bemerkung'] === 'Landing Page Lead aus bestandsimmobilie'
                && $action['parameters']['strasse'] === 'Hauptstraße'
                && $action['parameters']['wohnflaeche'] === 140;
        });
    }

    public function test_it_creates_the_owner_relation_with_estate_and_contact_ids(): void
    {
        Http::fake([
            'https://api.onoffice.test' => Http::response($this->successResponse(), 200),
        ]);

        $response = app(OnOfficeClient::class)->createOwnerRelation('estate-42', 'contact-7');

        $this->assertSame('success', $response['status']);
        Http::assertSent(function ($request): bool {
            $action = $request->data()['request']['actions'][0];

            return $action['resourcetype'] === 'relation'
                && $action['parameters']['parentid'] === 'estate-42'
                && $action['parameters']['childid'] === 'contact-7'
                && $action['parameters']['relationtype'] === 'urn:onoffice-de-ns:smart:2.5:relationTypes:estate:address:owner';
        });
    }

    /** @return array<string, mixed> */
    private function successResponse(?string $recordId = null): array
    {
        return [
            'response' => [
                'results' => [[
                    'status' => ['errorcode' => 0],
                    'data' => ['records' => $recordId ? [['id' => $recordId]] : []],
                ]],
            ],
        ];
    }
}
