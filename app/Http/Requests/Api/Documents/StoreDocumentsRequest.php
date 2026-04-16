<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Documents;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreDocumentsRequest extends FormRequest
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
            'files' => ['required', 'array', 'min:1', 'max:5'],
            'files.*' => ['required', 'file', 'mimes:pdf', 'max:51200'],
            'documents_meta' => ['required', 'array', 'min:1', 'max:5'],
            'documents_meta.*.title' => ['required', 'string', 'max:200'],
            'documents_meta.*.description' => ['nullable', 'string'],
            'documents_meta.*.issue_date' => ['required', 'date'],
            'documents_meta.*.effective_date' => ['nullable', 'date'],
            'documents_meta.*.expiration_date' => ['nullable', 'date'],
            'documents_meta.*.target_audience' => ['required', 'in:all,specific_institutions,specific_roles'],
            'documents_meta.*.target_institution_ids' => ['nullable', 'array'],
            'documents_meta.*.target_institution_ids.*' => ['integer', 'exists:institutions,id'],
            'documents_meta.*.target_role_ids' => ['nullable', 'array'],
            'documents_meta.*.target_role_ids.*' => ['integer', 'exists:roles,id'],
            'documents_meta.*.target_user_ids' => ['nullable', 'array'],
            'documents_meta.*.target_user_ids.*' => ['integer', 'exists:users,id'],
            'documents_meta.*.tag_ids' => ['required', 'array', 'min:1'],
            'documents_meta.*.tag_ids.*' => ['integer', 'exists:tags,id'],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var array<int, mixed> $files */
            $files = (array) $this->file('files', []);
            /** @var array<int, array<string, mixed>> $metaList */
            $metaList = (array) $this->input('documents_meta', []);

            if (count($files) !== count($metaList)) {
                $validator->errors()->add('documents_meta', 'Chaque fichier doit avoir ses métadonnées correspondantes.');
            }

            foreach ($metaList as $idx => $meta) {
                $label = 'documents_meta.'.($idx + 1);
                $audience = (string) ($meta['target_audience'] ?? '');

                if ($audience === 'specific_institutions' && empty($meta['target_institution_ids'])) {
                    $validator->errors()->add($label, 'Institutions cibles requises pour ce document.');
                }
                if ($audience === 'specific_roles' && empty($meta['target_role_ids'])) {
                    $validator->errors()->add($label, 'Rôles cibles requis pour ce document.');
                }

                $issue = isset($meta['issue_date']) ? strtotime((string) $meta['issue_date']) : false;
                $effective = isset($meta['effective_date']) && $meta['effective_date'] !== null
                    ? strtotime((string) $meta['effective_date'])
                    : false;
                $expiration = isset($meta['expiration_date']) && $meta['expiration_date'] !== null
                    ? strtotime((string) $meta['expiration_date'])
                    : false;

                if ($issue !== false && $effective !== false && $effective < $issue) {
                    $validator->errors()->add($label, 'effective_date doit être >= issue_date.');
                }
                if ($issue !== false && $expiration !== false && $expiration <= $issue) {
                    $validator->errors()->add($label, 'expiration_date doit être > issue_date.');
                }
            }

        });
    }
}

