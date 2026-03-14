<?php

namespace App\Http\Requests;

use App\Support\DigitNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class StoreEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'product_name' => ['required', 'string', 'max:255'],
            'seller_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'regex:/^05\d{8}$/'],
            'location_text' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['required', 'image', 'max:4096'],
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
            'price' => DigitNormalizer::toEnglishDigits($this->input('price')),
        ]);
    }
}
