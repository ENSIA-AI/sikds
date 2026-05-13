<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('role.edit');
    }

    protected function prepareForValidation(): void
    {
        $name = $this->input('name');
        if (is_string($name)) {
            $this->merge([
                'name' => preg_replace('/\s+/', ' ', trim($name)),
            ]);
        }

        $description = $this->input('description');
        if (is_string($description)) {
            $description = trim($description);
            $this->merge([
                'description' => $description === '' ? null : $description,
            ]);
        }

        if (is_array($this->input('permission_ids'))) {
            $this->merge([
                'permission_ids' => array_values(array_unique(array_map('intval', $this->input('permission_ids')))),
            ]);
        }
    }

    public function rules(): array
    {
        $roleId = $this->route('role')->id;
        
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($roleId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'permission_ids' => ['required', 'array', 'min:1'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('Le nom du rôle est requis.'),
            'name.unique' => __('Ce nom de rôle existe déjà.'),
            'permission_ids.required' => __('Vous devez sélectionner au moins une permission.'),
        ];
    }
}