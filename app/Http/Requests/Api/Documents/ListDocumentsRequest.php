<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Documents;

use Illuminate\Foundation\Http\FormRequest;

class ListDocumentsRequest extends FormRequest
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
            'q' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', 'in:draft,active,archived,soft_deleted'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'tag_id' => ['nullable', 'integer', 'exists:tags,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'include_deleted' => ['nullable', 'boolean'],
            'sort_by' => ['nullable', 'in:issue_date,created_at,title,reference_number,status'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
        ];
    }
}

