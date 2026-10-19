<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $selectablePropertyTypes = collect(config('landingpages.property_types', []))
            ->filter(fn (array $type) => ($type['selectable'] ?? true) === true)
            ->keys()
            ->all();

        return [
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'phone_contact_consent' => ['required', 'accepted'],
            'valuation_disclaimer_accepted' => ['required', 'accepted'],

            'property.property_type' => ['required', 'string', 'max:100', Rule::in($selectablePropertyTypes)],
            'property.street' => ['nullable', 'string', 'max:255'],
            'property.house_number' => ['nullable', 'string', 'max:30'],
            'property.zip' => ['nullable', 'string', 'max:20'],
            'property.city' => ['nullable', 'string', 'max:255'],
            'property.country' => ['nullable', 'string', 'size:2'],
            'property.construction_year' => ['nullable', 'integer', 'min:1700', 'max:' . now()->year],
            'property.living_area' => ['nullable', 'integer', 'min:1', 'max:800000'],
            'property.plot_area' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'property.rooms' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'property.number_of_units' => ['required_if:property.property_type,mehrfamilienhaus', 'nullable', 'integer', 'min:2', 'max:1000'],
            'property.features' => ['nullable', 'array'],
        ];
    }
}
