<?php

namespace Tests\Feature;

use App\Models\LandingPage;
use App\Models\LandingPageTemplate;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminValuationLetterTest extends TestCase
{
    use RefreshDatabase;

    private function lead(): Lead
    {
        $template = LandingPageTemplate::create([
            'key' => 'letter-template',
            'name' => 'Letter test',
            'schema' => [],
        ]);
        $page = LandingPage::create([
            'landing_page_template_id' => $template->id,
            'slug' => 'letter-test',
            'title' => 'Letter test',
            'content' => [],
        ]);
        $lead = Lead::create([
            'landing_page_id' => $page->id,
            'first_name' => 'Erika',
            'last_name' => 'Mustermann',
            'email' => 'erika@example.test',
        ]);
        $lead->property()->create([
            'property_type' => 'wohnung',
            'street' => 'Teststraße',
            'house_number' => '12',
            'zip' => '40721',
            'city' => 'Hilden',
            'country' => 'DE',
            'living_area' => 72,
            'rooms' => 3,
        ]);
        $lead->valuation()->create([
            'provider' => 'manual',
            'estimated_value' => 162500,
            'range_low' => 160000,
            'range_high' => 165000,
            'range_percent' => 0,
            'status' => 'success',
        ]);

        return $lead;
    }

    private function letterData(): array
    {
        return [
            'recipient_name' => 'Frau und Herr Mustermann',
            'recipient_street' => 'Musterstraße 8',
            'recipient_zip' => '40723',
            'recipient_city' => 'Hilden',
            'adviser_name' => 'Maximilian Schäfer',
            'adviser_role' => 'Immobilienmakler',
            'adviser_mobile' => '+49 174 4973777',
            'adviser_email' => 'm.schaefer@2comehome.de',
            'issued_on' => '2026-11-24',
            'property_label' => 'Eigentumswohnung',
            'floor_location' => '1. Obergeschoss, rechts',
            'room_summary' => '3 Zimmer zzgl. Küche, Diele und Badezimmer',
            'object_description' => 'Eigentumswohnung mit 72 m². Lage und Ausstattung wurden überprüft.',
            'market_description' => 'Aus den vorhandenen Objektdaten ergibt sich folgende Preisspanne:',
            'value_low' => 160000,
            'value_high' => 165000,
            'orientation_value' => 162500,
            'valuation_notes' => 'Zustand, Ausstattung und Modernisierungen beeinflussen den Verkaufspreis.',
            'valuation_date' => '2026-11-01',
        ];
    }

    public function test_admin_can_save_letter_and_download_independent_pdf(): void
    {
        $lead = $this->lead();
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->get(route('admin.leads.letter.preview', $lead))->assertNotFound();
        $this->get(route('admin.leads.letter.edit', $lead))->assertOk();

        $this->post(route('admin.leads.letter.store', $lead), $this->letterData())
            ->assertRedirect(route('admin.leads.letter.edit', $lead));

        $this->assertDatabaseHas('valuation_letters', [
            'lead_id' => $lead->id,
            'recipient_name' => 'Frau und Herr Mustermann',
            'recipient_street' => 'Musterstraße 8',
            'recipient_zip' => '40723',
            'recipient_city' => 'Hilden',
            'property_label' => 'Eigentumswohnung',
            'value_low' => 160000,
            'value_high' => 165000,
        ]);

        $pdf = $this->get(route('admin.leads.letter.preview', $lead))->assertOk();
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());

        $download = $this->get(route('admin.leads.letter.download', $lead))->assertOk();
        $this->assertStringContainsString('attachment;', $download->headers->get('Content-Disposition'));

        // Letter generation must never modify the original lead valuation.
        $this->assertEquals(162500, $lead->valuation()->first()->estimated_value);
    }

    public function test_prices_must_be_in_order_and_orientation_inside_range(): void
    {
        $lead = $this->lead();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $data = $this->letterData();
        $data['value_high'] = 140000;
        $this->post(route('admin.leads.letter.store', $lead), $data)
            ->assertSessionHasErrors('value_high');

        $data = $this->letterData();
        $data['orientation_value'] = 190000;
        $this->post(route('admin.leads.letter.store', $lead), $data)
            ->assertSessionHasErrors('orientation_value');

        $this->assertDatabaseCount('valuation_letters', 0);
    }

    public function test_updating_or_removing_a_private_photo_preserves_slot_layout(): void
    {
        Storage::fake('local');
        $lead = $this->lead();
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->post(route('admin.leads.letter.store', $lead), [
            ...$this->letterData(),
            'photo_1' => UploadedFile::fake()->image('fassade.jpg', 800, 1200),
        ])->assertRedirect();

        $path = data_get($lead->valuationLetter()->first()->photo_paths, '1');
        $this->assertNotNull($path);
        Storage::disk('local')->assertExists($path);
        $this->get(route('admin.leads.letter.photo', [$lead, 1]))
            ->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->get(route('admin.leads.letter.photo', [$lead, 2]))->assertNotFound();

        $this->post(route('admin.leads.letter.store', $lead), [
            ...$this->letterData(),
            'remove_photo_1' => true,
        ])->assertRedirect();

        Storage::disk('local')->assertMissing($path);
        $this->get(route('admin.leads.letter.photo', [$lead, 1]))->assertNotFound();
    }

    public function test_pdf_must_be_reviewed_again_when_property_changes(): void
    {
        $lead = $this->lead();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->post(route('admin.leads.letter.store', $lead), $this->letterData())->assertRedirect();

        $this->travel(10)->seconds();
        $lead->property()->update(['living_area' => 75]);

        $this->get(route('admin.leads.letter.preview', $lead))->assertStatus(409);
        $this->post(route('admin.leads.letter.store', $lead), $this->letterData())->assertRedirect();
        $this->get(route('admin.leads.letter.preview', $lead))->assertOk();
    }

    public function test_letter_requires_complete_recipient_address_and_keeps_object_address_separate(): void
    {
        $lead = $this->lead();
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $missing = $this->letterData();
        unset($missing['recipient_zip']);
        $this->post(route('admin.leads.letter.store', $lead), $missing)
            ->assertSessionHasErrors('recipient_zip');

        $this->post(route('admin.leads.letter.store', $lead), $this->letterData())
            ->assertRedirect();

        $letter = $lead->valuationLetter()->firstOrFail();
        $html = view('pdf.valuation-letter', [
            'lead' => $lead->load('property'),
            'letter' => $letter,
            'brand' => [
                'logo' => public_path('images/logo-2comehome.png'),
                'watermark' => resource_path('valuation-letter/brand/watermark.svg'),
            ],
            'company' => config('valuation_letter'),
            'photos' => [],
            'pricePerSquareMeter' => null,
        ])->render();

        $this->assertStringContainsString('Musterstraße 8', $html);
        $this->assertStringContainsString('40723 Hilden', $html);
        $this->assertStringContainsString('Teststraße 12', $html);
        $this->assertFileExists(public_path('images/logo-2comehome.png'));

        // An old letter without these newly introduced fields cannot be issued again.
        $letter->update(['recipient_street' => null]);
        $this->get(route('admin.leads.letter.preview', $lead))->assertStatus(409);
        $this->get(route('admin.leads.letter.download', $lead))->assertStatus(409);
    }

    public function test_non_admin_cannot_read_or_write_valuation_letters(): void
    {
        $lead = $this->lead();
        $this->actingAs(User::factory()->create(['is_admin' => false]));
        $this->get(route('admin.leads.letter.edit', $lead))->assertForbidden();
        $this->post(route('admin.leads.letter.store', $lead), $this->letterData())->assertForbidden();
        $this->get(route('admin.leads.letter.download', $lead))->assertForbidden();
    }
}
