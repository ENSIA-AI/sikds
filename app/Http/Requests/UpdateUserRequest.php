<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('user.manage');
    }

    protected function prepareForValidation(): void
    {
        $fullName = $this->input('full_name');
        if (is_string($fullName)) {
            $this->merge([
                'full_name' => preg_replace('/\s+/', ' ', trim($fullName)),
            ]);
        }

        $email = $this->input('email');
        if (is_string($email)) {
            $this->merge([
                'email' => strtolower(trim($email)),
            ]);
        }

        if ($this->has('institution_id')) {
            $institutionId = $this->input('institution_id');
            $this->merge([
                'institution_id' => is_numeric($institutionId) ? (int) $institutionId : $institutionId,
            ]);
        }

        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => $this->boolean('is_active'),
            ]);
        }
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;
        
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'institution_id' => ['required', 'integer', 'exists:institutions,id'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => __('Le nom complet est requis.'),
            'email.required' => __('L\'email est requis.'),
            'email.email' => __('L\'email doit être valide.'),
            'email.unique' => __('Cet email est déjà utilisé.'),
            'institution_id.required' => __('L\'institution est requise.'),
            'institution_id.exists' => __('Institution invalide.'), 
        ];
    }
}