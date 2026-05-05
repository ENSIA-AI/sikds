<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Documents;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:200'],
            'description' => ['sometimes', 'nullable', 'string'],
            'issue_date' => ['sometimes', 'required', 'date'],
            'effective_date' => ['sometimes', 'nullable', 'date'],
            'expiration_date' => ['sometimes', 'nullable', 'date'],
            'target_audience' => ['sometimes', 'required', 'in:all,specific_institutions,specific_roles,specific_users'],
            'target_institution_ids' => ['nullable', 'array'],
            'target_institution_ids.*' => ['integer', 'exists:institutions,id'],
            'target_role_ids' => ['nullable', 'array'],
            'target_role_ids.*' => ['integer', 'exists:roles,id'],
            'target_user_ids' => ['nullable', 'array'],
            'target_user_ids.*' => ['integer', 'exists:users,id'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'file' => ['nullable', 'file', 'mimes:pdf', 'max:51200'],
            'change_summary' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $audience = (string) $this->input('target_audience', '');
            if ($audience === 'specific_institutions' && empty($this->input('target_institution_ids', []))) {
                $validator->errors()->add('target_institution_ids', 'Institutions cibles requises.');
            }
            if ($audience === 'specific_roles' && empty($this->input('target_role_ids', []))) {
                $validator->errors()->add('target_role_ids', 'Rôles cibles requis.');
            }
            if ($audience === 'specific_users' && empty($this->input('target_user_ids', []))) {
                $validator->errors()->add('target_user_ids', 'Utilisateurs cibles requis.');
            }

            $issue = $this->filled('issue_date') ? strtotime((string) $this->input('issue_date')) : false;
            $effective = $this->filled('effective_date') ? strtotime((string) $this->input('effective_date')) : false;
            $expiration = $this->filled('expiration_date') ? strtotime((string) $this->input('expiration_date')) : false;
            if ($issue !== false && $effective !== false && $effective < $issue) {
                $validator->errors()->add('effective_date', 'effective_date doit être >= issue_date.');
            }
            if ($issue !== false && $expiration !== false && $expiration <= $issue) {
                $validator->errors()->add('expiration_date', 'expiration_date doit être > issue_date.');
            }

        });
    }
}

