<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'consultation_requested' => ['nullable', 'boolean'],

            'property.property_type' => ['nullable', 'string', 'max:100'],
            'property.street' => ['nullable', 'string', 'max:255'],
            'property.house_number' => ['nullable', 'string', 'max:30'],
            'property.zip' => ['nullable', 'string', 'max:20'],
            'property.city' => ['nullable', 'string', 'max:255'],
            'property.country' => ['nullable', 'string', 'size:2'],
            'property.construction_year' => ['nullable', 'integer', 'min:1700', 'max:' . now()->year],
            'property.living_area' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'property.plot_area' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'property.rooms' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'property.features' => ['nullable', 'array'],
        ];
    }
}
