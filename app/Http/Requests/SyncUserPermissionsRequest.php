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
            // Roles are optional — admin may save the user with no role at all.
            'role_ids' => ['nullable', 'array'],
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
            'role_ids.*.exists' => __('Un ou plusieurs rôles sélectionnés sont invalides.'),
            'permission_ids.*.exists' => __('Une ou plusieurs permissions sélectionnées sont invalides.'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'role_ids' => __('rôles'),
            'permission_ids' => __('permissions personnalisées'),
        ];
    }
}