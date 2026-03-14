<?php

namespace App\Http\Requests;

use App\Support\DigitNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class StoreFarmRequest extends FormRequest
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
            'image' => ['required', 'image', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'farm_name.required' => 'اسم المزرعة مطلوب.',
            'farmer_name.required' => 'اسم المزارع مطلوب.',
            'phone.required' => 'رقم التواصل لهذا الإعلان مطلوب.',
            'phone.regex' => 'رقم الهاتف يجب أن يكون بصيغة سعودية صحيحة مثل 05XXXXXXXX.',
            'location_text.required' => 'الموقع النصي مطلوب.',
            'length.required' => 'طول المزرعة بالمتر مطلوب.',
            'length.numeric' => 'طول المزرعة يجب أن يكون رقمًا.',
            'length.min' => 'طول المزرعة يجب أن يكون أكبر من صفر.',
            'width.required' => 'عرض المزرعة بالمتر مطلوب.',
            'width.numeric' => 'عرض المزرعة يجب أن يكون رقمًا.',
            'width.min' => 'عرض المزرعة يجب أن يكون أكبر من صفر.',
            'image.required' => 'صورة المزرعة مطلوبة.',
            'image.image' => 'الملف المرفوع يجب أن يكون صورة.',
            'image.max' => 'حجم الصورة يجب ألا يتجاوز 4 ميجابايت.',
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
