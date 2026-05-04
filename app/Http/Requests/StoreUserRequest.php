<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Creating a user only requires user.manage. Roles/permissions are
        // optional at creation and gated separately by user.assign.permissions
        // when the admin chooses to assign them in the same form.
        return $this->user()->can('user.manage');
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'institution_id' => [
                'required',
                'integer',
                Rule::exists('institutions', 'id')->where('is_active', true),
            ],
            // Roles are OPTIONAL at user creation — admins can assign them later
            // via the Modify modal / edit-permissions screen.
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
            
            // Custom permissions (optional)
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
            
            'auth_type' => ['required', Rule::in(['sso', 'local'])],
            'password' => [
                'required_if:auth_type,local',
                'nullable',
                'string',
                'min:12',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
            ],
            'password_confirmation' => ['required_with:password', 'same:password'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Le nom complet est requis.',
            'email.required' => 'L\'email est requis.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'institution_id.required' => 'L\'institution est requise.',
            'institution_id.exists' => 'L\'institution sélectionnée n\'existe pas ou est inactive.',
            'permission_ids.*.exists' => 'Une ou plusieurs permissions sont invalides.',
            'password.required_if' => 'Le mot de passe est requis pour l\'authentification locale.',
            'password.min' => 'Le mot de passe doit contenir au moins 12 caractères.',
            'password.regex' => 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial.',
        ];
    }
}