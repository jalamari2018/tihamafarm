<?php

namespace App\Http\Requests;

use App\Support\DigitNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class UpdateHarvestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $readyDate = DigitNormalizer::toEnglishDigits($this->input('ready_date'));

        if ($this->input('ready_status') === 'now') {
            $this->merge([
                'phone' => DigitNormalizer::normalizeSaudiPhone($this->input('phone')),
                'ready_date' => null,
            ]);
            return;
        }

        $this->merge([
            'phone' => DigitNormalizer::normalizeSaudiPhone($this->input('phone')),
            'ready_date' => $readyDate,
        ]);
    }

    public function rules(): array
    {
        return [
            'harvest_name' => ['required', 'string', 'max:255'],
            'farmer_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'regex:/^05\d{8}$/'],
            'location_text' => ['required', 'string', 'max:255'],
            'ready_status' => ['required', 'in:now,future'],
            'ready_date' => ['nullable', 'required_if:ready_status,future', 'date', 'after_or_equal:tomorrow'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'harvest_name.required' => 'اسم المحصول مطلوب.',
            'farmer_name.required' => 'اسم المزارع مطلوب.',
            'phone.required' => 'رقم الهاتف مطلوب.',
            'phone.regex' => 'رقم الهاتف يجب أن يكون بصيغة سعودية صحيحة مثل 05XXXXXXXX.',
            'location_text.required' => 'الموقع النصي مطلوب.',
            'ready_status.required' => 'حالة الجاهزية مطلوبة.',
            'ready_status.in' => 'حالة الجاهزية غير صحيحة.',
            'ready_date.required_if' => 'تاريخ الجاهزية مطلوب عند اختيار جاهز لاحقًا.',
            'ready_date.date' => 'تاريخ الجاهزية يجب أن يكون تاريخًا صحيحًا.',
            'ready_date.after_or_equal' => 'تاريخ الجاهزية يجب أن يكون من الغد أو بعده.',
            'image.image' => 'الملف المرفوع يجب أن يكون صورة.',
        ];
    }
}
