<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'admin.name' => ['required', 'string', 'max:180'],
            'admin.email' => ['required', 'email', 'max:180', 'unique:users,email'],
            'admin.password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],

            'company.name' => ['required', 'string', 'max:180'],
            'company.address_line' => ['required', 'string', 'max:255'],
            'company.city' => ['nullable', 'string', 'max:120'],
            'company.state' => ['nullable', 'string', 'max:120'],
            'company.country' => ['nullable', 'string', 'size:2'],
            'company.postal_code' => ['nullable', 'string', 'max:20'],
            'company.owner_phone' => ['nullable', 'string', 'max:30'],

            'workers_count' => ['required', 'integer', 'min:0', 'max:50'],
            'workers' => ['nullable', 'array'],
            'workers.*.name' => ['required_with:workers', 'string', 'max:180'],
            'workers.*.email' => ['required_with:workers', 'email', 'max:180', 'distinct', 'unique:users,email'],
            'workers.*.password' => ['nullable', 'string', 'min:8', 'max:100'],

            'worker_password_strategy' => ['required', Rule::in(['common', 'generated', 'manual'])],
            'common_worker_password' => ['nullable', 'string', 'min:8', 'max:100'],

            'subscription.plan' => ['required', Rule::in(['starter', 'pro', 'enterprise'])],
            'subscription.billing_period' => ['required', Rule::in(['monthly', 'semiannual', 'annual'])],
            'subscription.trial_enabled' => ['required', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $workersCount = (int) $this->input('workers_count', 0);
            $workers = $this->input('workers', []);

            if ($workersCount !== count($workers)) {
                $validator->errors()->add('workers_count', 'La cantidad de workers no coincide con los datos capturados.');
            }

            $strategy = $this->input('worker_password_strategy');
            $common = trim((string) $this->input('common_worker_password', ''));

            if ($strategy === 'common' && $workersCount > 0 && strlen($common) < 8) {
                $validator->errors()->add('common_worker_password', 'La contraseña común debe tener al menos 8 caracteres.');
            }

            if ($strategy === 'manual') {
                foreach ($workers as $index => $worker) {
                    $password = trim((string) ($worker['password'] ?? ''));
                    if ($password === '') {
                        $validator->errors()->add("workers.{$index}.password", 'Cada worker requiere contraseña manual.');
                    }
                }
            }
        });
    }
}
