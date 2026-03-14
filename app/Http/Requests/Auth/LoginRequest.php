<?php

namespace App\Http\Requests\Auth;

use App\Support\DigitNormalizer;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (! is_string($value) || ! $this->isEmailOrSaudiPhone($value)) {
                        $fail('حقل البريد الإلكتروني أو رقم الجوال يجب أن يكون بريدًا إلكترونيًا صحيحًا أو رقم جوال سعودي بصيغة 05XXXXXXXX.');
                    }
                },
            ],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt([
            'email' => $this->normalizedIdentifier(),
            'password' => (string) $this->input('password'),
        ], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }

    protected function prepareForValidation(): void
    {
        $identifier = (string) $this->input('email', '');

        $this->merge([
            'email' => $this->normalizeIdentifier($identifier),
        ]);
    }

    private function normalizeIdentifier(string $value): string
    {
        $normalizedPhone = DigitNormalizer::normalizeSaudiPhone($value);

        if (preg_match('/^05\d{8}$/', $normalizedPhone) === 1) {
            return $normalizedPhone ?? '';
        }

        return Str::lower(trim($value));
    }

    private function normalizedIdentifier(): string
    {
        return $this->normalizeIdentifier((string) $this->input('email', ''));
    }

    private function isEmailOrSaudiPhone(string $value): bool
    {
        $normalizedPhone = DigitNormalizer::normalizeSaudiPhone($value);

        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false
            || preg_match('/^05\d{8}$/', $normalizedPhone) === 1;
    }
}
