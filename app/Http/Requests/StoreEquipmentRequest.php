<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['worker', 'admin', 'developer'], true);
    }

    public function rules(): array
    {
        return [
            'customer_mode' => ['required', 'in:registered,walk_in'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id', 'required_if:customer_mode,registered'],
            'type' => ['required', 'string', 'max:100'],
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:150'],
            'serial_number' => ['nullable', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required_if' => 'Selecciona un cliente registrado o usa la opción Cliente de Mostrador.',
        ];
    }
}
