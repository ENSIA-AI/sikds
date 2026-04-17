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