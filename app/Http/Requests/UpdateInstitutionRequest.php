<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Users\Models\Institution;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateInstitutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('institution.edit') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper(trim((string) $this->input('code'))),
            ]);
        }

        $phone = $this->input('contact_phone');
        if (is_string($phone)) {
            $phone = trim($phone);
        }
        if ($phone === '' || $phone === null) {
            $this->merge(['contact_phone' => null]);
        } else {
            $this->merge(['contact_phone' => $phone]);
        }

        $address = $this->input('address');
        if (is_string($address)) {
            $address = trim($address);
        }
        if ($address === '' || $address === null) {
            $this->merge(['address' => null]);
        } else {
            $this->merge(['address' => $address]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Institution $institution */
        $institution = $this->route('institution');

        return [
            'name' => ['required', 'string', 'max:2000'],
            'code' => ['required', 'string', 'max:50', Rule::unique('institutions', 'code')->ignore($institution->id)],
            'contact_email' => ['required', 'string', 'email:rfc', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50', 'regex:/^[\d\s+().-]{8,32}$/'],
            'address' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp,svg', 'max:4096'],
            'type' => ['sometimes', 'string', Rule::in(['ministry', 'university'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'logo.mimes' => 'Le logo doit être un fichier de type: jpg, jpeg, png, gif, webp ou svg.',
            'logo.max' => 'Le logo ne doit pas dépasser 4 Mo.',
        ];
    }
}
