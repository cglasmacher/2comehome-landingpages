<?php

namespace Tests\Feature;

use App\DTO\ValuationResult;
use App\Models\LandingPage;
use App\Models\LandingPageTemplate;
use App\Models\Lead;
use App\Models\User;
use App\Services\Valuation\ValuationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLeadPropertyEditingTest extends TestCase
{
    use RefreshDatabase;

    private function lead(): Lead
    {
        $template = LandingPageTemplate::create([
            'key' => 'test-admin-property',
            'name' => 'Test',
            'schema' => [],
        ]);
        $page = LandingPage::create([
            'landing_page_template_id' => $template->id,
            'slug' => 'property-edit-test',
            'title' => 'Property edit test',
            'content' => [],
            'valuation_range_percent' => 7.5,
        ]);
        $lead = Lead::create([
            'landing_page_id' => $page->id,
            'first_name' => 'Erika',
            'email' => 'erika@example.test',
        ]);
        $lead->property()->create([
            'property_type' => 'einfamilienhaus',
            'street' => 'Alte Straße',
            'house_number' => '1',
            'zip' => '40721',
            'city' => 'Hilden',
            'country' => 'DE',
            'construction_year' => 1998,
            'living_area' => 120,
            'plot_area' => 450,
            'rooms' => 4,
        ]);
        $lead->valuation()->create([
            'provider' => 'manual',
            'status' => 'success',
            'estimated_value' => 500000,
            'range_low' => 460000,
            'range_high' => 540000,
            'range_percent' => 0,
        ]);

        return $lead;
    }

    private function propertyData(array $overrides = []): array
    {
        return array_replace([
            'property_type' => 'einfamilienhaus',
            'street' => 'Alte Straße',
            'house_number' => '1',
            'zip' => '40721',
            'city' => 'Hilden',
            'country' => 'DE',
            'construction_year' => 1998,
            'living_area' => 120,
            'plot_area' => 450,
            'rooms' => 4,
            'number_of_units' => null,
            'recalculate' => false,
        ], $overrides);
    }

    public function test_admin_can_correct_property_and_marks_old_valuation_as_stale(): void
    {
        $lead = $this->lead();
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->put(route('admin.leads.property.update', $lead), $this->propertyData([
            'property_type' => 'wohnung',
            'street' => 'Neue Straße',
            'living_area' => 90,
            'plot_area' => 800, // excluded for apartments even if submitted
            'number_of_units' => 12,
        ]))->assertRedirect();

        $this->assertDatabaseHas('lead_properties', [
            'lead_id' => $lead->id,
            'property_type' => 'wohnung',
            'street' => 'Neue Straße',
            'living_area' => 90,
            'plot_area' => null,
            'number_of_units' => null,
        ]);
        $this->assertSame('stale', $lead->valuation()->first()->status);
        $this->assertEquals(500000, $lead->valuation()->first()->estimated_value);
    }

    public function test_unchanged_property_does_not_invalidate_manual_valuation(): void
    {
        $lead = $this->lead();
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->put(route('admin.leads.property.update', $lead), $this->propertyData())->assertRedirect();

        $this->assertSame('success', $lead->valuation()->first()->status);
    }

    public function test_multifamily_house_requires_number_of_units(): void
    {
        $lead = $this->lead();
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->put(route('admin.leads.property.update', $lead), $this->propertyData([
            'property_type' => 'mehrfamilienhaus',
            'number_of_units' => null,
        ]))->assertSessionHasErrors('number_of_units');

        $this->assertSame('einfamilienhaus', $lead->property()->first()->property_type);
    }

    public function test_explicit_recalculation_replaces_old_values(): void
    {
        $lead = $this->lead();
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->mock(ValuationService::class)
            ->shouldReceive('createForLead')
            ->once()
            ->andReturn(new ValuationResult(
                estimatedValue: 620000,
                rangePercent: 7.5,
                rangeLow: 590000,
                rangeHigh: 650000,
                provider: 'formula',
            ));

        $this->put(route('admin.leads.property.update', $lead), $this->propertyData([
            'living_area' => 140,
            'recalculate' => true,
        ]))->assertRedirect();

        $valuation = $lead->valuation()->first();
        $this->assertSame('completed', $valuation->status);
        $this->assertSame('formula', $valuation->provider);
        $this->assertEquals(620000, $valuation->estimated_value);
    }

    public function test_non_admin_cannot_update_property(): void
    {
        $lead = $this->lead();
        $this->actingAs(User::factory()->create(['is_admin' => false]));

        $this->put(route('admin.leads.property.update', $lead), $this->propertyData([
            'living_area' => 140,
        ]))->assertForbidden();

        $this->assertSame(120, $lead->property()->first()->living_area);
    }
}
