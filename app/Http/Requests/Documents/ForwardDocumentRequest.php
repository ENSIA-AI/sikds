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
            'recipient_id.required' => __('Veuillez sélectionner un destinataire.'),
            'recipient_id.integer' => __('Identifiant de destinataire invalide.'),
            'recipient_id.exists' => __('Le destinataire sélectionné est introuvable.'),
        ];
    }
}
