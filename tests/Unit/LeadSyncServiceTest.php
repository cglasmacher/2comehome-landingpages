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
        $lead->update(['notes' => "Bitte abends anrufen.\n<script>alert(1)</script>"]);
        $lead->valuation()->create([
            'estimated_value' => 500000, 'range_low' => 450000, 'range_high' => 550000,
            'range_percent' => 10, 'status' => 'completed',
        ]);
        $client = Mockery::mock(OnOfficeClient::class);
        $client->shouldReceive('findLatestContactByEmail')->once()->with('ada@example.test')->andReturn(['status' => 'success', 'external_contact_id' => null, 'contact_types' => [], 'matches' => [], 'raw' => []]);
        $client->shouldReceive('createContactWithRemark')->once()->andReturn([
            'status' => 'success', 'external_contact_id' => 'contact-7', 'raw' => [],
        ]);
        $client->shouldReceive('createEstate')->once()->withArgs(function (array $property, string $note): bool {
            return $property['city'] === 'Köln' && $note === 'Landingpage Lead von bestandsimmobilie'
                && str_contains($property['description'], 'Bitte abends anrufen.')
                && str_contains($property['description'], '&lt;script&gt;')
                && ! str_contains($property['description'], '<script>')
                && str_contains($property['description'], 'Minimum: 450.000,00 EUR')
                && str_contains($property['description'], 'Maximum: 550.000,00 EUR')
                && $property['valuation'] === ['estimated_value' => 500000.0, 'range_low' => 450000.0, 'range_high' => 550000.0];
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
        $client->shouldReceive('findLatestContactByEmail')->once()->with('ada@example.test')->andReturn(['status' => 'success', 'external_contact_id' => null, 'contact_types' => [], 'matches' => [], 'raw' => []]);
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

    public function test_it_reuses_the_highest_existing_contact_and_adds_status_warning(): void
    {
        $lead = $this->makeLead();
        $client = Mockery::mock(OnOfficeClient::class);
        $client->shouldReceive('findLatestContactByEmail')->once()->with('ada@example.test')->andReturn([
            'status' => 'success', 'external_contact_id' => '4711',
            'contact_types' => ['Interessent'], 'matches' => ['4711', '3987', '1822'], 'raw' => [],
        ]);
        $client->shouldReceive('createContactWithRemark')->never();
        $client->shouldReceive('createEstate')->once()->withArgs(function (array $property, string $note): bool {
            return str_contains($note, 'Vorhandener Kontakt aus Datenbank')
                && str_contains($note, 'ACHTUNG: Aktuell als Interessent geführt');
        })->andReturn(['status' => 'success', 'external_estate_id' => 'estate-42', 'raw' => []]);
        $client->shouldReceive('createOwnerRelation')->once()->with('estate-42', '4711')->andReturn([
            'status' => 'success', 'raw' => [],
        ]);

        $log = (new LeadSyncService($client))->sync($lead);

        $this->assertSame('success', $log->status);
        $this->assertSame('4711', $log->external_contact_id);
        $this->assertStringContainsString('Vorhandener Kontakt aus Datenbank', data_get($log->request_payload, 'estate.internal_note'));
        $this->assertStringContainsString('ACHTUNG: Aktuell als Interessent geführt', data_get($log->request_payload, 'estate.internal_note'));
    }

    public function test_failed_valuation_keeps_message_without_sending_zero_prices(): void
    {
        $lead = $this->makeLead();
        $lead->update(['notes' => 'Bitte melden.']);
        $lead->valuation()->create(['range_percent' => 10, 'status' => 'failed', 'estimated_value' => 500000]);
        $lead->load('valuation');
        $service = new LeadSyncService(app(OnOfficeClient::class));
        $method = new \ReflectionMethod($service, 'buildPropertyPayload');
        $payload = $method->invoke($service, $lead);
        $this->assertSame([], $payload['valuation']);
        $this->assertSame("Nachricht des Interessenten:\nBitte melden.", $payload['description']);
    }

    public function test_demo_values_are_labelled_and_not_sent_to_numeric_pricehubble_fields(): void
    {
        $lead = $this->makeLead();
        $lead->valuation()->create([
            'range_percent' => 10, 'status' => 'completed', 'estimated_value' => 500000,
            'provider_response' => ['confidence' => 'demo'],
        ]);
        $lead->load('valuation');
        $service = new LeadSyncService(app(OnOfficeClient::class));
        $payload = (new \ReflectionMethod($service, 'buildPropertyPayload'))->invoke($service, $lead);
        $this->assertSame([], $payload['valuation']);
        $this->assertStringContainsString('Demo-Schätzung', $payload['description']);
    }

    public function test_somantic_and_formula_values_keep_their_source_without_writing_pricehubble_fields(): void
    {
        $lead = $this->makeLead();
        foreach (['somantic' => 'Somantic', 'formula' => 'Formelbasierte Orientierung'] as $provider => $label) {
            $lead->valuation()->updateOrCreate([], [
                'provider' => $provider, 'range_percent' => 10, 'status' => 'completed',
                'estimated_value' => 500000, 'range_low' => 450000, 'range_high' => 550000,
            ]);
            $lead->load('valuation');
            $service = new LeadSyncService(app(OnOfficeClient::class));
            $payload = (new \ReflectionMethod($service, 'buildPropertyPayload'))->invoke($service, $lead);
            $this->assertSame([], $payload['valuation']);
            $this->assertStringContainsString('Bewertungsquelle: '.$label, $payload['description']);
            $this->assertStringContainsString('Minimum: 450.000,00 EUR', $payload['description']);
        }
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
