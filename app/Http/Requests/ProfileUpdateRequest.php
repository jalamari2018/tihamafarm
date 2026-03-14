<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\DigitNormalizer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $identifier = (string) $value;
                    $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL) !== false;
                    $isSaudiPhone = preg_match('/^05\d{8}$/', $identifier) === 1;

                    if (! $isEmail && ! $isSaudiPhone) {
                        $fail('أدخل بريدًا إلكترونيًا صحيحًا أو رقم جوال سعودي بصيغة 05XXXXXXXX.');
                    }
                },
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'avatar' => ['nullable', 'image', 'max:4096'],
            'remove_avatar' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'الاسم مطلوب.',
            'email.required' => 'البريد الإلكتروني أو رقم الجوال مطلوب.',
            'email.unique' => 'البريد الإلكتروني أو رقم الجوال مستخدم مسبقًا.',
            'avatar.image' => 'الملف المرفوع يجب أن يكون صورة.',
            'avatar.max' => 'حجم الصورة يجب ألا يتجاوز 4 ميجابايت.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $identifier = trim((string) $this->input('email', ''));
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL) !== false;

        $normalized = $isEmail
            ? mb_strtolower($identifier)
            : DigitNormalizer::normalizeSaudiPhone($identifier);

        $this->merge([
            'email' => $normalized,
            'remove_avatar' => $this->boolean('remove_avatar'),
        ]);
    }
}
