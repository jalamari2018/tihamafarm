<?php

namespace App\Http\Requests;

use App\Support\DigitNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFarmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'farm_name' => ['required', 'string', 'max:255'],
            'farmer_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'regex:/^05\d{8}$/'],
            'location_text' => ['required', 'string', 'max:255'],
            'length' => ['required', 'numeric', 'min:0.1'],
            'width' => ['required', 'numeric', 'min:0.1'],
            'has_well' => ['nullable', 'boolean'],
            'has_electricity' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'رقم الهاتف يجب أن يكون بصيغة سعودية صحيحة مثل 05XXXXXXXX.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => DigitNormalizer::normalizeSaudiPhone($this->input('phone')),
            'length' => DigitNormalizer::toEnglishDigits($this->input('length')),
            'width' => DigitNormalizer::toEnglishDigits($this->input('width')),
        ]);
    }
}
