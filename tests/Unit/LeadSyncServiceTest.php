<?php

namespace Tests\Unit;

use App\Models\LandingPage;
use App\Models\LandingPageTemplate;
use App\Models\Lead;
use App\Models\LeadProperty;
use App\Models\LeadSyncLog;
use App\Services\OnOffice\LeadSyncService;
use App\Services\OnOffice\OnOfficeClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class LeadSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_contact_estate_and_owner_relation_in_order(): void
    {
        $lead = $this->makeLead();
        $client = Mockery::mock(OnOfficeClient::class);
        $client->shouldReceive('createContactWithRemark')->once()->andReturn([
            'status' => 'success', 'external_contact_id' => 'contact-7', 'raw' => [],
        ]);
        $client->shouldReceive('createEstate')->once()->withArgs(function (array $property, string $note): bool {
            return $property['city'] === 'Köln' && $note === 'Landing Page Lead aus bestandsimmobilie';
        })->andReturn([
            'status' => 'success', 'external_estate_id' => 'estate-42', 'raw' => [],
        ]);
        $client->shouldReceive('createOwnerRelation')->once()->with('estate-42', 'contact-7')->andReturn([
            'status' => 'success', 'raw' => [],
        ]);

        $log = (new LeadSyncService($client))->sync($lead);

        $this->assertSame('success', $log->status);
        $this->assertSame('contact-7', $log->external_contact_id);
        $this->assertSame('estate-42', $log->external_estate_id);
        $this->assertSame('estate-42', data_get($log->response_payload, 'estate.external_estate_id'));
    }

    public function test_it_marks_estate_failure_as_partial_and_skips_the_relation(): void
    {
        $lead = $this->makeLead();
        $client = Mockery::mock(OnOfficeClient::class);
        $client->shouldReceive('createContactWithRemark')->once()->andReturn([
            'status' => 'success', 'external_contact_id' => 'contact-7', 'raw' => [],
        ]);
        $client->shouldReceive('createEstate')->once()->andReturn([
            'status' => 'failed', 'external_estate_id' => null,
            'raw' => ['response' => ['results' => [['status' => ['message' => 'Estate rejected']]]]],
        ]);
        $client->shouldReceive('createOwnerRelation')->never();

        $log = (new LeadSyncService($client))->sync($lead);

        $this->assertSame('partial', $log->status);
        $this->assertSame('Estate rejected', $log->error_message);
        $this->assertNull($log->external_estate_id);
    }

    private function makeLead(): Lead
    {
        $template = LandingPageTemplate::create([
            'name' => 'Test', 'key' => 'test', 'schema' => [], 'default_content' => [], 'is_active' => true,
        ]);
        $page = LandingPage::create([
            'landing_page_template_id' => $template->id,
            'slug' => 'bestandsimmobilie', 'title' => 'Bestand', 'content' => [], 'published_at' => now(),
        ]);
        $lead = Lead::create([
            'landing_page_id' => $page->id, 'first_name' => 'Ada', 'last_name' => 'Lovelace',
            'email' => 'ada@example.test', 'phone' => '+49123456789', 'source' => 'landingpage',
        ]);
        LeadProperty::create([
            'lead_id' => $lead->id, 'property_type' => 'Einfamilienhaus', 'street' => 'Hauptstraße',
            'house_number' => '12', 'zip' => '50667', 'city' => 'Köln', 'country' => 'DE',
            'living_area' => 140, 'rooms' => 5,
        ]);

        return $lead->fresh(['landingPage', 'property']);
    }
}
