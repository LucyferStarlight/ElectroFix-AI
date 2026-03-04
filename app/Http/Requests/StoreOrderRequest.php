<?php

namespace App\Http\Requests;

use App\Support\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['worker', 'admin', 'developer'], true);
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'equipment_id' => ['required', 'integer', 'exists:equipments,id'],
            'technician' => ['nullable', 'string', 'max:255'],
            'technician_profile_id' => ['nullable', 'integer', 'exists:technician_profiles,id'],
            'request_ai_diagnosis' => ['nullable', 'boolean'],
            'symptoms' => ['nullable', 'string', 'min:5', 'max:600', 'required_if:request_ai_diagnosis,1'],
            'status' => ['nullable', Rule::in(OrderStatus::all())],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
