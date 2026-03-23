<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBillingDocumentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $source = $this->input('source');
        $items = $this->input('items', []);

        if (! is_array($items)) {
            return;
        }

        foreach ($items as $index => $item) {
            if (! is_array($item) || ! empty($item['item_kind'])) {
                continue;
            }

            if ($source === 'sale') {
                $items[$index]['item_kind'] = 'product';
            }

            if ($source === 'repair') {
                $items[$index]['item_kind'] = 'service';
            }
        }

        $this->merge(['items' => $items]);
    }

    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['worker', 'admin', 'developer'], true)
            && $this->user()?->canAccessModule('billing');
    }

    public function rules(): array
    {
        $requiresRepairOutcome = fn (): bool => in_array($this->input('source'), ['repair', 'mixed'], true);

        return [
            'document_type' => ['required', 'in:quote,invoice'],
            'source' => ['required', 'in:repair,sale,mixed'],
            'customer_mode' => ['required', 'in:registered,walk_in'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id', 'required_if:customer_mode,registered'],
            'walk_in_name' => ['nullable', 'string', 'max:180', 'required_if:customer_mode,walk_in'],
            'tax_mode' => ['required', 'in:included,excluded'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_kind' => ['required', 'in:service,product'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01', 'max:999999'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'items.*.inventory_item_id' => ['nullable', 'integer', 'exists:inventory_items,id'],
            'items.*.order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'repair_outcome' => [
                Rule::requiredIf($requiresRepairOutcome),
                'nullable',
                Rule::in(['repaired', 'partial', 'not_repaired']),
            ],
            'outcome_notes' => ['nullable', 'string', 'max:1000', 'required_if:repair_outcome,partial'],
            'work_performed' => [Rule::requiredIf($requiresRepairOutcome), 'nullable', 'string', 'max:800'],
            'actual_amount_charged' => [Rule::requiredIf($requiresRepairOutcome), 'nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }
}
