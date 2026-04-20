<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SyncUserPermissionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * Requires BOTH permissions:
     * - user.manage: to modify user details
     * - user.assign.permissions: to assign roles and permissions
     */
    public function authorize(): bool
    {
        return $this->user()->can('user.manage') 
            && $this->user()->can('user.assign.permissions');
    }

    /**
     * Get the validation rules.
     */
    public function rules(): array
    {
        return [
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'role_ids.required' => 'Au moins un rôle doit être assigné.',
            'role_ids.min' => 'Au moins un rôle doit être assigné.',
            'role_ids.*.exists' => 'Un ou plusieurs rôles sélectionnés sont invalides.',
            'permission_ids.*.exists' => 'Une ou plusieurs permissions sélectionnées sont invalides.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'role_ids' => 'rôles',
            'permission_ids' => 'permissions personnalisées',
        ];
    }
}