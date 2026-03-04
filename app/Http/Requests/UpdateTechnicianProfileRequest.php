<?php

namespace App\Http\Requests;

use App\Support\TechnicianStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTechnicianProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['admin', 'developer'], true);
    }

    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:180'],
            'specialties' => ['nullable', 'array'],
            'specialties.*' => ['string', 'max:80'],
            'status' => ['required', Rule::in(TechnicianStatus::all())],
            'max_concurrent_orders' => ['required', 'integer', 'min:1', 'max:100'],
            'hourly_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'is_assignable' => ['nullable', 'boolean'],
        ];
    }
}

