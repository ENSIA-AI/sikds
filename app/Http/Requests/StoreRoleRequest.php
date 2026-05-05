<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreRoleRequest extends FormRequest
{
    
    // Determine if the user is authorized to make this request.
    public function authorize(): bool
    {
        return $this->user()->can('role.create');
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

    
    // Get the validation rules that apply to the request.
     
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'permission_ids' => ['required', 'array', 'min:1'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ];
    }

    
    // Get custom error messages.
     
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du rôle est requis.',
            'name.unique' => 'Ce nom de rôle existe déjà.',
            'permission_ids.required' => 'Vous devez sélectionner au moins une permission.',
            'permission_ids.*.exists' => 'Permission invalide sélectionnée.',
        ];
    }
}