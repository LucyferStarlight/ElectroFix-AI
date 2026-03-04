<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'plan' => ['required', Rule::in(['starter', 'pro', 'enterprise'])],
            'billing_period' => ['required', Rule::in(['monthly', 'semiannual', 'annual'])],
        ];
    }
}
