<?php

namespace Tests\Unit;

use App\Services\OnOffice\OnOfficeClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
        config()->set('landingpages.onoffice.estate_user_id', 17);
        config()->set('landingpages.onoffice.estate_note_field', 'interne_Bemerkung');
        Http::preventStrayRequests();
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
                && $action['parameters']['data']['status'] === 2
                && $action['parameters']['data']['status2'] === 'status2obj_akquise'
                && $action['parameters']['data']['interne_Bemerkung'] === 'Landing Page Lead aus bestandsimmobilie'
                && $action['parameters']['data']['strasse'] === 'Hauptstraße'
                && $action['parameters']['data']['wohnflaeche'] === 140
                && $action['parameters']['data']['benutzer'] === 17
                && $action['parameters']['data']['land'] === 'DEU'
                && $action['parameters']['data']['objektart'] === 'haus'
                && $action['parameters']['data']['objekttyp'] === 'einfamilienhaus';
        });

        Http::assertSent(function ($request): bool {
            $action = $request->data()['request']['actions'][0];

            return $action['actionid'] === 'urn:onoffice-de-ns:smart:2.5:smartml:action:get'
                && $action['resourcetype'] === 'fields'
                && $action['parameters']['modules'] === ['estate']
                && ! isset($action['parameters']['fieldList']);
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
                && $action['parameters']['parentid'] === ['estate-42']
                && $action['parameters']['childid'] === ['contact-7']
                && $action['parameters']['relationtype'] === 'urn:onoffice-de-ns:smart:2.5:relationTypes:estate:address:owner';
        });
    }

    public function test_it_rejects_global_errors_and_logs_them_without_debug(): void
    {
        Log::spy();
        Http::fake(['*' => Http::response([
            'status' => ['code' => 500, 'errorcode' => 75, 'message' => 'Unknown field'],
        ])]);
        $response = app(OnOfficeClient::class)->createContactWithRemark([], '');
        $this->assertSame('failed', $response['status']);
        $this->assertSame('Unknown field', $response['message']);
        Log::shouldHaveReceived('log')->withArgs(
            fn ($level, $message, $context) => $level === 'error' && $context['global_errorcode'] === 75
        )->once();
    }

    public function test_it_rejects_malformed_http_and_idless_create_responses(): void
    {
        Http::fake(['*' => Http::sequence()
            ->push('<html>Proxy failure</html>', 200)
            ->push([], 200)
            ->push($this->successResponse(), 200)
            ->push($this->successResponse('42'), 503)
            ->push(['response' => ['results' => [['status' => ['errorcode' => 23, 'message' => 'Missing configuration']]]]], 200),
        ]);
        for ($i = 0; $i < 5; $i++) {
            $this->assertSame('failed', app(OnOfficeClient::class)->createContactWithRemark([], '')['status']);
        }
        Http::assertSentCount(5);
    }

    public function test_missing_credentials_do_not_report_demo_success(): void
    {
        config()->set('landingpages.onoffice.token', null);
        Http::fake();
        $this->assertSame('failed', app(OnOfficeClient::class)->createContactWithRemark([], '')['status']);
        $this->assertSame('failed', app(OnOfficeClient::class)->createEstate([], '')['status']);
        Http::assertNothingSent();
    }

    public function test_it_resolves_cg_and_reuses_the_account_scoped_cache(): void
    {
        config()->set('landingpages.onoffice.estate_user_id', null);
        Http::fake(['*' => Http::sequence()
            ->push($this->fieldConfigurationResponse(['custom_akquise' => 'In Akquise']))
            ->push(['response' => ['results' => [[
                'status' => ['errorcode' => 0],
                'data' => ['records' => [['id' => 29, 'elements' => ['Kuerzel' => 'CG']]]],
            ]]]])
            ->push($this->successResponse('42'))
            ->push($this->successResponse('43')),
        ]);
        $client = app(OnOfficeClient::class);
        $this->assertSame('success', $client->createEstate(['property_type' => 'maisonette'], '')['status']);
        $this->assertSame('success', $client->createEstate(['property_type' => 'wohnung'], '')['status']);
        Http::assertSentCount(4);
        Http::assertSent(fn ($request) => data_get($request->data(), 'request.actions.0.parameters.data.benutzer') === 29
            && data_get($request->data(), 'request.actions.0.parameters.data.objekttyp') === 'maisonette');
        Http::assertSent(fn ($request) => data_get($request->data(), 'request.actions.0.resourcetype') === 'user'
            && data_get($request->data(), 'request.actions.0.parameters.filter.Kuerzel.0.val') === 'CG');
    }

    public function test_ambiguous_cg_prevents_an_estate_create(): void
    {
        config()->set('landingpages.onoffice.estate_user_id', null);
        Http::fake(['*' => Http::sequence()
            ->push($this->fieldConfigurationResponse(['custom_akquise' => 'In Akquise']))
            ->push(['response' => ['results' => [[
                'status' => ['errorcode' => 0],
                'data' => ['records' => [
                    ['id' => 29, 'elements' => ['Kuerzel' => 'CG']],
                    ['id' => 30, 'elements' => ['Kuerzel' => 'CG']],
                ]],
            ]]]]),
        ]);
        $response = app(OnOfficeClient::class)->createEstate([], '');
        $this->assertSame('failed', $response['status']);
        $this->assertStringContainsString('exactly one user', $response['message']);
        Http::assertSentCount(2);
    }

    public function test_diagnosis_never_creates_records_and_returns_failure_exit_code(): void
    {
        Http::fake(['*' => Http::response($this->fieldConfigurationResponse(['other' => 'Aktiv']))]);
        $this->artisan('onoffice:diagnose')->assertExitCode(1);
        Http::assertSentCount(1);
        Http::assertNotSent(fn ($request) => str_ends_with(data_get($request->data(), 'request.actions.0.actionid'), ':create'));
    }

    public function test_transport_failure_is_not_retried(): void
    {
        Http::fake(['*' => Http::failedConnection()]);
        $response = app(OnOfficeClient::class)->createContactWithRemark([], '');
        $this->assertSame('failed', $response['status']);
        $this->assertStringContainsString('outcome unknown', $response['message']);
    }

    public function test_all_form_types_are_mapped_and_unknown_types_are_not_sent(): void
    {
        $cases = [
            'einfamilienhaus' => ['haus', 'einfamilienhaus'],
            'doppelhaushälfte' => ['haus', 'doppelhaushaelfte'],
            'reihenhaus' => ['haus', 'reihenhaus'],
            'wohnung' => ['wohnung', null],
            'maisonette' => ['wohnung', 'maisonette'],
            'grundstück' => ['grundstueck', null],
        ];
        Http::fake(function ($request) use ($cases) {
            $action = $request->data()['request']['actions'][0];
            if ($action['resourcetype'] === 'fields') {
                return Http::response($this->fieldConfigurationResponse(['custom' => 'In Akquise']));
            }
            $data = $action['parameters']['data'];
            [$kind, $subtype] = $cases[$data['interne_Bemerkung']];
            $this->assertSame($kind, $data['objektart']);
            $this->assertSame($subtype, $data['objekttyp'] ?? null);

            return Http::response($this->successResponse('42'));
        });
        foreach ($cases as $type => $expected) {
            $this->assertSame('success', app(OnOfficeClient::class)->createEstate(['property_type' => $type], $type)['status']);
        }
        Http::assertSentCount(7);
        $this->expectException(\InvalidArgumentException::class);
        app(OnOfficeClient::class)->createEstate(['property_type' => 'unmapped'], '');
    }

    public function test_denied_user_read_permission_prevents_estate_creation(): void
    {
        config()->set('landingpages.onoffice.estate_user_id', null);
        Http::fake(['*' => Http::sequence()
            ->push($this->fieldConfigurationResponse(['custom' => 'In Akquise']))
            ->push(['status' => ['code' => 500, 'errorcode' => 99, 'message' => 'Permission denied']]),
        ]);
        $response = app(OnOfficeClient::class)->createEstate([], '');
        $this->assertSame('failed', $response['status']);
        $this->assertStringContainsString('ONOFFICE_ESTATE_USER_ID', $response['message']);
        Http::assertSentCount(2);
    }

    public function test_missing_optional_note_does_not_block_estate_or_leak_into_public_text(): void
    {
        Log::spy();
        $fields = $this->fieldConfigurationResponse(['in_akquise' => 'In Akquise']);
        unset($fields['response']['results'][0]['data']['records'][0]['elements']['interne_Bemerkung']);
        Http::fake(['*' => Http::sequence()->push($fields)->push($this->successResponse('42'))]);
        $response = app(OnOfficeClient::class)->createEstate(['property_type' => 'einfamilienhaus'], 'Private source note');
        $this->assertSame('success', $response['status']);
        Http::assertSent(function ($request) {
            $action = $request->data()['request']['actions'][0];

            return $action['resourcetype'] === 'estate'
                && ! array_key_exists('interne_Bemerkung', $action['parameters']['data'])
                && ! in_array('Private source note', $action['parameters']['data'], true)
                && $action['parameters']['data']['status2'] === 'in_akquise'
                && $action['parameters']['data']['benutzer'] === 17;
        });
        Log::shouldHaveReceived('warning')->withArgs(fn ($message, $context) => $context['field'] === 'interne_Bemerkung')->once();
    }

    public function test_unknown_property_field_is_named_before_any_estate_write(): void
    {
        $fields = $this->fieldConfigurationResponse(['in_akquise' => 'In Akquise']);
        unset($fields['response']['results'][0]['data']['records'][0]['elements']['hausnummer']);
        Http::fake(['*' => Http::response($fields)]);
        $response = app(OnOfficeClient::class)->createEstate(['house_number' => '39'], '');
        $this->assertSame('failed', $response['status']);
        $this->assertSame(['hausnummer'], $response['raw']['unknown_fields']);
        $this->assertStringContainsString('hausnummer', $response['message']);
        Http::assertSentCount(1);
    }

    public function test_diagnosis_reports_optional_and_required_fields_and_internal_candidates(): void
    {
        $fields = $this->fieldConfigurationResponse(['in_akquise' => 'In Akquise']);
        $elements = &$fields['response']['results'][0]['data']['records'][0]['elements'];
        unset($elements['interne_Bemerkung'], $elements['hausnummer']);
        $elements['custom_private'] = ['type' => 'freetext', 'label' => 'Interne Notiz'];
        Http::fake(['*' => Http::response($fields)]);
        $result = app(OnOfficeClient::class)->diagnose();
        $this->assertSame('success', $result['status2']['status']);
        $this->assertSame('failed', $result['estate_fields']['status']);
        $this->assertSame(['hausnummer'], $result['estate_fields']['unknown_fields']);
        $this->assertFalse($result['estate_fields']['note_field_available']);
        $this->assertSame(['custom_private' => 'Interne Notiz'], $result['estate_fields']['note_field_candidates']);
        Http::assertSentCount(1);
    }

    public function test_configured_internal_field_is_used_only_when_present(): void
    {
        config()->set('landingpages.onoffice.estate_note_field', 'private_note');
        $fields = $this->fieldConfigurationResponse(['in_akquise' => 'In Akquise']);
        $fields['response']['results'][0]['data']['records'][0]['elements']['private_note'] = ['type' => 'freetext'];
        Http::fake(['*' => Http::sequence()->push($fields)->push($this->successResponse('42'))]);
        $this->assertSame('success', app(OnOfficeClient::class)->createEstate([], 'Internal source')['status']);
        Http::assertSent(fn ($request) => data_get($request->data(), 'request.actions.0.parameters.data.private_note') === 'Internal source');
    }

    public function test_description_and_prices_are_kept_when_numeric_fields_are_not_configured(): void
    {
        Http::fake(['*' => Http::sequence()
            ->push($this->fieldConfigurationResponse(['in_akquise' => 'In Akquise']))
            ->push($this->successResponse('42'))]);
        $description = "Nachricht des Interessenten:\nBitte anrufen.\n\nMinimum: 450.000,00 EUR";
        $result = app(OnOfficeClient::class)->createEstate([
            'description' => $description, 'valuation' => ['range_low' => 450000],
        ], 'Landingpage Lead von test');
        $this->assertSame('success', $result['status']);
        $this->assertSame('skipped', $result['valuation_fields']['status']);
        Http::assertSent(fn ($request) => data_get($request->data(), 'request.actions.0.parameters.data.objektbeschreibung') === $description
            && data_get($request->data(), 'request.actions.0.parameters.data.interne_Bemerkung') === 'Landingpage Lead von test');
        Http::assertSentCount(2);
    }

    public function test_price_fields_are_written_separately_and_read_back(): void
    {
        config()->set('landingpages.onoffice.estate_valuation_fields', ['range_low' => 'custom_min', 'range_high' => 'custom_max']);
        $fields = $this->fieldConfigurationResponse(['in_akquise' => 'In Akquise']);
        $elements = &$fields['response']['results'][0]['data']['records'][0]['elements'];
        $elements['custom_min'] = $elements['custom_max'] = ['type' => 'float'];
        $read = $this->successResponse('42');
        $read['response']['results'][0]['data']['records'][0]['elements'] = ['custom_min' => '450000.25', 'custom_max' => '550000.75'];
        Http::fake(['*' => Http::sequence()->push($fields)->push($this->successResponse('42'))
            ->push($this->successResponse())->push($read)]);
        $result = app(OnOfficeClient::class)->createEstate(['valuation' => ['range_low' => 450000.25, 'range_high' => 550000.75]], 'Source');
        $this->assertSame('success', $result['valuation_fields']['status']);
        Http::assertSent(fn ($request) => data_get($request->data(), 'request.actions.0.actionid') === 'urn:onoffice-de-ns:smart:2.5:smartml:action:modify'
            && data_get($request->data(), 'request.actions.0.resourceid') === '42'
            && data_get($request->data(), 'request.actions.0.parameters.data') === ['custom_min' => 450000.25, 'custom_max' => 550000.75]);
        Http::assertSentCount(4);
    }

    public function test_rejected_price_field_does_not_lose_the_created_estate(): void
    {
        config()->set('landingpages.onoffice.estate_valuation_fields', ['range_low' => 'custom_min']);
        $fields = $this->fieldConfigurationResponse(['in_akquise' => 'In Akquise']);
        $fields['response']['results'][0]['data']['records'][0]['elements']['custom_min'] = ['type' => 'float'];
        Http::fake(['*' => Http::sequence()->push($fields)->push($this->successResponse('42'))
            ->push(['status' => ['errorcode' => 143, 'message' => 'Read only']])]);
        $result = app(OnOfficeClient::class)->createEstate(['valuation' => ['range_low' => 450000]], 'Source');
        $this->assertSame('success', $result['status']);
        $this->assertSame('42', $result['external_estate_id']);
        $this->assertSame('warning', $result['valuation_fields']['status']);
        Http::assertSentCount(3);
    }

    public function test_ignored_price_write_is_reported_as_unverified(): void
    {
        config()->set('landingpages.onoffice.estate_valuation_fields', ['range_low' => 'custom_min']);
        $fields = $this->fieldConfigurationResponse(['in_akquise' => 'In Akquise']);
        $fields['response']['results'][0]['data']['records'][0]['elements']['custom_min'] = ['type' => 'float'];
        Http::fake(['*' => Http::sequence()->push($fields)->push($this->successResponse('42'))
            ->push($this->successResponse())->push($this->successResponse('42'))]);
        $result = app(OnOfficeClient::class)->createEstate(['valuation' => ['range_low' => 450000]], 'Source');
        $this->assertSame('success', $result['status']);
        $this->assertSame('warning', $result['valuation_fields']['status']);
        Http::assertSentCount(4);
    }

    public function test_unknown_price_fields_are_not_sent(): void
    {
        config()->set('landingpages.onoffice.estate_valuation_fields', ['range_low' => 'missing']);
        Http::fake(['*' => Http::sequence()->push($this->fieldConfigurationResponse(['in_akquise' => 'In Akquise']))
            ->push($this->successResponse('42'))]);
        $result = app(OnOfficeClient::class)->createEstate(['valuation' => ['range_low' => 450000]], 'Source');
        $this->assertSame('success', $result['status']);
        $this->assertSame('warning', $result['valuation_fields']['status']);
        Http::assertSentCount(2);
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
                            'elements' => array_merge(array_fill_keys([
                                'objektart', 'objekttyp', 'status', 'benutzer', 'nutzungsart', 'vermarktungsart',
                                'strasse', 'hausnummer', 'plz', 'ort', 'land', 'baujahr', 'wohnflaeche',
                                'grundstuecksflaeche', 'anzahl_zimmer', 'interne_Bemerkung', 'objektbeschreibung',
                            ], ['type' => 'freetext']), [
                                'status2' => [
                                    'type' => 'singleselect',
                                    'permittedvalues' => $permittedValues,
                                ],
                            ]),
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
