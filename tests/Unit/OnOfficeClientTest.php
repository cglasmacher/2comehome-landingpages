<?php

namespace Tests\Unit;

use App\Services\OnOffice\OnOfficeClient;
use Illuminate\Support\Facades\Cache;
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
        config()->set('landingpages.onoffice.estate_status2_label', 'in akquise');
        Cache::flush();
    }

    public function test_it_creates_an_estate_with_the_landing_page_note(): void
    {
        Http::fake([
            'https://api.onoffice.test' => Http::sequence()
                ->push($this->fieldConfigurationResponse(['status2obj_akquise' => 'in akquise']), 200)
                ->push($this->successResponse('estate-42'), 200),
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
                && $action['parameters']['status'] === 2
                && $action['parameters']['status2'] === 'status2obj_akquise'
                && $action['parameters']['interne_Bemerkung'] === 'Landing Page Lead aus bestandsimmobilie'
                && $action['parameters']['strasse'] === 'Hauptstraße'
                && $action['parameters']['wohnflaeche'] === 140;
        });

        Http::assertSent(function ($request): bool {
            $action = $request->data()['request']['actions'][0];

            return $action['actionid'] === 'urn:onoffice-de-ns:smart:2.5:smartml:action:get'
                && $action['resourcetype'] === 'fields'
                && $action['parameters']['modules'] === ['estate']
                && $action['parameters']['fieldList'] === ['status2'];
        });
    }

    public function test_it_does_not_create_an_estate_when_status2_label_is_missing(): void
    {
        Http::fake([
            'https://api.onoffice.test' => Http::response(
                $this->fieldConfigurationResponse(['status2obj_aktiv' => 'Aktiv']),
                200,
            ),
        ]);

        $response = app(OnOfficeClient::class)->createEstate([], 'Landing Page Lead aus bestandsimmobilie');

        $this->assertSame('failed', $response['status']);
        $this->assertNull($response['external_estate_id']);
        $this->assertStringContainsString('in akquise', $response['message']);
        Http::assertSentCount(1);
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

    /** @param array<string, string> $permittedValues */
    private function fieldConfigurationResponse(array $permittedValues): array
    {
        return [
            'response' => [
                'results' => [[
                    'status' => ['errorcode' => 0],
                    'data' => [
                        'records' => [[
                            'id' => 'estate',
                            'elements' => [
                                'status2' => [
                                    'type' => 'singleselect',
                                    'permittedvalues' => $permittedValues,
                                ],
                            ],
                        ]],
                    ],
                ]],
            ],
        ];
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
