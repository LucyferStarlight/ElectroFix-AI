<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustInventoryStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['worker', 'admin', 'developer'], true)
            && $this->user()?->canAccessModule('inventory');
    }

    public function rules(): array
    {
        return [
            'movement_type' => ['required', 'in:addition,removal'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
