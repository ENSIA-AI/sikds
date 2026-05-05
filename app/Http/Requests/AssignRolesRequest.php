<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AssignRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('user.assign.permissions');
    }

    public function rules(): array
    {
        return [
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'role_ids.required' => 'Vous devez sélectionner au moins un rôle.',
            'role_ids.*.exists' => 'Rôle invalide sélectionné.',
        ];
    }
}