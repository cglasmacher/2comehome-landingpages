<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class SaveValuationLetterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    public function rules(): array
    {
        $rules = [
            'recipient_name' => ['required', 'string', 'max:180'],
            'recipient_street' => ['required', 'string', 'max:255'],
            'recipient_zip' => ['required', 'string', 'max:20'],
            'recipient_city' => ['required', 'string', 'max:255'],
            'adviser_name' => ['required', 'string', 'max:120'],
            'adviser_role' => ['nullable', 'string', 'max:80'],
            'adviser_mobile' => ['nullable', 'string', 'max:60'],
            'adviser_email' => ['nullable', 'email', 'max:180'],
            'issued_on' => ['required', 'date_format:Y-m-d'],
            'property_label' => ['required', 'string', 'max:100'],
            'floor_location' => ['nullable', 'string', 'max:120'],
            'room_summary' => ['nullable', 'string', 'max:170'],
            // Restrict length to preserve the two-page reference layout.
            'object_description' => ['required', 'string', 'max:530'],
            'market_description' => ['required', 'string', 'max:310'],
            'value_low' => ['required', 'numeric', 'min:1', 'max:9999999999.99'],
            'value_high' => ['required', 'numeric', 'min:1', 'max:9999999999.99', 'gte:value_low'],
            'orientation_value' => ['required', 'numeric', 'min:1', 'max:9999999999.99', 'gte:value_low', 'lte:value_high'],
            'valuation_notes' => ['required', 'string', 'max:450'],
            'valuation_date' => ['required', 'date_format:Y-m-d'],
        ];

        for ($slot = 1; $slot <= 4; $slot++) {
            $rules['photo_'.$slot] = ['nullable', File::image()->types(['jpg', 'jpeg', 'png'])->max(8 * 1024)];
            $rules['remove_photo_'.$slot] = ['sometimes', 'boolean'];
        }

        return $rules;
    }
}
