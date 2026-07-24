<?php

namespace Tests\Unit;

use App\Http\Requests\StoreLeadRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreLeadRequestTest extends TestCase
{
    public function test_email_is_required(): void
    {
        $validator = Validator::make(
            [],
            (new StoreLeadRequest())->rules(),
        );

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_phone_is_required(): void
    {
        $validator = Validator::make(
            [],
            (new StoreLeadRequest())->rules(),
        );

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('phone', $validator->errors()->toArray());
    }

    public function test_phone_contact_consent_is_required(): void
    {
        $validator = Validator::make(
            ['valuation_disclaimer_accepted' => true],
            (new StoreLeadRequest())->rules(),
        );

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('phone_contact_consent', $validator->errors()->toArray());
    }

    public function test_valuation_disclaimer_is_required(): void
    {
        $validator = Validator::make(
            ['phone_contact_consent' => true],
            (new StoreLeadRequest())->rules(),
        );

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('valuation_disclaimer_accepted', $validator->errors()->toArray());
    }

    public function test_both_consents_must_be_accepted(): void
    {
        $validator = Validator::make(
            [
                'phone_contact_consent' => false,
                'valuation_disclaimer_accepted' => false,
            ],
            (new StoreLeadRequest())->rules(),
        );

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('phone_contact_consent', $validator->errors()->toArray());
        $this->assertArrayHasKey('valuation_disclaimer_accepted', $validator->errors()->toArray());
    }
}
