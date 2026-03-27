<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApproveOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['worker', 'admin', 'developer'], true);
    }

    public function rules(): array
    {
        return [
            'approved_by' => ['nullable', Rule::in(['customer', 'system'])],
            'approval_channel' => ['required', Rule::in(['whatsapp', 'system', 'verbal', 'phone', 'email', 'other'])],
        ];
    }
}
