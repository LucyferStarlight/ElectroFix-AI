<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignEquipmentCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['worker', 'admin', 'developer'], true);
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
        ];
    }
}
