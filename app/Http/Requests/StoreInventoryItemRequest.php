<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['worker', 'admin', 'developer'], true)
            && $this->user()?->canAccessModule('inventory');
    }

    public function rules(): array
    {
        $companyId = $this->user()?->company_id;

        return [
            'name' => ['required', 'string', 'max:180'],
            'internal_code' => [
                'required',
                'string',
                'max:120',
                Rule::unique('inventory_items', 'internal_code')
                    ->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'is_sale_enabled' => ['nullable', 'boolean'],
            'sale_price' => ['nullable', 'required_if:is_sale_enabled,1', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }
}
