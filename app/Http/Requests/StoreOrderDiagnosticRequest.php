<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderDiagnosticRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'symptoms' => ['required', 'string', 'min:5', 'max:600'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $symptoms = $this->input('symptoms');

        $this->merge([
            'symptoms' => $symptoms !== null
                ? trim(strip_tags((string) $symptoms))
                : null,
        ]);
    }
}
