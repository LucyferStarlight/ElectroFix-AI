<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        /** @var User $worker */
        $worker = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($worker?->id),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'can_access_billing' => ['nullable', 'boolean'],
            'can_access_inventory' => ['nullable', 'boolean'],
        ];
    }
}
