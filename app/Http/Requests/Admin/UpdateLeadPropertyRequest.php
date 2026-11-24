<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeadPropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    public function rules(): array
    {
        $types = collect(config('landingpages.property_types', []))
            ->filter(fn (array $type) => ($type['selectable'] ?? true) === true)
            ->keys()
            ->all();

        // Historical values such as Maisonette must stay editable on their own lead.
        $existingType = $this->route('lead')?->property?->property_type;
        if (is_string($existingType) && $existingType !== '') {
            $types[] = $existingType;
        }

        return [
            'property_type' => ['required', 'string', 'max:100', Rule::in(array_unique($types))],
            'street' => ['nullable', 'string', 'max:255'],
            'house_number' => ['nullable', 'string', 'max:30'],
            'zip' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['required', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
            'construction_year' => ['nullable', 'integer', 'min:1700', 'max:'.now()->year],
            'living_area' => ['nullable', 'integer', 'min:1', 'max:800000'],
            'plot_area' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'rooms' => ['nullable', 'integer', 'min:1', 'max:100'],
            'number_of_units' => ['required_if:property_type,mehrfamilienhaus', 'nullable', 'integer', 'min:2', 'max:1000'],
            'recalculate' => ['sometimes', 'boolean'],
        ];
    }
}
