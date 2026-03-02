<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'plan' => ['required', Rule::in(['starter', 'pro', 'enterprise', 'developer_test'])],
            'status' => ['required', Rule::in(['active', 'trial', 'past_due', 'canceled', 'suspended'])],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'billing_cycle' => ['required', Rule::in(['monthly', 'yearly'])],
            'user_limit' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ];
    }
}
