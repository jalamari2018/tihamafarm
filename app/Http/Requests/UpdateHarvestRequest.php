<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHarvestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('ready_status') === 'now') {
            $this->merge(['ready_date' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'harvest_name' => ['required', 'string', 'max:255'],
            'farmer_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'location_text' => ['required', 'string', 'max:255'],
            'ready_status' => ['required', 'in:now,future'],
            'ready_date' => ['nullable', 'required_if:ready_status,future', 'date', 'after_or_equal:tomorrow'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:4096'],
        ];
    }
}
