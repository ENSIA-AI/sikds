<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreInstitutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('institution.create') ?? false;
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
        return [
            'name' => ['required', 'string', 'max:2000'],
            'code' => ['required', 'string', 'max:50', Rule::unique('institutions', 'code')],
            'contact_email' => ['required', 'string', 'email:rfc', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50', 'regex:/^[\d\s+().-]{8,32}$/'],
            'address' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'max:4096'],
            'type' => ['sometimes', 'string', Rule::in(['ministry', 'university'])],
        ];
    }
}
