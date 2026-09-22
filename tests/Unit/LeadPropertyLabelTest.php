<?php

namespace Tests\Unit;

use App\Models\LeadProperty;
use Tests\TestCase;

class LeadPropertyLabelTest extends TestCase
{
    public function test_it_returns_the_central_property_type_label(): void
    {
        $property = new LeadProperty(['property_type' => 'einfamilienhaus']);

        $this->assertSame('Einfamilienhaus', $property->property_type_label);
    }

    public function test_it_falls_back_for_unknown_property_types(): void
    {
        $property = new LeadProperty(['property_type' => 'sonderobjekt']);

        $this->assertSame('Sonderobjekt', $property->property_type_label);
    }
}
