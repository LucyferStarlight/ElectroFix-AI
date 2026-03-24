<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRepairOutcomeFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['worker', 'admin', 'developer'], true);
    }

    public function rules(): array
    {
        return [
            'diagnostic_accuracy' => ['required', Rule::in(['correct', 'partial', 'incorrect'])],
            'technician_notes' => ['nullable', 'string', 'max:1000'],
            'actual_causes' => ['nullable', 'array'],
            'actual_causes.*' => ['string', 'max:255'],
        ];
    }
}
