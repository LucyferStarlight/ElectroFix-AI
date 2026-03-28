<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSimilarCasesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'symptoms' => ['required', 'string', 'min:5', 'max:600'],
            'equipment_id' => ['nullable', 'integer'],
            'equipment_type' => ['nullable', 'string', 'max:120'],
            'customer_id' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:25'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $symptoms = $this->input('symptoms');
        $equipmentType = $this->input('equipment_type');

        $this->merge([
            'symptoms' => $symptoms !== null ? trim(strip_tags((string) $symptoms)) : null,
            'equipment_type' => $equipmentType !== null ? trim(strip_tags((string) $equipmentType)) : null,
        ]);
    }
}
