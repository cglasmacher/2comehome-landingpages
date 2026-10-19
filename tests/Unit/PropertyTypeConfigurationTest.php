<?php

namespace Tests\Unit;

use Tests\TestCase;

class PropertyTypeConfigurationTest extends TestCase
{
    public function test_public_property_types_replace_maisonette_with_multifamily_house(): void
    {
        $types = collect(config('landingpages.property_types', []));
        $selectable = $types->filter(fn (array $type) => ($type['selectable'] ?? true) === true);

        $this->assertFalse($selectable->has('maisonette'));
        $this->assertTrue($selectable->has('mehrfamilienhaus'));
        $this->assertSame('multi_family_house', data_get($selectable->get('mehrfamilienhaus'), 'pricehubble.code'));
        $this->assertSame('haus', data_get($selectable->get('mehrfamilienhaus'), 'onoffice.objektart'));
        $this->assertSame('hausbau_mehrfamilienhaus', data_get($selectable->get('mehrfamilienhaus'), 'onoffice.objekttyp'));
    }

    public function test_maisonette_remains_legacy_only_for_existing_records(): void
    {
        $maisonette = config('landingpages.property_types.maisonette');

        $this->assertIsArray($maisonette);
        $this->assertFalse($maisonette['selectable']);
        $this->assertSame('maisonette', data_get($maisonette, 'onoffice.objekttyp'));
    }
}
