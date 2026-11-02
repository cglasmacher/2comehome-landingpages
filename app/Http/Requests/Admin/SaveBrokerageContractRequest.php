<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveBrokerageContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(collect($this->only(['offer_price', 'commission_percent']))
            ->map(fn ($value) => is_string($value) ? str_replace(',', '.', $value) : $value)->all());
    }

    public function rules(): array
    {
        return [
            'agent_id' => ['required', Rule::exists('users', 'id')->where('is_admin', true)],
            'sellers' => ['required', 'array', 'min:1', 'max:10'],
            'sellers.*' => ['array:name,address,email'],
            'sellers.*.name' => ['required', 'string', 'max:255'],
            'sellers.*.address' => ['required', 'string', 'max:1000'],
            'sellers.*.email' => ['nullable', 'email', 'max:255'],
            'property_address' => ['required', 'string', 'max:1000'],
            'property_description' => ['nullable', 'string', 'max:255'],
            'offer_price' => ['nullable', 'numeric', 'decimal:0,2', 'min:0.01', 'max:999999999999.99'],
            'commission_percent' => ['required', 'numeric', 'decimal:0,2', 'between:0,100'],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'duration_months' => ['required', 'integer', 'between:1,120'],
            'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'notice_months' => ['required', 'integer', 'between:1,120'],
            'renewal_months' => ['required', 'integer', 'between:0,120'],
            'signing_place' => ['required', 'string', 'max:255'],
            'special_agreements' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
