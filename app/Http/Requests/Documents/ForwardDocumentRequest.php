<?php

declare(strict_types=1);

namespace App\Http\Requests\Documents;

use Illuminate\Foundation\Http\FormRequest;

class ForwardDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'recipient_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'recipient_id.required' => 'Veuillez sélectionner un destinataire.',
            'recipient_id.integer' => 'Identifiant de destinataire invalide.',
            'recipient_id.exists' => 'Le destinataire sélectionné est introuvable.',
        ];
    }
}
