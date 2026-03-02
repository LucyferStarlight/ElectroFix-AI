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
            'technician' => ['required', 'string', 'max:255'],
            'symptoms' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(OrderStatus::all())],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
